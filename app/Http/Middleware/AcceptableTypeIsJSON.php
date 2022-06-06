<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AcceptableTypeIsJSON
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
        if (!$request->wantsJson()) {
            return response()->json(['error' => 'This API only serves JSON responses.'], Response::HTTP_NOT_ACCEPTABLE);
        }

        return $next($request);
    }
}
