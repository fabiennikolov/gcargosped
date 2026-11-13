<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Keeps pictures at the size the site actually shows them.
 *
 * A photo straight from a phone is 4000 px wide and several megabytes, while
 * the widest place the site draws one is well under 1600 px. Every upload that
 * goes through {@see store()} is scaled down to the width the field asks for
 * and saved as WebP, so nobody has to prepare pictures by hand and a slow
 * mobile connection is never asked to download pixels it cannot show.
 *
 * SVG and GIF are left alone — a vector has no pixels to shed, and GD would
 * flatten an animation to its first frame.
 */
class ImageOptimizer
{
    private const UNTOUCHED = ['image/svg+xml', 'image/gif'];

    /**
     * Saves an upload to $directory on $disk, scaled to at most $maxWidth px
     * wide, and returns the stored path. Anything that cannot be processed is
     * stored exactly as it came in.
     */
    public static function store(TemporaryUploadedFile $file, string $disk, string $directory, int $maxWidth, int $quality = 80, ?int $thumbWidth = null): string
    {
        $mime = (string) $file->getMimeType();

        if (! in_array($mime, self::UNTOUCHED, true)) {
            $image = self::load($file->getRealPath(), $mime);

            if ($image) {
                $path = trim($directory, '/').'/'.Str::ulid().'.webp';
                $full = self::fit($image, $maxWidth);
                Storage::disk($disk)->put($path, self::encode($full, 'webp', $quality), 'public');

                if ($thumbWidth) {
                    Storage::disk($disk)->put(self::thumbPath($path, $thumbWidth), self::encode(self::fit($full, $thumbWidth), 'webp', 74), 'public');
                }

                return $path;
            }
        }

        return $file->store($directory, $disk);
    }

    /**
     * Re-encodes a file in place, in its own format, so the paths already
     * saved in the database keep working. The file is replaced only when the
     * result is meaningfully smaller. Returns the bytes saved.
     */
    public static function shrink(string $path, int $maxWidth, int $quality = 78): int
    {
        $mime = (string) (getimagesize($path)['mime'] ?? '');
        $format = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        $image = $format ? self::load($path, $mime) : null;

        if (! $image) {
            return 0;
        }

        $before = filesize($path);
        $data = self::encode(self::fit($image, $maxWidth), $format, $quality);

        if (strlen($data) > $before * 0.95) {
            return 0;
        }

        file_put_contents($path, $data);

        return $before - strlen($data);
    }

    /**
     * Where the small copy of a picture lives: `photo.webp` → `photo-720.webp`.
     * The listing cards use it, so a card does not download the full-size
     * photo it shows at a third of the width.
     */
    public static function thumbPath(string $path, int $width): string
    {
        return preg_replace('/\.[^.\/]+$/', '', $path).'-'.$width.'.webp';
    }

    /** Writes the small copy next to an existing file. Returns false when it cannot be made. */
    public static function thumbnail(string $path, int $width, int $quality = 74): bool
    {
        $mime = (string) (getimagesize($path)['mime'] ?? '');
        $image = self::load($path, $mime);

        if (! $image) {
            return false;
        }

        return (bool) file_put_contents(self::thumbPath($path, $width), self::encode(self::fit($image, $width), 'webp', $quality));
    }

    private static function load(string $path, string $mime): ?GdImage
    {
        // Decoding a large photo takes far more memory than the file weighs.
        @ini_set('memory_limit', '512M');

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image) {
            return null;
        }

        // GD drops EXIF on re-encoding, so a phone photo that relied on the
        // orientation flag has to be turned for real first.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $angle = match (@exif_read_data($path)['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($angle) {
                $image = imagerotate($image, $angle, 0) ?: $image;
            }
        }

        return $image;
    }

    private static function fit(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        return imagescale($image, $maxWidth, -1, IMG_BICUBIC) ?: $image;
    }

    private static function encode(GdImage $image, string $format, int $quality): string
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();

        match ($format) {
            'jpeg' => imagejpeg($image, null, $quality),
            'png' => imagepng($image, null, 9),
            default => imagewebp($image, null, $quality),
        };

        return (string) ob_get_clean();
    }
}
