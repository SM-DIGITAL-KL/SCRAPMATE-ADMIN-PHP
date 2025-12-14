<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NodeApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function index(Request $request)
    {
        // Check if cache should be cleared
        if ($request->has('clear_cache') && $request->get('clear_cache') === 'true') {
            // Clear Node.js cache
            $this->nodeApi->clearCache('/admin/dashboard');
            $this->nodeApi->clearCache('/admin/dashboard/kpis');
            $this->nodeApi->clearCache('/admin/dashboard/charts');
            $this->nodeApi->clearCache('/admin/dashboard/recent-orders');
            $this->nodeApi->clearCache('/admin/dashboard/call-logs');
            
            // Clear PHP cache (10 minutes cache)
            Cache::forget('dashboard_kpis');
            Cache::forget('dashboard_charts');
            Cache::forget('dashboard_recent_orders_8');
            Cache::forget('dashboard_call_logs');
            
            Log::info('Dashboard cache cleared (both Node.js and PHP) via request parameter');
        }
        
        // Return minimal data - actual data will be loaded via AJAX
        $data = [
            'pagename' => 'Dashboard',
            'shops' => 0,
            'customers' => 0,
            'this_month_customers' => 0,
            'this_month_vendors' => 0,
            'deliveryboys' => 0,
            'users' => 0,
            'orders' => 0,
            'calllogs' => 0,
            'todayscalllogs' => 0,
            'recent_orders' => [],
            'locations' => [],
            'pending_b2b_approvals' => 0,
            'v2_users_count' => 0,
            'v2_b2b_count' => 0,
            'v2_b2c_count' => 0,
            'door_step_buyers_count' => 0,
            'v2_door_step_buyers_count' => 0,
            'month_wise_customers_count' => array_fill(0, 12, 0),
            'month_wise_vendor_count' => array_fill(0, 12, 0),
            'month_wise_orders_count' => array_fill(0, 12, 0),
            'month_wise_completed_orders_count' => array_fill(0, 12, 0),
            'month_wise_pending_orders_count' => array_fill(0, 12, 0),
        ];
        
        return view('admin/dashboard', $data);
    }

    // Cached dashboard API endpoints (10 minutes cache)
    public function dashboardKPIs(Request $request)
    {
        $cacheKey = 'dashboard_kpis';
        $cacheTime = 600; // 10 minutes in seconds
        
        // Check if we have stale cache (in case Node.js times out)
        $staleCache = Cache::get($cacheKey);
        
        try {
            return Cache::remember($cacheKey, $cacheTime, function () use ($staleCache) {
                try {
                    // Use longer timeout (60 seconds) for dashboard endpoints
                    $apiResponse = $this->nodeApi->get('/admin/dashboard/kpis', [], 60);
                    
                    // Log the response for debugging
                    Log::info('Dashboard KPIs API Response', [
                        'status' => $apiResponse['status'] ?? 'unknown',
                        'has_data' => isset($apiResponse['data']),
                        'message' => $apiResponse['msg'] ?? 'no message'
                    ]);
                    
                    if (isset($apiResponse['status']) && $apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Dashboard KPIs retrieved',
                            'data' => $apiResponse['data']
                        ]);
                    }
                    
                    // If error but we have stale cache, return it
                    if ($staleCache) {
                        Log::warning('Dashboard KPIs: Node.js error, returning stale cache', [
                            'api_status' => $apiResponse['status'] ?? 'unknown',
                            'api_msg' => $apiResponse['msg'] ?? 'no message'
                        ]);
                        return $staleCache;
                    }
                    
                    // Check if it's an API key error
                    if (isset($apiResponse['error']) && strpos(strtolower($apiResponse['error']), 'api key') !== false) {
                        Log::error('Dashboard KPIs: API Key mismatch', [
                            'error' => $apiResponse['error'],
                            'hint' => $apiResponse['hint'] ?? 'Check NODE_API_KEY in PHP .env matches API_KEY in Node.js .env'
                        ]);
                        return response()->json([
                            'status' => 'error',
                            'msg' => 'API Key mismatch: ' . ($apiResponse['hint'] ?? 'Check that NODE_API_KEY in Laravel .env matches API_KEY in Node.js .env'),
                            'data' => null
                        ], 401);
                    }
                    
                    // Return the actual error from Node.js API
                    return response()->json([
                        'status' => 'error',
                        'msg' => $apiResponse['msg'] ?? ($apiResponse['error'] ?? 'Failed to load KPIs from Node.js API'),
                        'data' => null,
                        'debug' => [
                            'api_status' => $apiResponse['status'] ?? 'unknown',
                            'api_error' => $apiResponse['error'] ?? null
                        ]
                    ], 500);
                } catch (\Exception $e) {
                    Log::error('Dashboard KPIs API error: ' . $e->getMessage(), [
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Return stale cache if available
                    if ($staleCache) {
                        Log::warning('Dashboard KPIs: Exception, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Error loading dashboard KPIs: ' . $e->getMessage(),
                        'data' => null
                    ], 500);
                }
            });
        } catch (\Exception $e) {
            // If Cache::remember fails (e.g., timeout), return stale cache
            if ($staleCache) {
                Log::warning('Dashboard KPIs: Cache remember failed, returning stale cache');
                return $staleCache;
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading dashboard KPIs: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function dashboardCharts(Request $request)
    {
        $cacheKey = 'dashboard_charts';
        $cacheTime = 600; // 10 minutes in seconds
        
        $staleCache = Cache::get($cacheKey);
        
        try {
            return Cache::remember($cacheKey, $cacheTime, function () use ($staleCache) {
                try {
                    $apiResponse = $this->nodeApi->get('/admin/dashboard/charts', [], 60);
                    if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Dashboard charts retrieved',
                            'data' => $apiResponse['data']
                        ]);
                    }
                    if ($staleCache) {
                        Log::warning('Dashboard charts: Node.js error, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Failed to load charts',
                        'data' => null
                    ], 500);
                } catch (\Exception $e) {
                    Log::error('Dashboard charts API error: ' . $e->getMessage());
                    if ($staleCache) {
                        Log::warning('Dashboard charts: Exception, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Error loading dashboard charts',
                        'data' => null
                    ], 500);
                }
            });
        } catch (\Exception $e) {
            if ($staleCache) {
                Log::warning('Dashboard charts: Cache remember failed, returning stale cache');
                return $staleCache;
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading dashboard charts',
                'data' => null
            ], 500);
        }
    }

    public function dashboardRecentOrders(Request $request)
    {
        $limit = $request->get('limit', 8);
        $cacheKey = 'dashboard_recent_orders_' . $limit;
        $cacheTime = 600; // 10 minutes in seconds
        
        $staleCache = Cache::get($cacheKey);
        
        try {
            return Cache::remember($cacheKey, $cacheTime, function () use ($limit, $staleCache) {
                try {
                    $apiResponse = $this->nodeApi->get('/admin/dashboard/recent-orders', ['limit' => $limit], 60);
                    if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Recent orders retrieved',
                            'data' => $apiResponse['data']
                        ]);
                    }
                    if ($staleCache) {
                        Log::warning('Dashboard recent orders: Node.js error, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Failed to load recent orders',
                        'data' => null
                    ], 500);
                } catch (\Exception $e) {
                    Log::error('Dashboard recent orders API error: ' . $e->getMessage());
                    if ($staleCache) {
                        Log::warning('Dashboard recent orders: Exception, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Error loading recent orders',
                        'data' => null
                    ], 500);
                }
            });
        } catch (\Exception $e) {
            if ($staleCache) {
                Log::warning('Dashboard recent orders: Cache remember failed, returning stale cache');
                return $staleCache;
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading recent orders',
                'data' => null
            ], 500);
        }
    }

    public function dashboardCallLogs(Request $request)
    {
        $cacheKey = 'dashboard_call_logs';
        $cacheTime = 600; // 10 minutes in seconds
        
        $staleCache = Cache::get($cacheKey);
        
        try {
            return Cache::remember($cacheKey, $cacheTime, function () use ($staleCache) {
                try {
                    $apiResponse = $this->nodeApi->get('/admin/dashboard/call-logs', [], 60);
                    if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Call logs retrieved',
                            'data' => $apiResponse['data']
                        ]);
                    }
                    if ($staleCache) {
                        Log::warning('Dashboard call logs: Node.js error, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Failed to load call logs',
                        'data' => null
                    ], 500);
                } catch (\Exception $e) {
                    Log::error('Dashboard call logs API error: ' . $e->getMessage());
                    if ($staleCache) {
                        Log::warning('Dashboard call logs: Exception, returning stale cache');
                        return $staleCache;
                    }
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Error loading call logs',
                        'data' => null
                    ], 500);
                }
            });
        } catch (\Exception $e) {
            if ($staleCache) {
                Log::warning('Dashboard call logs: Cache remember failed, returning stale cache');
                return $staleCache;
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading call logs',
                'data' => null
            ], 500);
        }
    }
}
