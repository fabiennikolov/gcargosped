<?php

namespace App\Http\Middleware;

use App\Models\Service;
use App\Models\Setting;
use App\Support\Canonical;
use App\Support\GoogleAds;
use App\Support\Marketing;
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
     * @return array<string, string|bool|array<int, string>|null>
     */
    private function settings(): array
    {
        $settings = Setting::map();
        $settings['logo_url'] = MediaPath::url($settings['logo_path'] ?? null);

        // The Ads ID reaches the browser only after it has been checked, and
        // the conversion target is assembled here, so nothing in the front end
        // has to know that send_to is the ID and the label with a slash
        // between them. Both are null until the admin fills the section in.
        $settings['google_ads_id'] = GoogleAds::id();
        $settings['google_ads_send_to'] = GoogleAds::sendTo();

        // The rest of the tracking codes, the same way: the browser gets the
        // checked value or null, never what was typed. The front end needs
        // them to report a lead and the pages Inertia swaps in; the tags
        // themselves are rendered by the root template.
        $settings['ga4_id'] = Marketing::ga4Id();
        $settings['gtm_id'] = Marketing::gtmId();
        $settings['meta_pixel_ids'] = Marketing::metaPixelIds();
        $settings['linkedin_partner_id'] = Marketing::linkedinPartnerId();
        $settings['linkedin_lead_id'] = Marketing::linkedinLeadId();

        // The cookie banner asks only when there is something to ask about.
        // Its text is plain text, rendered as such; the link is checked.
        $settings['tracking_consent_required'] = Marketing::trackingConfigured();
        $settings['privacy_url'] = Marketing::privacyUrl();

        // Only ever needed in the <head>, which the template renders itself.
        unset($settings['google_site_verification'], $settings['facebook_domain_verification']);

        return $settings;
    }
}
