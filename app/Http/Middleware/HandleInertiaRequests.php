<?php

namespace App\Http\Middleware;

use App\Models\Service;
use App\Models\Setting;
use App\Support\Canonical;
use App\Support\MediaPath;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],

            // Contact details and hero copy are editable in the admin and used
            // by the header, footer and every form, so they ride along on every
            // response. Settings::map() is cached, so this costs no query.
            'settings' => $this->settings(),

            // The same address the <link rel="canonical"> in the root template
            // carries, so og:url cannot drift away from it.
            'canonical' => Canonical::for($request),

            // Nav is data-driven: adding a service in the admin puts it in the
            // menu without a code change.
            'nav' => [
                'mainServices' => Service::published()->main()->orderBy('sort_order')
                    ->get(['slug', 'title', 'is_main'])
                    ->map(fn (Service $s) => ['title' => $s->title, 'url' => $s->url]),
            ],

            'flash' => [
                'inquiry' => fn () => $request->session()->get('inquiry'),
            ],

            // @routes only defines the global route() helper in the browser, so
            // the SSR entry rebuilds it from this payload.
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * The settings table stores the logo as a path; the browser needs a URL,
     * and resolving it here keeps every component from having to know whether
     * the file came with the old static site or was uploaded in the admin.
     *
     * @return array<string, string|null>
     */
    private function settings(): array
    {
        $settings = Setting::map();
        $settings['logo_url'] = MediaPath::url($settings['logo_path'] ?? null);

        return $settings;
    }
}
