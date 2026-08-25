<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceCardResource;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Canonical;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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

    /** @return Collection<int, array<string, string|null>> */
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
            // route() builds on the host the request came in on; the sitemap
            // has to list the same addresses the pages call canonical, or the
            // two contradict each other.
            ->view('sitemap', ['urls' => $urls->map(fn (array $url) => [
                ...$url,
                'loc' => Canonical::rebase($url['loc']),
            ])])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generated rather than a file in public/ so the Sitemap line always names
     * the same origin as the canonical tags, whatever APP_URL is set to.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Nothing in the admin belongs in an index, and a login form in
            // the results is only an invitation.
            'Disallow: /admin',
            'Disallow: /up',
            '',
            'Sitemap: '.Canonical::origin().'/sitemap.xml',
            '',
        ];

        return response(implode("\n", $lines))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
