<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Images reach the database from two places: the seeder points at files that
 * shipped with the original site (public/assets/...), while anything uploaded
 * through Filament lands on the public disk (storage/app/public/...). This
 * turns either kind of stored path into a browser URL.
 */
class MediaPath
{
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        // Files that came with the original static site are served straight
        // out of the document root.
        if (str_starts_with($path, 'assets/')) {
            return '/'.$path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * The small copy of an image (see ImageOptimizer::thumbPath), or the
     * full-size URL when there is none — a picture uploaded before thumbnails
     * existed still shows.
     */
    public static function thumbUrl(?string $path, int $width = 720): ?string
    {
        if (blank($path) || str_starts_with($path, 'http')) {
            return self::url($path);
        }

        $thumb = ImageOptimizer::thumbPath(ltrim($path, '/'), $width);
        $exists = str_starts_with($thumb, 'assets/')
            ? is_file(public_path($thumb))
            : Storage::disk('public')->exists($thumb);

        return self::url($exists ? $thumb : $path);
    }
}
