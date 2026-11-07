<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAuthenticatedPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $wasAuthenticated = $request->user() !== null;
        $response = $next($request);

        if ($wasAuthenticated || $request->user() !== null) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
