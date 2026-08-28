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
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAdsId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @js($googleAdsId));
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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
