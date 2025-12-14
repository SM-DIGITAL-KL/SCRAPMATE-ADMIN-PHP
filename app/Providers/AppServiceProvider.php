<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // ✅ Import URL facade

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // IMPORTANT: Only force HTTPS if we're actually using HTTPS
        // This prevents redirect loops when SSL certificates are not installed
        if (env('APP_ENV') !== 'local') {
            $appUrl = env('APP_URL', '');
            
            // Only force HTTPS if:
            // 1. APP_URL is explicitly set to HTTPS
            // 2. AND the request is actually coming through HTTPS (not behind a proxy that strips it)
            if (!empty($appUrl) && str_starts_with(strtolower($appUrl), 'https://')) {
                // Check if we're behind a proxy/load balancer
                // If X-Forwarded-Proto is set, trust it; otherwise check the request
                if (request()->header('X-Forwarded-Proto') === 'https' || 
                    request()->secure() || 
                    request()->server('HTTPS') === 'on') {
                    URL::forceScheme('https');
                }
                // If APP_URL is HTTPS but request is HTTP, don't force (prevents redirect loop)
            }
            // If APP_URL is HTTP or not set, never force HTTPS
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Removed view composer that was making API calls on every view load
        // This was causing significant performance issues
        // Admin profile should be loaded only when needed in specific views
    }
}
