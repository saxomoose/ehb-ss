<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureUserBelongsToEvent
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
        $model = current($request->route()->parameters()); // Order of parameters is important. {user} should not be first parameter.
        $eventId =  $model->event_id ?? $model->id; // Order is important.
        if ($eventId != $request->user()->event_id) {
            return response()->json(['error' => 'The user does not belong to this event.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
