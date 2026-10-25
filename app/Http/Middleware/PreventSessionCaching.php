<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSessionCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Web responses contain session-specific CSRF tokens and account data.
        // Static Vite assets are served directly by Nginx/Apache, outside Laravel.
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
