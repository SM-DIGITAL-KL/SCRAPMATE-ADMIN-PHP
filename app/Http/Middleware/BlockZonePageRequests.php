<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockZonePageRequests
{
    /**
     * Restrict zone dashboard users from opening non-dashboard pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = strtolower((string) session('user_email', ''));

        if (!preg_match('/^zone(\d{1,2})@scrapmate\.co\.in$/', $email)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        $allowed = [
            '',
            'dashboard',
            'admin/dashboard',
            'admin/dashboard/v2',
            'logout',
        ];

        if (!in_array($path, $allowed, true)) {
            abort(404);
        }

        return $next($request);
    }
}
