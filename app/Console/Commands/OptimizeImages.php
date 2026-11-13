<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use Illuminate\Console\Command;

/**
 * Scales down pictures that were uploaded, or shipped with the site, before
 * uploads were optimised automatically. Safe to run again: a file that is
 * already small enough is left alone.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Shrink oversized images in public/assets/img and the uploaded media';

    /** directory (relative to the project) => [max width, quality] */
    private const TARGETS = [
        'public/assets/img/services' => [1280, 74],
        'public/assets/img' => [1280, 74],
        'storage/app/public/services' => [1280, 76],
        'storage/app/public/posts' => [1280, 76],
        'storage/app/public/partners' => [480, 80],
        'storage/app/public/branding' => [400, 85],
    ];

    public function handle(): int
    {
        $saved = 0;

        foreach (self::TARGETS as $directory => [$width, $quality]) {
            foreach (glob(base_path($directory).'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $file) {
                if (preg_match('/-\d+\.webp$/', $file)) {
                    continue; // a thumbnail made below
                }

                $bytes = ImageOptimizer::shrink($file, $width, $quality);

                if ($bytes > 0) {
                    $this->line(sprintf('%s  -%d KB', str_replace(base_path().'/', '', $file), intdiv($bytes, 1024)));
                    $saved += $bytes;
                }
            }
        }

        // Small copies for the service cards.
        foreach (['public/assets/img/services', 'storage/app/public/services'] as $directory) {
            foreach (glob(base_path($directory).'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $file) {
                if (! preg_match('/-\d+\.webp$/', $file)) {
                    ImageOptimizer::thumbnail($file, 720);
                }
            }
        }

        $this->info(sprintf('Saved %.1f MB', $saved / 1048576));

        return self::SUCCESS;
    }
}
