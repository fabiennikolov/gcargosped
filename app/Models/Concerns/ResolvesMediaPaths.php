<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Images reach the database from two places: the seeder points at files that
 * shipped with the original site (public/assets/...), while anything uploaded
 * through Filament lands on the public disk (storage/app/public/...). This
 * turns either kind of stored path into a browser URL.
 */
trait ResolvesMediaPaths
{
    protected function mediaUrl(?string $path): ?string
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
}
