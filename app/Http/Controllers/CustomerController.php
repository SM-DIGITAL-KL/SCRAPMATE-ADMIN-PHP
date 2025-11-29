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
    public function customers()
    {
        Log::info('🔵 CustomerController::customers called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/customer/list');
        
        Log::info('🔵 Node.js API Response for customers', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ customers: Successfully retrieved data');
        } else {
            Log::error('❌ Node API failed for customers', ['response' => $apiResponse]);
            $data = ['pagename' => 'Customers'];
        }
        
        return view('customers/customers', $data);
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
            // Convert arrays to objects for DataTables compatibility
            $order = collect($apiResponse['data'])->map(function($orderItem) {
                return (object)$orderItem;
            });
            Log::info('✅ view_orders: Successfully retrieved orders', ['count' => $order->count()]);
        } else {
            Log::error('❌ Node API failed for view_orders', ['response' => $apiResponse]);
            $order = collect([]);
        }

        return datatables()->of($order)
        ->addIndexColumn()
        ->editColumn('status',function ($d)
        {
            $full_status = match ($d->status) {
                1 => '<span class="badge rounded-pill bg-warning">Request Pending</span>',
                2 => '<span class="badge rounded-pill bg-warning">Shop Accepted</span>',
                3 => '<span class="badge rounded-pill bg-warning">Assigned Door Step Buyer</span>',
                4 => '<span class="badge rounded-pill bg-success">Completed</span>',
                5 => '<span class="badge rounded-pill bg-danger">Shop Declined</span>',
                6 => '<span class="badge rounded-pill bg-danger">Customer Cancelled</span>',
            };

            return $full_status;
        })
        ->addColumn('callStatus',function ($d)
        {
            if ($d->call_log == 1) {
                $c = '<i class="material-icons text-success fs-3" title="Not Called">phone_forwarded</i>';
            } else {
                $c = '<i class="material-icons text-danger fs-3" title="Called">phone_disabled</i>';
            }

            if ($d->customerCallLog == 1) {
                $d = '<i class="material-icons text-success fs-3" title="Not Called">phone_forwarded</i>';
            } else {
                $d = '<i class="material-icons text-danger fs-3" title="Called">phone_disabled</i>';
            }
            

            return $c.' || '.$d;
            // return 'hai';
        })
        ->editColumn('customerdetails',function ($d)
        {
            $json = json_decode($d->customerdetails, true);
            $return = '<p><b>'.$json['name'].'</b></p>';
            return $return;
        })
        ->editColumn('shopdetails',function ($d)
        {
            $json = json_decode($d->shopdetails, true);
            $return = '<p><a href="'.route('shop_view_by_id', ['id' => $json['shop_id']]).'"><b>'.$json['shop_name'].'</b></a></p>';
            return $return;
            // return 'hai';
        })
        ->editColumn('action',function ($d)
        {
            $details = '<div class="dropdown">
							<button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
								<svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="large_modal('.$d->id.','."'view_order_details'".')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">View Order Details</a>
								<a class="dropdown-item" href="#">Delete</a>
							</div>
						</div>';

            return $details;
        })
        ->rawColumns(['customerdetails' ,'status' ,'shopdetails' ,'action','callStatus'])
        ->make(true);
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
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data['order'] = (object)$apiResponse['data'];
            $data['pagename'] = 'orders details';
            Log::info('✅ view_order_details: Successfully retrieved order data');
            return view('customers/view_order_details', $data);
        } else {
            Log::error('❌ Node API failed for view_order_details', ['response' => $apiResponse]);
            $data = [
                'order' => null,
                'pagename' => 'orders details'
            ];
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
