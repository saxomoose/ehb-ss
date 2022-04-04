<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RestrictToAccountableUser
{
    /**
     * This middelware is designed to restrict access of the resource to the user who created it and the admin.
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $model = current($request->route()->parameters());
        $paramUserId = $model->created_by;
        $userId = $request->user()->id;

        if ($request->user()->tokenCan('manager') && $userId != $paramUserId) {
            return response()->json(['error' => 'The user is only authorised to access the record(s) for which he/she is accountable'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
