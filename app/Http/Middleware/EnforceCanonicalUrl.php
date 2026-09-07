<?php

namespace App\Http\Middleware;

use App\Support\Canonical;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every visitor to the one address the page claims as its own.
 *
 * A <link rel="canonical"> is a hint, not an instruction: when www and the bare
 * domain both answer with 200 and identical content, Google is free to weigh
 * its own signals — inbound links, history, the address people type — and pick
 * the other one. That is exactly what Search Console reported for /about, where
 * the page named https://gcargosped.com/about and Google indexed the www
 * version anyway. A 301 is not a hint; the duplicate stops existing.
 *
 * The redirect belongs in nginx too (see deploy/nginx.conf) and is faster
 * there, but the server's configuration lives outside this repository and this
 * is the only copy that travels with a deployment. Doing it in both places
 * costs one comparison per request and cannot loop: the canonical host itself
 * is never redirected.
 */
class EnforceCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        // GET and HEAD only. A 301 drops the body of a POST, and a form on a
        // page cached at the old address should still submit rather than lose
        // what someone typed.
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $target = $this->target($request);

        return $target === null
            ? $next($request)
            : redirect($target, 301);
    }

    /**
     * The address this request should have been made to, or null if it already
     * was the right one.
     */
    private function target(Request $request): ?string
    {
        $wrongHost = $this->isSiblingOfCanonicalHost($request);

        $path = Canonical::path($request->getPathInfo());
        $wrongPath = $path !== $request->getPathInfo();

        if (! $wrongHost && ! $wrongPath) {
            return null;
        }

        // Only the host is rewritten to the canonical origin. The scheme rides
        // along with it, but a request that is already on the right host is
        // left alone even over http: behind a proxy that terminates TLS,
        // Laravel can see http on a request the browser made over https, and
        // redirecting on that guess is how a site ends up in a loop. HTTPS is
        // the web server's job, and it is already doing it.
        $base = $wrongHost
            ? Canonical::origin($request)
            : $request->getSchemeAndHttpHost();

        // utm_* and gclid have to survive the hop, or a 301 quietly deletes
        // the campaign that paid for the visit. Taken off the raw request URI
        // rather than from getQueryString(), which sorts the parameters — an
        // address that comes back reordered is one more thing to explain when
        // someone compares it with what they pasted into an ad.
        $query = (string) parse_url($request->getRequestUri(), PHP_URL_QUERY);

        return $base.$path.($query === '' ? '' : '?'.$query);
    }

    /**
     * True when the visitor is on www and the site lives on the bare domain, or
     * the other way round — the one case where a redirect is certainly right.
     *
     * Any other host is left as it is: a staging domain, an IP, localhost or a
     * preview URL serves the same code, and sending those to production would
     * make the site untestable anywhere but production.
     */
    private function isSiblingOfCanonicalHost(Request $request): bool
    {
        $canonical = (string) parse_url(Canonical::origin($request), PHP_URL_HOST);
        $host = $request->getHost();

        if ($canonical === '' || strcasecmp($host, $canonical) === 0) {
            return false;
        }

        return strcasecmp($this->bare($host), $this->bare($canonical)) === 0;
    }

    private function bare(string $host): string
    {
        return preg_replace('/^www\./i', '', $host) ?? $host;
    }
}
