<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use ResolvesMediaPaths;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    public function scopeSub(Builder $query): Builder
    {
        return $query->where('is_main', false);
    }

    /**
     * Main services and sub-services live under different URL prefixes, kept
     * identical to the old Webflow site so indexed links keep resolving.
     */
    public function getUrlAttribute(): string
    {
        return $this->is_main
            ? route('service.show', $this->slug)
            : route('subservice.show', $this->slug);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }
}
