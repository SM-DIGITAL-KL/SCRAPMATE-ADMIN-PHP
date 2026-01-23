<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Services\ScrapPriceScraperService;
use App\Services\NodeApiService;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled task: Refresh live prices every 2 hours
Schedule::call(function () {
    try {
        Log::info('🔄 [SCHEDULED] Starting scheduled live prices refresh (every 2 hours)');
        
        // Clear PHP cache
        Cache::forget('live_prices_data');
        Log::info('🗑️  [SCHEDULED] Cleared PHP cache');
        
        // Scrape fresh data
        Log::info('🔄 [SCHEDULED] Scraping fresh data...');
        $scraper = new ScrapPriceScraperService();
        $result = $scraper->scrapeAll();
        $allPrices = $result['results'] ?? [];
        Log::info('✅ [SCHEDULED] Scraped ' . count($allPrices) . ' prices');
        
        if (empty($allPrices)) {
            Log::warning('⚠️  [SCHEDULED] No prices scraped');
            return;
        }
        
        // Cache the scraped data
        Cache::put('live_prices_data', $allPrices, 60 * 60); // 1 hour
        Log::info('💾 [SCHEDULED] Cached scraped data in PHP');
        
        // Update last refresh time
        Cache::put('live_prices_last_refresh', now()->toIso8601String(), 24 * 60 * 60); // Store for 24 hours
        Log::info('💾 [SCHEDULED] Updated last refresh timestamp');
        
        // Sync to DynamoDB and update Redis cache
        try {
            Log::info('🔄 [SCHEDULED] Syncing to DynamoDB and updating Redis cache...');
            $nodeApiService = new NodeApiService();
            
            // Format prices for sync
            $formattedPrices = collect($allPrices)->map(function($price) {
                return [
                    'location' => $price['location'] ?? '',
                    'item' => $price['item'] ?? '',
                    'category' => $price['category'] ?? null,
                    'city' => $price['city'] ?? null,
                    'buy_price' => $price['buy_price'] ?? null,
                    'sell_price' => $price['sell_price'] ?? null,
                    'lme_price' => $price['lme_price'] ?? null,
                    'mcx_price' => $price['mcx_price'] ?? null,
                    'injection_moulding' => $price['injection_moulding'] ?? null,
                    'battery_price' => $price['battery_price'] ?? null,
                    'pe_63' => $price['pe_63'] ?? null,
                    'drum_scrap' => $price['drum_scrap'] ?? null,
                    'blow' => $price['blow'] ?? null,
                    'pe_100' => $price['pe_100'] ?? null,
                    'crate' => $price['crate'] ?? null,
                    'black_cable' => $price['black_cable'] ?? null,
                    'white_pipe' => $price['white_pipe'] ?? null,
                    'grey_pvc' => $price['grey_pvc'] ?? null,
                ];
            })->values()->toArray();
            
            $syncResponse = $nodeApiService->post('/v2/live-prices/sync', ['prices' => $formattedPrices], 120);
            
            if (isset($syncResponse['status']) && $syncResponse['status'] === 'success') {
                Log::info('✅ [SCHEDULED] Successfully synced to DynamoDB and updated Redis cache', [
                    'count' => $syncResponse['data']['synced'] ?? 0
                ]);
            } else {
                Log::warning('⚠️  [SCHEDULED] Sync returned error', [
                    'response' => $syncResponse ?? 'No response'
                ]);
            }
        } catch (\Exception $syncError) {
            Log::error('❌ [SCHEDULED] Error syncing to DynamoDB', [
                'error' => $syncError->getMessage(),
                'trace' => $syncError->getTraceAsString()
            ]);
        }
        
        Log::info('✅ [SCHEDULED] Scheduled refresh completed successfully');
    } catch (\Exception $e) {
        Log::error('❌ [SCHEDULED] Scheduled refresh failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
    }
})->everyTwoHours()->name('refresh-live-prices');
