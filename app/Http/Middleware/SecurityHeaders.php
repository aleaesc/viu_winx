<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('Referrer-Policy','no-referrer');
        $response->headers->set('Permissions-Policy','geolocation=()');
        $response->headers->set('Content-Security-Policy',
            "default-src 'self' https:; " .
            "img-src 'self' data: https:; " .
            "script-src 'self' 'unsafe-inline' https:; " .
            "style-src 'self' 'unsafe-inline' https:; " .
            "font-src 'self' https: data:;"
        );
        return $response;
    }
}
