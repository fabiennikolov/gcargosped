<?php

namespace App\Http\Middleware;

use App\Models\Service;
use App\Models\Setting;
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
            'settings' => Setting::map(),

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
}
