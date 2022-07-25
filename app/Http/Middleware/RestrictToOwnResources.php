<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RestrictToOwnResources
{
    /**
     * This middleware is designed to restrict access to resources relating to the authenticated user. It only applies to sellers.
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //$paramUserId = $request->route('user')->id;
        $model = $request->route('user') ?? current($request->route()->parameters()); // Order is important. Ensure that if {user} is not among route parameters, user_id is an attribute of model. Only applicable to sellers.
        $paramUserId =  $model->user_id ?? $model->id; // Order is important.
        $userId = $request->user()->user_id ?? $request->user()->id; // Order is important.
        if ($request->user()->tokenCan('seller') && $paramUserId != $userId) {
            return response()->json(['error' => 'The user is only authorized to access his/her own record(s)'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
