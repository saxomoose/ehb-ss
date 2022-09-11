<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecureResponseHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        // The no-store response directive indicates that any caches of any kind (private or shared) should not store this response.
        $response->header('Cache-Control', 'no-store');
        //$response->header('Strict-Transport-Security', 'max-age=31536000; includeSubdomains');
        $response->header('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
