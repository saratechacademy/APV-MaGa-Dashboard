<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends pages requested over plain HTTP to HTTPS, on installations whose
 * APP_URL is an https:// address. Without it a login typed as http://…
 * travels unencrypted even though the certificate is installed.
 *
 * Only GET/HEAD are redirected: a redirect makes a client replay a POST as a
 * GET, which would silently drop the readings of an IoT device still
 * configured with an http:// address.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !$request->secure()
            && $request->isMethodSafe()
            && str_starts_with((string) config('app.url'), 'https://')
        ) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
