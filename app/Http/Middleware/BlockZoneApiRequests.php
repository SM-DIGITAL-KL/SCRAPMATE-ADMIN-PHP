<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockZoneApiRequests
{
    /**
     * Block /api endpoints for zone dashboard users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = strtolower((string) session('user_email', ''));

        if (preg_match('/^zone(\d{1,2})@scrapmate\.co\.in$/', $email)) {
            abort(404);
        }

        return $next($request);
    }
}
