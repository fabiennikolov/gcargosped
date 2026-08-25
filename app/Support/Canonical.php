<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The one address a page admits to living at.
 *
 * url()->current() answers with whatever host the visitor happened to type, so
 * the same page self-canonicalises as https://www.gcargosped.com/services to
 * one visitor and as http://gcargosped.com/services to the next — which is how
 * a single page ends up competing with itself in Google. The origin is taken
 * from APP_URL instead, and only the path comes from the request.
 */
class Canonical
{
    public static function for(Request $request): string
    {
        return self::origin($request).self::path($request->getPathInfo());
    }

    /**
     * Absolute base of every canonical URL. APP_URL is the site's real address
     * in production; a misconfigured or relative one falls back to the request
     * so local development and `php artisan serve` still emit a usable tag.
     */
    public static function origin(?Request $request = null): string
    {
        $configured = rtrim((string) config('app.url'), '/');

        if (preg_match('#^https?://[^/]+#', $configured, $match) === 1) {
            return $match[0];
        }

        return rtrim(($request ?? request())->getSchemeAndHttpHost(), '/');
    }

    /**
     * Re-points an already generated URL at the canonical origin, so a link in
     * the sitemap says the same thing the page's own canonical tag does.
     */
    public static function rebase(string $url, ?Request $request = null): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return self::origin($request).self::path($path);
    }

    /**
     * One page, one path: no query string (?page=2 on /blog is the same list
     * Google already has), no trailing slash, and "/" stays "/". The path is
     * left percent-encoded exactly as the request carried it.
     */
    public static function path(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
