<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        /*
         * The WhatsApp click beacon is sent with navigator.sendBeacon while the
         * browser is already leaving for wa.me, and sendBeacon cannot set the
         * CSRF header — so every click would 419. Exempting it costs nothing:
         * the endpoint is unauthenticated, changes no state a forgery could
         * abuse, and is rate limited at the route. The figure it feeds is a
         * counter, not a decision.
         */
        $middleware->validateCsrfTokens(except: ['track/whatsapp']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
