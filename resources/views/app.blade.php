<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Google tag (gtag.js) for Google Ads, on every page of the public
             site — this is the root template every one of them is rendered
             into. The ID comes from Настройки на сайта → Google Ads; an empty
             or malformed one leaves the page with no tag at all, so a site that
             has not been connected to an Ads account ships nothing.

             Nothing here counts a conversion. A page load is not a lead, and
             the conversion action must not be the "page load" kind either, or
             every visit to the home page is reported as one. The conversion
             event is sent from the inquiry form, once, when the thank-you card
             appears. See App\Support\GoogleAds. --}}
        @php($googleAdsId = \App\Support\GoogleAds::id())
        @if ($googleAdsId)
            {{-- gtag.js is 160 KB that runs for half a second on a phone. It is
                 fetched on the visitor's first touch, scroll, key press or
                 pointer move — or after 4 s of doing none of those — so it never
                 competes with the first paint. The gtag() stub below queues
                 anything sent before it arrives, and the form interacts first,
                 so the inquiry conversion is still reported. --}}
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @js($googleAdsId));
                (function () {
                    var loaded = false;
                    var events = ['pointerdown', 'pointermove', 'keydown', 'scroll', 'touchstart'];
                    function load() {
                        if (loaded) return;
                        loaded = true;
                        events.forEach(function (e) { window.removeEventListener(e, load); });
                        var s = document.createElement('script');
                        s.async = true;
                        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + @js($googleAdsId);
                        document.head.appendChild(s);
                    }
                    events.forEach(function (e) { window.addEventListener(e, load, { once: true, passive: true }); });
                    setTimeout(load, 4000);
                })();
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
        @inertia
    </body>
</html>
