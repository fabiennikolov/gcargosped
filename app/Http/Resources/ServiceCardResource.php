<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The trimmed shape used by every service listing (home, services index,
 * "related services" on a detail page). Detail pages send the body separately.
 *
 * @mixin \App\Models\Service
 */
class ServiceCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->image_url,
            'icon' => $this->icon_svg,
            'isMain' => $this->is_main,
            'url' => $this->url,
        ];
    }
}
