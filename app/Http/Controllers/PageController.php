<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceCardResource;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageController extends Controller
{
    public function home(): InertiaResponse
    {
        return Inertia::render('site/home', [
            // One list for the marquee, headline services first — the same
            // order the original carousel used.
            'services' => ServiceCardResource::collection(
                Service::published()->orderByDesc('is_main')->orderBy('sort_order')->get(),
            ),
            'partners' => $this->partners(),
            'meta' => [
                'title' => Setting::get('seo_title'),
                'description' => Setting::get('seo_description'),
            ],
        ]);
    }

    public function about(): InertiaResponse
    {
        return Inertia::render('site/about', [
            'meta' => [
                'title' => 'За нас — '.Setting::get('site_name'),
                'description' => 'Глобал Карго Спед — транспорт, спедиция и логистика от Пловдив '
                    .'за България, Европа и Турция.',
            ],
        ]);
    }

    public function contact(): InertiaResponse
    {
        return Inertia::render('site/contact', [
            'meta' => [
                'title' => 'Контакти — '.Setting::get('site_name'),
                'description' => 'Свържете се с Глобал Карго Спед за оферта: '
                    .Setting::get('phone').', '.Setting::get('email').'.',
            ],
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, array<string, string|null>> */
    private function partners()
    {
        return Partner::published()->orderBy('sort_order')->get()
            ->map(fn (Partner $partner) => [
                'name' => $partner->name,
                'logo' => $partner->logo_url,
                'url' => $partner->url,
            ]);
    }

    /**
     * Generated rather than static so new services and posts appear without
     * anyone remembering to update a file.
     */
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('services'), 'priority' => '0.9'],
            ['loc' => route('about'), 'priority' => '0.7'],
            ['loc' => route('contact'), 'priority' => '0.7'],
            ['loc' => route('blog'), 'priority' => '0.6'],
        ]);

        foreach (Service::published()->orderBy('sort_order')->get() as $service) {
            $urls->push([
                'loc' => $service->url,
                'lastmod' => $service->updated_at?->toAtomString(),
                'priority' => $service->is_main ? '0.9' : '0.8',
            ]);
        }

        foreach (Post::published()->get() as $post) {
            $urls->push([
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at?->toAtomString(),
                'priority' => '0.5',
            ]);
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
