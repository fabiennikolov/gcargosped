<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- No <title> here on purpose: @inertiaHead emits the per-page title
             from the SSR render, and a fallback tag alongside it would leave
             two <title> elements on every page, with the generic one first. --}}

        <link rel="canonical" href="{{ url()->current() }}">

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
