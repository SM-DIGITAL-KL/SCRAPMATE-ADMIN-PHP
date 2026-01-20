<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NodeApiService;
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
        // Cache clearing removed - data is fetched directly from database
        
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

    public function dashboardKPIs(Request $request)
    {
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
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading dashboard KPIs: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function dashboardCharts(Request $request)
    {
        try {
            $apiResponse = $this->nodeApi->get('/admin/dashboard/charts', [], 60);
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Dashboard charts retrieved',
                    'data' => $apiResponse['data']
                ]);
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to load charts',
                'data' => null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Dashboard charts API error: ' . $e->getMessage());
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
        
        try {
            $apiResponse = $this->nodeApi->get('/admin/dashboard/recent-orders', ['limit' => $limit], 60);
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Recent orders retrieved',
                    'data' => $apiResponse['data']
                ]);
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to load recent orders',
                'data' => null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Dashboard recent orders API error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading recent orders',
                'data' => null
            ], 500);
        }
    }

    public function dashboardCallLogs(Request $request)
    {
        try {
            $apiResponse = $this->nodeApi->get('/admin/dashboard/call-logs', [], 60);
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Call logs retrieved',
                    'data' => $apiResponse['data']
                ]);
            }
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to load call logs',
                'data' => null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Dashboard call logs API error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading call logs',
                'data' => null
            ], 500);
        }
    }

    public function v2Dashboard(Request $request)
    {
        $data = [
            'pagename' => 'V2 User Types Dashboard',
        ];
        
        return view('admin/v2-dashboard', $data);
    }

    public function v2DashboardData(Request $request)
    {
        try {
            $apiResponse = $this->nodeApi->get('/admin/dashboard/v2-user-types', [], 60);
            
            if (isset($apiResponse['status']) && $apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'V2 user types dashboard data retrieved',
                    'data' => $apiResponse['data']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'msg' => $apiResponse['msg'] ?? 'Failed to load v2 dashboard data',
                'data' => null
            ], 500);
        } catch (\Exception $e) {
            Log::error('V2 Dashboard API error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading v2 dashboard data',
                'data' => null
            ], 500);
        }
    }

    public function orderDetails($id)
    {
        try {
            $apiResponse = $this->nodeApi->get('/customer/order/' . $id, [], 60);
            
            if (isset($apiResponse['status']) && $apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Order details retrieved',
                    'data' => $apiResponse['data']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'msg' => $apiResponse['msg'] ?? 'Failed to load order details',
                'data' => null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Order details API error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Error loading order details',
                'data' => null
            ], 500);
        }
    }

    public function getCustomerAppOrdersPaginated(Request $request)
    {
        try {
            // DataTables sends: draw, start, length, search[value], etc.
            $draw = intval($request->get('draw', 1));
            $start = intval($request->get('start', 0));
            $length = intval($request->get('length', 10));
            $search = $request->get('search', []);
            $searchValue = isset($search['value']) ? $search['value'] : '';
            
            // Calculate page number (DataTables uses start/length, we need page/limit)
            $page = $length > 0 ? floor($start / $length) + 1 : 1;
            $limit = $length > 0 ? $length : 10;
            
            $params = [
                'page' => $page,
                'limit' => $limit
            ];
            
            if ($searchValue) {
                $params['search'] = $searchValue;
            }
            
            try {
                $apiResponse = $this->nodeApi->get('/admin/dashboard/customer-app-orders', $params, 60);
            } catch (\Exception $apiException) {
                Log::error('Node API call failed for customer app orders', [
                    'error' => $apiException->getMessage(),
                    'trace' => $apiException->getTraceAsString()
                ]);
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Failed to fetch orders from API: ' . $apiException->getMessage()
                ], 500);
            }
            
            // Check if response is valid
            if (!is_array($apiResponse)) {
                Log::error('Invalid API response format for customer app orders', [
                    'response_type' => gettype($apiResponse),
                    'response' => $apiResponse
                ]);
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Invalid API response format'
                ], 500);
            }
            
            Log::info('Customer app orders API response', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'has_data' => isset($apiResponse['data']),
                'data_count' => isset($apiResponse['data']) && is_array($apiResponse['data']) ? count($apiResponse['data']) : 0,
                'total' => $apiResponse['total'] ?? 0,
                'response_keys' => array_keys($apiResponse)
            ]);
            
            if (isset($apiResponse['status']) && $apiResponse['status'] === 'success') {
                // Format response for DataTables
                $orders = $apiResponse['data'] ?? [];
                $total = isset($apiResponse['total']) ? intval($apiResponse['total']) : 0;
                
                // Ensure orders is an array
                if (!is_array($orders)) {
                    Log::warning('Orders data is not an array', [
                        'type' => gettype($orders),
                        'value' => $orders
                    ]);
                    $orders = [];
                }
                
                // Format data for DataTables
                $formattedData = [];
                
                foreach ($orders as $index => $order) {
                    try {
                        // Handle both object and array formats
                        $orderObj = is_array($order) ? (object)$order : $order;
                        
                        $orderDate = 'N/A';
                        if (isset($orderObj->created_at) || isset($orderObj->date)) {
                            $dateStr = $orderObj->created_at ?? $orderObj->date ?? null;
                            if ($dateStr) {
                                try {
                                    $orderDate = date('Y-m-d', strtotime($dateStr));
                                } catch (\Exception $e) {
                                    $orderDate = 'N/A';
                                }
                            }
                        }
                        
                        $amount = $orderObj->total_amount ?? $orderObj->estim_price ?? $orderObj->amount ?? '0.00';
                        $status = $this->getStatusLabel($orderObj->status ?? 0);
                        $orderId = $orderObj->id ?? 'N/A';
                        
                        $formattedData[] = [
                            'DT_RowIndex' => $start + $index + 1,
                            'id' => $orderId,
                            'order_number' => $orderObj->order_no ?? $orderObj->order_number ?? 'N/A',
                            'customer_id' => $orderObj->customer_id ?? 'N/A',
                            'shop_id' => $orderObj->shop_id ?? 'N/A',
                            'status' => $status,
                            'status_badge' => '<span class="badge badge-' . $this->getStatusColor($orderObj->status ?? 0) . '">' . $status . '</span>',
                            'amount' => '₹' . number_format((float)$amount, 2),
                            'date' => $orderDate,
                            'action' => '<button class="btn btn-sm btn-primary" onclick="viewOrderDetails(' . $orderId . ', \'customer_app\')"><i class="fa fa-eye"></i> View Details</button>'
                        ];
                    } catch (\Exception $orderException) {
                        Log::warning('Error formatting order', [
                            'index' => $index,
                            'error' => $orderException->getMessage(),
                            'order' => $order
                        ]);
                        // Skip this order
                        continue;
                    }
                }
                
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => $total,
                    'recordsFiltered' => $total,
                    'data' => $formattedData
                ]);
            }
            
            // API returned error status
            Log::warning('API returned error status for customer app orders', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'msg' => $apiResponse['msg'] ?? 'No message',
                'response' => $apiResponse
            ]);
            
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $apiResponse['msg'] ?? 'Failed to load orders'
            ]);
        } catch (\Exception $e) {
            Log::error('Customer app orders paginated API error: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->get('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading customer app orders'
            ], 500);
        }
    }
    
    private function getStatusLabel($status)
    {
        $statusMap = [
            1 => 'Scheduled',
            2 => 'Accepted',
            3 => 'In Progress',
            4 => 'Picked Up',
            5 => 'Completed',
            6 => 'Accepted by Other',
            7 => 'Cancelled'
        ];
        return $statusMap[$status] ?? $status ?? 'N/A';
    }
    
    private function getStatusColor($status)
    {
        if ($status === 5) {
            return 'success';
        } else if ($status === 1 || $status === 2 || $status === 3) {
            return 'warning';
        } else if ($status === 7) {
            return 'danger';
        } else if ($status === 6) {
            return 'info';
        }
        return 'secondary';
    }
}
