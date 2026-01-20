<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScrapPriceScraperService;
use App\Services\NodeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
                Cache::forget($cacheKey);
            }
            
            $allPrices = Cache::remember($cacheKey, $cacheMinutes * 60, function () {
                Log::info('🔄 Cache miss - scraping fresh data');
                $scraper = new ScrapPriceScraperService();
                $result = $scraper->scrapeAll();
                return $result['results'] ?? [];
            });
            
            // Convert to collection
            $prices = collect($allPrices);
            
            // Get unique locations and categories for display
            $locations = $prices->pluck('location')->unique()->sort()->values();
            $categories = $prices->pluck('category')->filter()->unique()->sort()->values();
            
            $data = [
                'pagename' => 'Live Scrap Prices',
                'prices' => $prices,
                'locations' => $locations,
                'categories' => $categories,
                'totalCount' => $prices->count(),
                'lastUpdated' => Cache::has($cacheKey) ? 'Cached (' . now()->format('Y-m-d H:i:s') . ')' : 'Just now'
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
            Log::info('🚀 [LIVE PRICES] Cache invalidation requested');
            
            // Clear PHP cache
            Cache::forget('live_prices_data');
            
            // Invalidate Redis cache via Node.js API (without re-scraping)
            try {
                Log::info('🔄 [LIVE PRICES] Invalidating Redis cache via Node.js API...');
                $nodeApiService = new NodeApiService();
                
                $invalidateResponse = $nodeApiService->post('/v2/live-prices/invalidate-cache', []);
                
                Log::info('📥 [LIVE PRICES] Node.js API cache invalidation response received', [
                    'status' => $invalidateResponse['status'] ?? 'unknown',
                    'msg' => $invalidateResponse['msg'] ?? 'N/A'
                ]);
                
                if ($invalidateResponse['status'] === 'success') {
                    Log::info('✅ [LIVE PRICES] Successfully invalidated Redis cache');
                    $successMessage = 'Cache invalidated successfully! Next request will fetch fresh data from DynamoDB.';
                } else {
                    Log::warning('⚠️  [LIVE PRICES] Cache invalidation returned error', [
                        'response' => $invalidateResponse
                    ]);
                    $successMessage = 'Cache invalidation warning: ' . ($invalidateResponse['msg'] ?? 'Unknown error');
                }
            } catch (\Exception $invalidateError) {
                Log::error('❌ [LIVE PRICES] Failed to invalidate Redis cache', [
                    'error' => $invalidateError->getMessage(),
                    'trace' => $invalidateError->getTraceAsString()
                ]);
                // Don't fail the whole operation if cache invalidation fails
                $successMessage = 'PHP cache cleared. Note: Redis cache invalidation failed: ' . $invalidateError->getMessage();
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
            }
            
            $allPrices = Cache::remember($cacheKey, $cacheMinutes * 60, function () {
                Log::info('🔄 [API] Cache miss - scraping fresh data');
                $scraper = new ScrapPriceScraperService();
                $result = $scraper->scrapeAll();
                return $result['results'] ?? [];
            });
            
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