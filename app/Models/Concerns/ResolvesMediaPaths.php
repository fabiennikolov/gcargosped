<?php

namespace App\Models\Concerns;

use App\Support\MediaPath;

/**
 * Model-side sugar over App\Support\MediaPath, which does the actual work —
 * settings resolve the same paths without being models.
 */
trait ResolvesMediaPaths
{
    protected function mediaUrl(?string $path): ?string
    {
        return MediaPath::url($path);
    }
}
