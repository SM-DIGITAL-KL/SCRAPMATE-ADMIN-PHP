<?php

namespace App\Http\Middleware;

use Closure;

class ApiTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->header('api-key')) {
            if ($request->header('api-key') == env('API_KEY')) {
                return $next($request);
            } else {
                return response()->json([
                'error' => 'api key mismatch'
                ], 401);
            }
        } else {
            return response()->json([
            'error' => 'header api key not found'
            ], 404);
        }
    }
}
