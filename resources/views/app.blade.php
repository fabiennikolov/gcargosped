<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Every tracking and verification code, on every page of the public
             site — this is the root template every one of them is rendered
             into. All of them come from the admin, Маркетинг, and every one is
             rendered only once it has the exact shape its vendor issues; an
             empty or malformed field leaves that tag off the page entirely.
             See App\Support\GoogleAds and App\Support\Marketing.

             Nothing here counts a conversion. A page load is not a lead. The
             lead is reported from the inquiry form, once, when the thank-you
             card appears — see resources/js/lib/marketing.ts. --}}
        @php
            $googleAdsId = \App\Support\GoogleAds::id();
            $ga4Id = \App\Support\Marketing::ga4Id();
            $gtmId = \App\Support\Marketing::gtmId();
            $metaPixelIds = \App\Support\Marketing::metaPixelIds();
            $linkedinPartnerId = \App\Support\Marketing::linkedinPartnerId();
            $googleVerification = \App\Support\Marketing::googleVerification();
            $facebookVerification = \App\Support\Marketing::facebookVerification();
            $googleTagId = $googleAdsId ?? $ga4Id;
            $trackingConfigured = \App\Support\Marketing::trackingConfigured();
        @endphp

        @if ($googleVerification)
            <meta name="google-site-verification" content="{{ $googleVerification }}">
        @endif
        @if ($facebookVerification)
            <meta name="facebook-domain-verification" content="{{ $facebookVerification }}">
        @endif

        {{-- The vendors' libraries are a few hundred KB of script between them
             and would compete with the first paint on a phone. So none is
             fetched until the visitor first touches, scrolls, types or moves
             the pointer — or after 4 s of doing none of those. Each tag below
             defines its stub right away, and every stub queues what it is
             given, so nothing sent before its library arrives is lost: the
             inquiry form needs an interaction first anyway.

             Consent (GDPR): the choice made in the cookie banner is read here,
             before any tag, from localStorage — resources/js/lib/consent.ts
             writes it. Google's tags load either way, under Consent Mode with
             everything denied until the visitor accepts, so they set no
             cookies and send only cookieless pings. Meta and LinkedIn have
             nothing of the kind, so their libraries ("gated") are not fetched
             at all without consent.

             The URLs are written straight into string literals on purpose:
             every ID has already been matched against its vendor's shape
             (letters, digits and a dash at most), so none can close one. --}}
        @if ($trackingConfigured)
            <script>
                (function () {
                    var consent = null;
                    try {
                        var stored = JSON.parse(localStorage.getItem('gcs-consent') || 'null');
                        if (stored && stored.v === 1 && Date.now() - stored.t < 180 * 864e5) {
                            consent = stored.granted === true ? 'granted' : 'denied';
                        }
                    } catch (e) {}
                    window.tagConsent = consent;

                    var queue = [], gated = [], loaded = false;
                    var events = ['pointerdown', 'pointermove', 'keydown', 'scroll', 'touchstart'];
                    function inject(src) {
                        var s = document.createElement('script');
                        s.async = true;
                        s.src = src;
                        document.head.appendChild(s);
                    }
                    function flush() {
                        if (!loaded) return;
                        queue.splice(0).forEach(inject);
                        if (window.tagConsent === 'granted') gated.splice(0).forEach(inject);
                    }
                    function load() {
                        if (loaded) return;
                        loaded = true;
                        events.forEach(function (e) { window.removeEventListener(e, load); });
                        flush();
                    }
                    window.deferTagScript = function (src, needsConsent) {
                        (needsConsent ? gated : queue).push(src);
                        flush();
                    };
                    window.releaseGatedTags = flush;
                    events.forEach(function (e) { window.addEventListener(e, load, { once: true, passive: true }); });
                    setTimeout(load, 4000);
                })();
            </script>
        @endif

        {{-- Google Consent Mode v2: the default has to be set before any
             config or Tag Manager container, so it comes first. --}}
        @if ($googleTagId || $gtmId)
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                (function (c) {
                    gtag('consent', 'default', {
                        ad_storage: c, ad_user_data: c, ad_personalization: c, analytics_storage: c,
                        wait_for_update: 500
                    });
                    gtag('set', 'ads_data_redaction', c === 'denied');
                })(window.tagConsent === 'granted' ? 'granted' : 'denied');
            </script>
        @endif

        {{-- Google tag (gtag.js): one library for Google Ads and Analytics 4,
             loaded once with whichever ID is there and configured for each.
             Analytics counts the pages Inertia swaps in by itself (enhanced
             measurement, "page changes based on browser history events"); Ads
             does not, which is what useMarketingPageViews is for. --}}
        @if ($googleTagId)
            <script>
                gtag('js', new Date());
                @if ($googleAdsId)
                gtag('config', @js($googleAdsId));
                @endif
                @if ($ga4Id)
                gtag('config', @js($ga4Id));
                @endif
                deferTagScript('https://www.googletagmanager.com/gtag/js?id={{ $googleTagId }}');
            </script>
        @endif

        @if ($gtmId)
            <script>
                window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
                deferTagScript('https://www.googletagmanager.com/gtm.js?id={{ $gtmId }}');
            </script>
        @endif

        {{-- Meta Pixel. Every pixel in the list is initialised and every event
             goes to all of them. The pixel's own guess at single-page
             navigation is switched off: the layout sends a PageView for each
             page Inertia swaps in, and two mechanisms would count some twice. --}}
        @if ($metaPixelIds)
            <script>
                !function(f){if(f.fbq)return;var n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[]}(window);
                fbq.disablePushState = true;
                @foreach ($metaPixelIds as $pixelId)
                fbq('init', @js($pixelId));
                @endforeach
                fbq('track', 'PageView');
                deferTagScript('https://connect.facebook.net/en_US/fbevents.js', true);
            </script>
        @endif

        {{-- LinkedIn Insight Tag. --}}
        @if ($linkedinPartnerId)
            <script>
                _linkedin_partner_id = @js($linkedinPartnerId);
                window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
                window._linkedin_data_partner_ids.push(_linkedin_partner_id);
                if (!window.lintrk) {
                    window.lintrk = function (a, b) { window.lintrk.q.push([a, b]); };
                    window.lintrk.q = [];
                }
                deferTagScript('https://snap.licdn.com/li.lms-analytics/insight.min.js', true);
            </script>
        @endif

        {{-- No <title> here on purpose: @inertiaHead emits the per-page title
             from the SSR render, and a fallback tag alongside it would leave
             two <title> elements on every page, with the generic one first. --}}

        {{-- Built from APP_URL rather than the requested host: www and
             non-www served the same page would otherwise each declare
             themselves canonical. See App\Support\Canonical. --}}
        <link rel="canonical" href="{{ \App\Support\Canonical::for(request()) }}">

        {{-- Marks that scripting is available, before anything paints. The
             scroll-reveal animation hides elements only under html.js, so a
             visitor without JavaScript still sees the whole page. --}}
        <script>document.documentElement.classList.add('js');</script>

        {{-- No web font on purpose. The public pages set their text in the
             system font stack (--sans in site.css), so a stylesheet from an
             external font host would only add a render-blocking request —
             and a slow one on a mobile connection — in front of the first
             paint, for a font nothing uses. --}}

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        {{-- No <noscript> pixels on purpose: a visitor without JavaScript
             never sees the cookie banner, so has never consented to being
             tracked, and a pixel that fires anyway would do exactly that. --}}
        @inertia
    </body>
</html>
