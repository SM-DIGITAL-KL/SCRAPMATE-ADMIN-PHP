<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use App\Services\NodeApiService;

class CustomerController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }
    public function customers(Request $request)
    {
        // Reduced logging for performance
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if (!empty($search)) {
            $params['search'] = $search;
        }
        
        $apiResponse = $this->nodeApi->get('/admin/customers', $params);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            // Convert users array to collection of objects
            if (isset($data['users']) && is_array($data['users'])) {
                $data['users'] = collect($data['users'])->map(function($user) {
                    return (object)$user;
                });
            } else {
                $data['users'] = collect([]);
            }
            
            $data['pagename'] = 'Customers';
            return view('customers/customers', $data);
        } else {
            Log::error('Node API failed for customers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'Customers',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('customers/customers', $data);
        }
    }
    
    public function orders()
    {
        Log::info('🔵 CustomerController::orders called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/customer/orders');
        
        Log::info('🔵 Node.js API Response for orders', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ orders: Successfully retrieved data');
        } else {
            Log::error('❌ Node API failed for orders', ['response' => $apiResponse]);
            $data = ['pagename' => 'orders'];
        }
        
        return view('customers/orders', $data);
    }
    public function view_customers()
    {
        Log::info('🔵 CustomerController::view_customers called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/customer/view-customers');
        
        Log::info('🔵 Node.js API Response for view_customers', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for DataTables compatibility
            $customers = collect($apiResponse['data'])->map(function($customer) {
                return (object)$customer;
            });
            Log::info('✅ view_customers: Successfully retrieved customers', ['count' => $customers->count()]);
        } else {
            Log::error('❌ Node API failed for view_customers', ['response' => $apiResponse]);
            $customers = collect([]);
        }
        
        return datatables()->of($customers)
        ->addIndexColumn()
        ->editColumn('name',function ($d)
        {
            if ($d->profile_photo){
                $image = url('/assets/images/profile/' . $d->profile_photo);
            } else {
                $image = asset('assets/images/no-img-avatar.png');
            }
            return '<div class="d-flex align-items-center">
                        <img src="'.$image.'" class="rounded-lg me-2" width="24" alt="">
                            <span class="w-space-no">'.$d->name.'</span>
                    </div>';
        })  
        ->addColumn('details',function ($d)
            {
                $details = '<p style="font-size: 12px ;width: 200px"><b>Address : </b><span class="text-wrap">'.$d->address.'</span><br><b>E-mail : </b>'.$d->email.'<br><b>Place : </b>'.$d->place.'</p>';
                return $details;
            })
        ->editColumn('action',function ($d)
        {
            $details = '<div class="dropdown">
							<button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
								<svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="large_modal('.$d->id.','."'show_recent_orders'".')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">Recent Orders</a>
								<a class="dropdown-item" href="javascript:;" onclick="custom_delete(\'/del_customer/' . $d->id . '\')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm">Delete</a>
							</div>
						</div>';

            return $details;
        })
        ->rawColumns(['name','action','details'])
        ->make(true);
    }
    public function view_orders()
    {
        try {
            Log::info('🔵 CustomerController::view_orders called', ['status_id' => $_GET['status_id'] ?? null]);
            $status_id = $_GET['status_id'] ?? null;
            $params = [];
            if ($status_id) {
                $params['status_id'] = $status_id;
            }
            
            Log::info('🔵 Calling Node.js API: /customer/view-orders', ['params' => $params]);
            $apiResponse = $this->nodeApi->get('/customer/view-orders', $params);
            
            Log::info('🔵 Node.js API Response for view_orders', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'hasData' => isset($apiResponse['data']),
                'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
                'response' => $apiResponse
            ]);
            
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                // Handle case where data might be 'empty data' string
                if ($apiResponse['data'] === 'empty data' || (is_string($apiResponse['data']) && $apiResponse['data'] === 'empty data')) {
                    $order = collect([]);
                } else {
                    // Convert arrays to objects for DataTables compatibility
                    $order = collect($apiResponse['data'])->map(function($orderItem) {
                        $item = (object)$orderItem;
                        
                        // Log sample data structure for debugging (first item only)
                        static $logged = false;
                        if (!$logged && isset($item->customerdetails)) {
                            Log::info('🔍 Sample order customerdetails structure', [
                                'type' => gettype($item->customerdetails),
                                'value' => is_string($item->customerdetails) ? substr($item->customerdetails, 0, 200) : $item->customerdetails,
                                'is_object' => is_object($item->customerdetails),
                                'is_array' => is_array($item->customerdetails)
                            ]);
                            $logged = true;
                        }
                        
                        return $item;
                    });
                }
                Log::info('✅ view_orders: Successfully retrieved orders', ['count' => $order->count()]);
            } else {
                Log::error('❌ Node API failed for view_orders', ['response' => $apiResponse]);
                $order = collect([]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception in view_orders: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            $order = collect([]);
        }

        try {
            return datatables()->of($order)
        ->addIndexColumn()
        ->editColumn('order_number',function ($order)
        {
            return isset($order->order_number) ? $order->order_number : (isset($order->id) ? '#' . $order->id : 'N/A');
        })
        ->editColumn('status',function ($order)
        {
            $status = isset($order->status) ? (int)$order->status : 0;
            $full_status = match ($status) {
                1 => '<span class="badge rounded-pill bg-warning">Request Pending</span>',
                2 => '<span class="badge rounded-pill bg-warning">Shop Accepted</span>',
                3 => '<span class="badge rounded-pill bg-warning">Assigned Door Step Buyer</span>',
                4 => '<span class="badge rounded-pill bg-success">Completed</span>',
                5 => '<span class="badge rounded-pill bg-danger">Shop Declined</span>',
                6 => '<span class="badge rounded-pill bg-danger">Customer Cancelled</span>',
                default => '<span class="badge rounded-pill bg-secondary">Unknown</span>',
            };

            return $full_status;
        })
        ->addColumn('callStatus',function ($order)
        {
            // Handle call_log (shop call log)
            $shopCallLog = isset($order->call_log) ? $order->call_log : 0;
            if ($shopCallLog == 1) {
                $shopIcon = '<i class="material-icons text-success fs-3" title="Not Called">phone_forwarded</i>';
            } else {
                $shopIcon = '<i class="material-icons text-danger fs-3" title="Called">phone_disabled</i>';
            }

            // Handle customerCallLog (customer call log) - check if property exists
            $customerCallLogValue = isset($order->customerCallLog) ? $order->customerCallLog : (isset($order->customer_call_log) ? $order->customer_call_log : 0);
            if ($customerCallLogValue == 1) {
                $customerIcon = '<i class="material-icons text-success fs-3" title="Not Called">phone_forwarded</i>';
            } else {
                $customerIcon = '<i class="material-icons text-danger fs-3" title="Called">phone_disabled</i>';
            }
            

            return $shopIcon.' || '.$customerIcon;
        })
        ->editColumn('customerdetails',function ($order)
        {
            if (!isset($order->customerdetails) || empty($order->customerdetails)) {
                return '<p><b>N/A</b></p>';
            }
            
            try {
                $name = 'N/A';
                
                // Handle different data formats
                if (is_string($order->customerdetails)) {
                    // Try to decode JSON
                    $json = json_decode($order->customerdetails, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        // Successfully decoded JSON
                        $name = $json['name'] ?? ($json['customer_name'] ?? 'N/A');
                    } else {
                        // Try decoding with stripslashes if escaped
                        $cleaned = stripslashes($order->customerdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $name = $json['name'] ?? ($json['customer_name'] ?? 'N/A');
                        } else {
                            // Try regex extraction as last resort
                            if (preg_match('/"name"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $order->customerdetails, $matches)) {
                                $name = stripcslashes($matches[1]);
                            } elseif (preg_match('/name["\']?\s*:\s*["\']([^"\']+)["\']/', $order->customerdetails, $matches)) {
                                $name = $matches[1];
                            }
                        }
                    }
                } elseif (is_object($order->customerdetails)) {
                    $name = $order->customerdetails->name ?? ($order->customerdetails->customer_name ?? 'N/A');
                } elseif (is_array($order->customerdetails)) {
                    $name = $order->customerdetails['name'] ?? ($order->customerdetails['customer_name'] ?? 'N/A');
                }
                
                // Fallback: if still N/A and we have the raw value, try one more time
                if ($name === 'N/A' && is_string($order->customerdetails) && strlen($order->customerdetails) > 0) {
                    // Maybe it's just a plain string name?
                    if (strlen($order->customerdetails) < 100 && !str_contains($order->customerdetails, '{')) {
                        $name = $order->customerdetails;
                    }
                }
                
                return '<p><b>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</b></p>';
            } catch (\Exception $e) {
                Log::error('Error parsing customerdetails: ' . $e->getMessage(), [
                    'raw_value' => is_string($order->customerdetails) ? substr($order->customerdetails, 0, 200) : gettype($order->customerdetails)
                ]);
                return '<p><b>N/A</b></p>';
            }
        })
        ->editColumn('shopdetails',function ($order)
        {
            if (!isset($order->shopdetails) || empty($order->shopdetails)) {
                return '<p><b>N/A</b></p>';
            }
            
            try {
                $shopName = 'N/A';
                $shopId = null;
                
                // Handle different data formats
                if (is_string($order->shopdetails)) {
                    // Try to decode JSON
                    $json = json_decode($order->shopdetails, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        // Successfully decoded JSON
                        $shopId = $json['shop_id'] ?? ($json['id'] ?? null);
                        $shopName = $json['shopname'] ?? ($json['shop_name'] ?? ($json['name'] ?? 'N/A'));
                    } else {
                        // Try decoding with stripslashes if escaped
                        $cleaned = stripslashes($order->shopdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $shopId = $json['shop_id'] ?? ($json['id'] ?? null);
                            $shopName = $json['shopname'] ?? ($json['shop_name'] ?? ($json['name'] ?? 'N/A'));
                        } else {
                            // Try regex extraction as last resort
                            if (preg_match('/"shopname"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $order->shopdetails, $matches)) {
                                $shopName = stripcslashes($matches[1]);
                            } elseif (preg_match('/"shop_name"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $order->shopdetails, $matches)) {
                                $shopName = stripcslashes($matches[1]);
                            } elseif (preg_match('/shopname["\']?\s*:\s*["\']([^"\']+)["\']/', $order->shopdetails, $matches)) {
                                $shopName = $matches[1];
                            }
                            
                            if (preg_match('/"shop_id"\s*:\s*"?(\d+)"?/', $order->shopdetails, $idMatches)) {
                                $shopId = $idMatches[1];
                            } elseif (preg_match('/"id"\s*:\s*"?(\d+)"?/', $order->shopdetails, $idMatches)) {
                                $shopId = $idMatches[1];
                            }
                        }
                    }
                } elseif (is_object($order->shopdetails)) {
                    $shopId = $order->shopdetails->shop_id ?? ($order->shopdetails->id ?? null);
                    $shopName = $order->shopdetails->shopname ?? ($order->shopdetails->shop_name ?? ($order->shopdetails->name ?? 'N/A'));
                } elseif (is_array($order->shopdetails)) {
                    $shopId = $order->shopdetails['shop_id'] ?? ($order->shopdetails['id'] ?? null);
                    $shopName = $order->shopdetails['shopname'] ?? ($order->shopdetails['shop_name'] ?? ($order->shopdetails['name'] ?? 'N/A'));
                }
                
                // Fallback: if still N/A and we have the raw value, try one more time
                if ($shopName === 'N/A' && is_string($order->shopdetails) && strlen($order->shopdetails) > 0) {
                    // Maybe it's just a plain string name?
                    if (strlen($order->shopdetails) < 100 && !str_contains($order->shopdetails, '{')) {
                        $shopName = $order->shopdetails;
                    }
                }
                
                if ($shopId) {
                    return '<p><a href="'.route('shop_view_by_id', ['id' => $shopId]).'"><b>'.htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8').'</b></a></p>';
                }
                return '<p><b>'.htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8').'</b></p>';
            } catch (\Exception $e) {
                Log::error('Error parsing shopdetails: ' . $e->getMessage(), [
                    'raw_value' => is_string($order->shopdetails) ? substr($order->shopdetails, 0, 200) : gettype($order->shopdetails)
                ]);
                return '<p><b>N/A</b></p>';
            }
        })
        ->editColumn('date',function ($order)
        {
            if (isset($order->date)) {
                return $order->date;
            } elseif (isset($order->created_at)) {
                return date('d-m-Y', strtotime($order->created_at));
            } elseif (isset($order->order_date)) {
                return date('d-m-Y', strtotime($order->order_date));
            }
            return 'N/A';
        })
        ->editColumn('action',function ($order)
        {
            $orderId = isset($order->id) ? $order->id : '0';
            $details = '<div class="dropdown">
							<button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
								<svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="large_modal('.$orderId.','."'view_order_details'".')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">View Order Details</a>
								<a class="dropdown-item" href="#">Delete</a>
							</div>
						</div>';

            return $details;
        })
        ->rawColumns(['customerdetails' ,'status' ,'shopdetails' ,'action','callStatus'])
        ->make(true);
        } catch (\Exception $e) {
            Log::error('❌ DataTables error in view_orders: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            // Return empty DataTables response on error
            return datatables()->of(collect([]))
                ->addIndexColumn()
                ->make(true);
        }
    }
    public function view_order_details($id)
    {
        Log::info('🔵 CustomerController::view_order_details called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/customer/order/' . $id);
        
        Log::info('🔵 view_order_details Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        $data = [
            'order' => null,
            'deliveryBoy' => null,
            'pagename' => 'orders details'
        ];
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $order = (object)$apiResponse['data'];
            $data['order'] = $order;
            
            // Parse customerdetails if it's a string
            if (isset($order->customerdetails) && is_string($order->customerdetails)) {
                try {
                    $json = json_decode($order->customerdetails, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $order->customerdetails = (object)$json;
                    } else {
                        $cleaned = stripslashes($order->customerdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $order->customerdetails = (object)$json;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing customerdetails: ' . $e->getMessage());
                }
            }
            
            // Parse shopdetails if it's a string
            if (isset($order->shopdetails) && is_string($order->shopdetails)) {
                try {
                    $json = json_decode($order->shopdetails, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $order->shopdetails = (object)$json;
                    } else {
                        $cleaned = stripslashes($order->shopdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $order->shopdetails = (object)$json;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing shopdetails: ' . $e->getMessage());
                }
            }
            
            // Fetch delivery boy details if delivery boy ID exists
            $delvBoyId = $order->delv_boy_id ?? $order->delv_id ?? null;
            if ($delvBoyId) {
                try {
                    $delvBoyResponse = $this->nodeApi->get('/agent/delivery-boy/' . $delvBoyId);
                    if ($delvBoyResponse['status'] === 'success' && isset($delvBoyResponse['data'])) {
                        $data['deliveryBoy'] = (object)$delvBoyResponse['data'];
                        Log::info('✅ view_order_details: Successfully retrieved delivery boy data');
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching delivery boy: ' . $e->getMessage());
                }
            }
            
            Log::info('✅ view_order_details: Successfully retrieved order data');
            return view('customers/view_order_details', $data);
        } else {
            Log::error('❌ Node API failed for view_order_details', ['response' => $apiResponse]);
            return view('customers/view_order_details', $data);
        }
    }
    
    public function del_customer($id)
    {
        Log::info('🔵 CustomerController::del_customer called', ['id' => $id]);
        $apiResponse = $this->nodeApi->delete('/customer/' . $id);
        
        Log::info('🔵 del_customer Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success') {
            Log::info('✅ del_customer: Customer deleted successfully');
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            Log::error('❌ del_customer: Failed to delete customer', ['response' => $apiResponse]);
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Data Not Found');
        }
    }

    public function show_recent_orders($id = '')
    {
        Log::info('🔵 CustomerController::show_recent_orders called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/customer/recent-orders/' . $id);
        
        Log::info('🔵 show_recent_orders Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data']) && !empty($apiResponse['data'])) {
            $orders = collect($apiResponse['data'])->map(function($order) {
                return (object)$order;
            });
            Log::info('✅ show_recent_orders: Successfully retrieved recent orders', ['count' => $orders->count()]);
            
            $display = '<div class="d-flex align-items-center">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Order Id</th>  
                                        <th>Order Date</th>
                                        <th>Shop Name</th>
                                        <th>Status</th> 
                                    </tr>';
            foreach ($orders as $order) {
                $display .= '<tr>
                                    <td>'.$order->order_number.'</td>
                                    <td>'.date('d-m-Y', strtotime($order->created_at)).'</td>
                                    <td>'.($order->shop_name ?? 'N/A').'</td>
                                    <td>';
                if ($order->status == 1) { 
                    $display .= 'Order Placed'; 
                } elseif ($order->status == 2) { 
                    $display .= 'Shop Accepted'; 
                } elseif ($order->status == 3) { 
                    $display .= 'Delivery Boy Assigned'; 
                } else { 
                    $display .= 'Completed'; 
                } 
                $display .= '</td>
                                </tr>';
            }
            $display .= '</table>
                        </div>';
        } else {
            Log::info('⚠️ show_recent_orders: No orders found');
            $display = '<h4 class="text-center text-danger">No Orders Found</h4>';
        }

        echo $display;
    }



}

                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        // Successfully decoded JSON
                        $shopId = $json['shop_id'] ?? ($json['id'] ?? null);
                        $shopName = $json['shopname'] ?? ($json['shop_name'] ?? ($json['name'] ?? 'N/A'));
                    } else {
                        // Try decoding with stripslashes if escaped
                        $cleaned = stripslashes($order->shopdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $shopId = $json['shop_id'] ?? ($json['id'] ?? null);
                            $shopName = $json['shopname'] ?? ($json['shop_name'] ?? ($json['name'] ?? 'N/A'));
                        } else {
                            // Try regex extraction as last resort
                            if (preg_match('/"shopname"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $order->shopdetails, $matches)) {
                                $shopName = stripcslashes($matches[1]);
                            } elseif (preg_match('/"shop_name"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $order->shopdetails, $matches)) {
                                $shopName = stripcslashes($matches[1]);
                            } elseif (preg_match('/shopname["\']?\s*:\s*["\']([^"\']+)["\']/', $order->shopdetails, $matches)) {
                                $shopName = $matches[1];
                            }
                            
                            if (preg_match('/"shop_id"\s*:\s*"?(\d+)"?/', $order->shopdetails, $idMatches)) {
                                $shopId = $idMatches[1];
                            } elseif (preg_match('/"id"\s*:\s*"?(\d+)"?/', $order->shopdetails, $idMatches)) {
                                $shopId = $idMatches[1];
                            }
                        }
                    }
                } elseif (is_object($order->shopdetails)) {
                    $shopId = $order->shopdetails->shop_id ?? ($order->shopdetails->id ?? null);
                    $shopName = $order->shopdetails->shopname ?? ($order->shopdetails->shop_name ?? ($order->shopdetails->name ?? 'N/A'));
                } elseif (is_array($order->shopdetails)) {
                    $shopId = $order->shopdetails['shop_id'] ?? ($order->shopdetails['id'] ?? null);
                    $shopName = $order->shopdetails['shopname'] ?? ($order->shopdetails['shop_name'] ?? ($order->shopdetails['name'] ?? 'N/A'));
                }
                
                // Fallback: if still N/A and we have the raw value, try one more time
                if ($shopName === 'N/A' && is_string($order->shopdetails) && strlen($order->shopdetails) > 0) {
                    // Maybe it's just a plain string name?
                    if (strlen($order->shopdetails) < 100 && !str_contains($order->shopdetails, '{')) {
                        $shopName = $order->shopdetails;
                    }
                }
                
                if ($shopId) {
                    return '<p><a href="'.route('shop_view_by_id', ['id' => $shopId]).'"><b>'.htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8').'</b></a></p>';
                }
                return '<p><b>'.htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8').'</b></p>';
            } catch (\Exception $e) {
                Log::error('Error parsing shopdetails: ' . $e->getMessage(), [
                    'raw_value' => is_string($order->shopdetails) ? substr($order->shopdetails, 0, 200) : gettype($order->shopdetails)
                ]);
                return '<p><b>N/A</b></p>';
            }
        })
        ->editColumn('date',function ($order)
        {
            if (isset($order->date)) {
                return $order->date;
            } elseif (isset($order->created_at)) {
                return date('d-m-Y', strtotime($order->created_at));
            } elseif (isset($order->order_date)) {
                return date('d-m-Y', strtotime($order->order_date));
            }
            return 'N/A';
        })
        ->editColumn('action',function ($order)
        {
            $orderId = isset($order->id) ? $order->id : '0';
            $details = '<div class="dropdown">
							<button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
								<svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="large_modal('.$orderId.','."'view_order_details'".')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">View Order Details</a>
								<a class="dropdown-item" href="#">Delete</a>
							</div>
						</div>';

            return $details;
        })
        ->rawColumns(['customerdetails' ,'status' ,'shopdetails' ,'action','callStatus'])
        ->make(true);
        } catch (\Exception $e) {
            Log::error('❌ DataTables error in view_orders: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            // Return empty DataTables response on error
            return datatables()->of(collect([]))
                ->addIndexColumn()
                ->make(true);
        }
    }
    public function view_order_details($id)
    {
        Log::info('🔵 CustomerController::view_order_details called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/customer/order/' . $id);
        
        Log::info('🔵 view_order_details Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        $data = [
            'order' => null,
            'deliveryBoy' => null,
            'pagename' => 'orders details'
        ];
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $order = (object)$apiResponse['data'];
            $data['order'] = $order;
            
            // Parse customerdetails if it's a string
            if (isset($order->customerdetails) && is_string($order->customerdetails)) {
                try {
                    $json = json_decode($order->customerdetails, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $order->customerdetails = (object)$json;
                    } else {
                        $cleaned = stripslashes($order->customerdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $order->customerdetails = (object)$json;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing customerdetails: ' . $e->getMessage());
                }
            }
            
            // Parse shopdetails if it's a string
            if (isset($order->shopdetails) && is_string($order->shopdetails)) {
                try {
                    $json = json_decode($order->shopdetails, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $order->shopdetails = (object)$json;
                    } else {
                        $cleaned = stripslashes($order->shopdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $order->shopdetails = (object)$json;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing shopdetails: ' . $e->getMessage());
                }
            }
            
            // Fetch delivery boy details if delivery boy ID exists
            $delvBoyId = $order->delv_boy_id ?? $order->delv_id ?? null;
            if ($delvBoyId) {
                try {
                    $delvBoyResponse = $this->nodeApi->get('/agent/delivery-boy/' . $delvBoyId);
                    if ($delvBoyResponse['status'] === 'success' && isset($delvBoyResponse['data'])) {
                        $data['deliveryBoy'] = (object)$delvBoyResponse['data'];
                        Log::info('✅ view_order_details: Successfully retrieved delivery boy data');
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching delivery boy: ' . $e->getMessage());
                }
            }
            
            Log::info('✅ view_order_details: Successfully retrieved order data');
            return view('customers/view_order_details', $data);
        } else {
            Log::error('❌ Node API failed for view_order_details', ['response' => $apiResponse]);
            return view('customers/view_order_details', $data);
        }
    }
    
    public function del_customer($id)
    {
        Log::info('🔵 CustomerController::del_customer called', ['id' => $id]);
        $apiResponse = $this->nodeApi->delete('/customer/' . $id);
        
        Log::info('🔵 del_customer Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success') {
            Log::info('✅ del_customer: Customer deleted successfully');
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            Log::error('❌ del_customer: Failed to delete customer', ['response' => $apiResponse]);
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Data Not Found');
        }
    }

    public function show_recent_orders($id = '')
    {
        Log::info('🔵 CustomerController::show_recent_orders called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/customer/recent-orders/' . $id);
        
        Log::info('🔵 show_recent_orders Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data']) && !empty($apiResponse['data'])) {
            $orders = collect($apiResponse['data'])->map(function($order) {
                return (object)$order;
            });
            Log::info('✅ show_recent_orders: Successfully retrieved recent orders', ['count' => $orders->count()]);
            
            $display = '<div class="d-flex align-items-center">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Order Id</th>  
                                        <th>Order Date</th>
                                        <th>Shop Name</th>
                                        <th>Status</th> 
                                    </tr>';
            foreach ($orders as $order) {
                $display .= '<tr>
                                    <td>'.$order->order_number.'</td>
                                    <td>'.date('d-m-Y', strtotime($order->created_at)).'</td>
                                    <td>'.($order->shop_name ?? 'N/A').'</td>
                                    <td>';
                if ($order->status == 1) { 
                    $display .= 'Order Placed'; 
                } elseif ($order->status == 2) { 
                    $display .= 'Shop Accepted'; 
                } elseif ($order->status == 3) { 
                    $display .= 'Delivery Boy Assigned'; 
                } else { 
                    $display .= 'Completed'; 
                } 
                $display .= '</td>
                                </tr>';
            }
            $display .= '</table>
                        </div>';
        } else {
            Log::info('⚠️ show_recent_orders: No orders found');
            $display = '<h4 class="text-center text-danger">No Orders Found</h4>';
        }

        echo $display;
    }



}
