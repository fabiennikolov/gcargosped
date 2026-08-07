<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostCardResource;
use App\Models\Post;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BlogController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('site/blog', [
            'posts' => PostCardResource::collection(
                Post::published()->with('category')->latest('published_at')->paginate(9),
            ),
            'meta' => [
                'title' => 'Блог — '.Setting::get('site_name'),
                'description' => 'Съвети и новини за транспорт, спедиция и логистика.',
            ],
        ]);
    }

    public function show(Post $post): InertiaResponse
    {
        abort_unless(
            $post->is_published && $post->published_at && $post->published_at->isPast(),
            404,
        );

        $post->load('category');

        return Inertia::render('site/blog-post', [
            'post' => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'coverImage' => $post->cover_image_url,
                'category' => $post->category?->name,
                'readMinutes' => $post->read_minutes,
                'publishedAt' => $post->published_at?->translatedFormat('j F Y'),
            ],
            'related' => PostCardResource::collection(
                Post::published()->with('category')
                    ->where('id', '!=', $post->id)
                    ->latest('published_at')->limit(3)->get(),
            ),
            'meta' => [
                'title' => $post->seo_title ?: $post->title.' — '.Setting::get('site_name'),
                'description' => $post->seo_description ?: $post->excerpt,
            ],
        ]);
    }
}
