<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Post
 */
class PostCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'coverImage' => $this->cover_image_url,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'readMinutes' => $this->read_minutes,
            'publishedAt' => $this->published_at?->translatedFormat('j F Y'),
            'url' => route('blog.show', $this->slug),
        ];
    }
}
