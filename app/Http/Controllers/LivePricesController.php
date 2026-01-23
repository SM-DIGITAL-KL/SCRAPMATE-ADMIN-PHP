<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScrapPriceScraperService;
use App\Services\NodeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LivePricesController extends Controller
{
    /**
     * Display live prices listing page (No database required)
     */
    public function index(Request $request)
    {
        try {
            // Check if we should force refresh
            $forceRefresh = $request->has('refresh');
            
            // Get cached data or scrape new data
            $cacheKey = 'live_prices_data';
            $cacheMinutes = 60; // Cache for 1 hour
            
            if ($forceRefresh) {
                Log::info('🔄 [INDEX] Refresh requested - clearing PHP cache');
                Cache::forget($cacheKey);
                
                // Also invalidate Node.js Redis cache
                try {
                    Log::info('🔄 [INDEX] Invalidating Node.js Redis cache...');
                    $nodeApiService = new NodeApiService();
                    $invalidateResponse = $nodeApiService->post('/v2/live-prices/invalidate-cache', []);
                    
                    if ($invalidateResponse['status'] === 'success') {
                        Log::info('✅ [INDEX] Successfully invalidated Node.js Redis cache');
                    } else {
                        Log::warning('⚠️  [INDEX] Node.js cache invalidation returned error', [
                            'response' => $invalidateResponse
                        ]);
                    }
                } catch (\Exception $invalidateError) {
                    Log::error('❌ [INDEX] Failed to invalidate Node.js Redis cache', [
                        'error' => $invalidateError->getMessage()
                    ]);
                }
            }
            
            $allPrices = Cache::remember($cacheKey, $cacheMinutes * 60, function () {
                Log::info('🔄 [INDEX] Cache miss - scraping fresh data');
                $scraper = new ScrapPriceScraperService();
                $result = $scraper->scrapeAll();
                return $result['results'] ?? [];
            });
            
            // If this is a refresh and we have prices, sync to DynamoDB
            if ($forceRefresh && !empty($allPrices)) {
                try {
                    Log::info('🔄 [INDEX] Syncing fresh prices to DynamoDB...', [
                        'count' => count($allPrices)
                    ]);
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
                        Log::info('✅ [INDEX] Successfully synced prices to DynamoDB and updated Redis cache', [
                            'count' => $syncResponse['data']['synced'] ?? 0
                        ]);
                        // Update last refresh time
                        Cache::put('live_prices_last_refresh', now()->toIso8601String(), 24 * 60 * 60);
                    } else {
                        Log::warning('⚠️  [INDEX] Sync to DynamoDB returned error', [
                            'response' => $syncResponse ?? 'No response'
                        ]);
                    }
                } catch (\Exception $syncError) {
                    Log::error('❌ [INDEX] Error syncing prices to DynamoDB', [
                        'error' => $syncError->getMessage(),
                        'trace' => $syncError->getTraceAsString()
                    ]);
                }
            }
            
            // Convert to collection
            $prices = collect($allPrices);
            
            // Get unique locations and categories for display
            $locations = $prices->pluck('location')->unique()->sort()->values();
            $categories = $prices->pluck('category')->filter()->unique()->sort()->values();
            
            // Calculate next refresh time (every 2 hours)
            $lastRefreshTime = Cache::get('live_prices_last_refresh');
            $nextRefreshTime = null;
            $nextRefreshTimeFormatted = null;
            
            if ($lastRefreshTime) {
                try {
                    // Calculate next refresh (2 hours from last refresh)
                    $lastRefresh = Carbon::parse($lastRefreshTime);
                    $nextRefresh = $lastRefresh->copy()->addHours(2);
                    
                    // If next refresh is in the past (scheduled task might be delayed), calculate from now
                    if ($nextRefresh->isPast()) {
                        // Find the next 2-hour interval from now
                        $now = now();
                        $hoursFromNow = 2 - ($now->diffInMinutes($nextRefresh) % 120) / 60;
                        $nextRefresh = $now->copy()->addHours(ceil($hoursFromNow));
                    }
                    
                    $nextRefreshTime = $nextRefresh;
                    $nextRefreshTimeFormatted = $nextRefresh->diffForHumans();
                } catch (\Exception $e) {
                    // If parsing fails, default to 2 hours from now
                    $nextRefreshTime = now()->addHours(2);
                    $nextRefreshTimeFormatted = 'in 2 hours';
                }
            } else {
                // If no last refresh time, set next refresh to 2 hours from now
                $nextRefreshTime = now()->addHours(2);
                $nextRefreshTimeFormatted = 'in 2 hours';
                
                // Store current time as last refresh if we just refreshed
                if ($forceRefresh) {
                    Cache::put('live_prices_last_refresh', now()->toIso8601String(), 24 * 60 * 60);
                    $nextRefreshTime = now()->addHours(2);
                    $nextRefreshTimeFormatted = 'in 2 hours';
                }
            }
            
            $data = [
                'pagename' => 'Live Scrap Prices',
                'prices' => $prices,
                'locations' => $locations,
                'categories' => $categories,
                'totalCount' => $prices->count(),
                'lastUpdated' => Cache::has($cacheKey) ? 'Cached (' . now()->format('Y-m-d H:i:s') . ')' : 'Just now',
                'nextRefreshTime' => $nextRefreshTime ? $nextRefreshTime->format('Y-m-d H:i:s') : null,
                'nextRefreshTimeFormatted' => $nextRefreshTimeFormatted ?? 'Calculating...'
            ];
            
            return view('admin.liveprices', $data);
            
        } catch (\Exception $e) {
            Log::error('Error loading live prices: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('admin.liveprices', [
                'pagename' => 'Live Scrap Prices',
                'prices' => collect([]),
                'locations' => collect([]),
                'categories' => collect([]),
                'totalCount' => 0,
                'error' => 'Failed to load prices: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Force refresh the cached prices
     * Now only invalidates Redis cache instead of re-scraping
     */
    public function scrapeNow(Request $request)
    {
        try {
            Log::info('🚀 [SCRAPE NOW] Refresh button clicked - starting full refresh process');
            
            // Clear PHP cache
            Cache::forget('live_prices_data');
            Log::info('🗑️  [SCRAPE NOW] Cleared PHP cache');
            
            // Scrape fresh data
            Log::info('🔄 [SCRAPE NOW] Scraping fresh data...');
            $scraper = new ScrapPriceScraperService();
            $result = $scraper->scrapeAll();
            $allPrices = $result['results'] ?? [];
            Log::info('✅ [SCRAPE NOW] Scraped ' . count($allPrices) . ' prices');
            
            if (empty($allPrices)) {
                return redirect()->back()->with('error', 'No prices scraped. Please try again.');
            }
            
            // Cache the scraped data
            Cache::put('live_prices_data', $allPrices, 60 * 60); // 1 hour
            Log::info('💾 [SCRAPE NOW] Cached scraped data in PHP');
            
            // Sync to DynamoDB and update Redis cache
            try {
                Log::info('🔄 [SCRAPE NOW] Syncing to DynamoDB and updating Redis cache...');
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
                    Log::info('✅ [SCRAPE NOW] Successfully synced to DynamoDB and updated Redis cache', [
                        'count' => $syncResponse['data']['synced'] ?? 0
                    ]);
                    // Update last refresh time
                    Cache::put('live_prices_last_refresh', now()->toIso8601String(), 24 * 60 * 60);
                    $successMessage = 'Successfully refreshed prices! ' . ($syncResponse['data']['synced'] ?? 0) . ' prices synced to database and cache updated.';
                } else {
                    Log::warning('⚠️  [SCRAPE NOW] Sync returned error', [
                        'response' => $syncResponse ?? 'No response'
                    ]);
                    $successMessage = 'Prices scraped but sync warning: ' . ($syncResponse['msg'] ?? 'Unknown error');
                }
            } catch (\Exception $syncError) {
                Log::error('❌ [SCRAPE NOW] Error syncing to DynamoDB', [
                    'error' => $syncError->getMessage(),
                    'trace' => $syncError->getTraceAsString()
                ]);
                $successMessage = 'Prices scraped but sync failed: ' . $syncError->getMessage();
            }
            
            // Redirect back with refresh parameter
            return redirect()->route('liveprices.index', ['refresh' => 1])
                ->with('success', $successMessage);
            
        } catch (\Exception $e) {
            Log::error('❌ [LIVE PRICES] Cache invalidation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to invalidate cache: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to return live prices as JSON
     * GET /api/liveprices
     * Returns JSON data for Node.js backend to sync
     */
    public function apiIndex(Request $request)
    {
        try {
            // Check if we should force refresh
            $forceRefresh = $request->has('refresh');
            
            // Get cached data or scrape new data
            $cacheKey = 'live_prices_data';
            $cacheMinutes = 60; // Cache for 1 hour
            
            if ($forceRefresh) {
                Cache::forget($cacheKey);
                
                // Also invalidate Node.js Redis cache when refreshing
                try {
                    Log::info('🔄 [API] Invalidating Node.js Redis cache for live prices...');
                    $nodeApiService = new NodeApiService();
                    $invalidateResponse = $nodeApiService->post('/v2/live-prices/invalidate-cache', []);
                    
                    if ($invalidateResponse['status'] === 'success') {
                        Log::info('✅ [API] Successfully invalidated Node.js Redis cache');
                    } else {
                        Log::warning('⚠️  [API] Node.js cache invalidation returned error', [
                            'response' => $invalidateResponse
                        ]);
                    }
                } catch (\Exception $invalidateError) {
                    Log::error('❌ [API] Failed to invalidate Node.js Redis cache', [
                        'error' => $invalidateError->getMessage()
                    ]);
                    // Continue even if cache invalidation fails
                }
            }
            
            $allPrices = Cache::remember($cacheKey, $cacheMinutes * 60, function () {
                Log::info('🔄 [API] Cache miss - scraping fresh data');
                $scraper = new ScrapPriceScraperService();
                $result = $scraper->scrapeAll();
                return $result['results'] ?? [];
            });
            
            // If this is a refresh and we have prices, sync to DynamoDB
            if ($forceRefresh && !empty($allPrices)) {
                try {
                    Log::info('🔄 [API] Syncing fresh prices to DynamoDB...', [
                        'count' => count($allPrices)
                    ]);
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
                        Log::info('✅ [API] Successfully synced prices to DynamoDB', [
                            'count' => $syncResponse['data']['synced'] ?? 0
                        ]);
                    } else {
                        Log::warning('⚠️  [API] Sync to DynamoDB returned error', [
                            'response' => $syncResponse ?? 'No response'
                        ]);
                    }
                } catch (\Exception $syncError) {
                    Log::error('❌ [API] Error syncing prices to DynamoDB', [
                        'error' => $syncError->getMessage(),
                        'trace' => $syncError->getTraceAsString()
                    ]);
                    // Continue even if sync fails - prices are still cached in PHP
                }
            }
            
            // Convert to array for JSON response
            $prices = collect($allPrices)->map(function($price) {
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
            
            return response()->json([
                'status' => 'success',
                'data' => $prices,
                'count' => count($prices),
                'lastUpdated' => Cache::has($cacheKey) ? now()->toIso8601String() : now()->toIso8601String()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ [API] Error loading live prices: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to load live prices: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}