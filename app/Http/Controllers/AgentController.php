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

use App\Models\ShopImages;
use App\Models\DeliveryBoy;

use App\Services\NodeApiService;

class AgentController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }
    public function agents()
    {
        Log::info('AgentController::agents called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/agent/list');
        
        Log::info('Node.js API Response for agents', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            // Convert shoptype array to collection of objects if it's an array
            if (isset($data['shoptype']) && is_array($data['shoptype'])) {
                $data['shoptype'] = collect($data['shoptype'])->map(function($shoptype) {
                    return (object)$shoptype;
                });
            } elseif (!isset($data['shoptype'])) {
                $data['shoptype'] = collect([]);
            }
            
            // Get shop counts by type from Node.js API
            $shop_counts = [];
            try {
                $shopsResponse = $this->nodeApi->get('/agent/shops');
                if ($shopsResponse['status'] === 'success' && isset($shopsResponse['data'])) {
                    // Count shops by shop_type
                    foreach ($shopsResponse['data'] as $shop) {
                        $shopType = $shop['shop_type'] ?? null;
                        if ($shopType !== null && $shop['del_status'] == 1) {
                            if (!isset($shop_counts[$shopType])) {
                                $shop_counts[$shopType] = 0;
                            }
                            $shop_counts[$shopType]++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch shop counts', ['error' => $e->getMessage()]);
            }
            $data['shop_counts'] = $shop_counts;
            
            return view('agent/agents', $data);
        } else {
            Log::error('Node API failed for agents', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'Vendor List',
                'shoptype' => collect([]),
                'shop_counts' => []
            ];
            return view('agent/agents', $data);
        }
    }
    public function manage_agent(Request $req , $id ='')
    {
        Log::info('🔵 AgentController::manage_agent called', ['method' => $req->method(), 'id' => $id]);
        
        if ($req->isMethod('post')){
            $apiData = [
                'shopname' => $req->post('shopname'),
                'email' => $req->post('email'),
                'password' => $req->post('password'),
                'ownername' => $req->post('ownername'),
                'contact' => $req->post('contact'),
                'address' => $req->post('address')
            ];
            
            Log::info('🔵 manage_agent POST: Calling Node.js API', [
                'user_id' => $req->post('user_id'),
                'apiData' => array_merge($apiData, ['password' => '***'])
            ]);
            
            if ($req->post('user_id') != '') {
                // Update existing agent
                $apiResponse = $this->nodeApi->put('/agent/' . $req->post('user_id'), $apiData);
                Log::info('🔵 manage_agent PUT Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
            } else {
                // Create new agent
                $apiResponse = $this->nodeApi->post('/agent', $apiData);
                Log::info('🔵 manage_agent POST Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
            }
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ manage_agent: Operation successful');
                if ($req->post('user_id') != '') {
                    return Redirect::to('/agents')->with('success','Updated successfully!');
                } else {
                    return Redirect::to('/agents')->with('success','Add User successfully!');
                }
            } else {
                Log::error('❌ manage_agent: Operation failed', ['response' => $apiResponse]);
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Operation failed');
            }
        }
        
        // GET request - get agent data if id provided
        if ($id) {
            Log::info('🔵 manage_agent GET: Calling Node.js API', ['id' => $id]);
            $apiResponse = $this->nodeApi->get('/agent/' . $id);
            Log::info('🔵 manage_agent GET Response', ['status' => $apiResponse['status'] ?? 'unknown', 'hasData' => isset($apiResponse['data'])]);
            
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                $data['shop'] = (object)$apiResponse['data'];
                Log::info('✅ manage_agent: Successfully retrieved agent data');
            } else {
                Log::error('❌ manage_agent: Failed to retrieve agent data', ['response' => $apiResponse]);
                $data['shop'] = null;
            }
        } else {
            $data['shop'] = null;
        }
        
        $data['pagename'] = 'Shop List';
        return view('agent/manage_agent', $data);
    }
    public function view_shops()
    {   
        Log::info('🔵 AgentController::view_shops called', ['shop_type_id' => $_GET['shop_type_id'] ?? null]);
        $shop_type_id = $_GET['shop_type_id'] ?? null;
        $params = [];
        if ($shop_type_id) {
            $params['shop_type_id'] = $shop_type_id;
        }
        
        Log::info('🔵 Calling Node.js API: /agent/shops', ['params' => $params]);
        $apiResponse = $this->nodeApi->get('/agent/shops', $params);
        
        Log::info('🔵 Node.js API Response for view_shops', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for DataTables compatibility
            $users = collect($apiResponse['data'])->map(function($shop) {
                return (object)$shop;
            });
            Log::info('✅ view_shops: Successfully retrieved shops', ['count' => $users->count()]);
        } else {
            Log::error('❌ Node API failed for view_shops', ['response' => $apiResponse]);
            $users = collect([]);
        }
        
        // Capture nodeApi for use in closures
        $nodeApi = $this->nodeApi;
        
        return datatables()->of($users)
        ->addIndexColumn()
        ->editColumn('status',function ($d)
            {
               // Check if status property exists, otherwise use del_status or default to pending
               $status = isset($d->status) ? $d->status : (isset($d->del_status) && $d->del_status == 1 ? 1 : 0);
               
               if ($status == 2) {
                    $details = '<span class="badge light badge-success">Accepted</span> ';
               } else{
                    $details = '<a href="#" onclick="if(confirm(\'Are you sure?\')){window.location.href=\'' . route('shop_status_change', ['id' => $d->id]) . '\'};return false;"><span class="badge light badge-warning">Pending</span></a>';
               }
               return $details;
            })
        ->editColumn('shopname',function ($d)
            {
                if ($d->profile_photo){
                    $image = url('/assets/images/profile/' . $d->profile_photo);
                } else {
                    $image = asset('assets/images/no-img-avatar.png');
                }
                return '<div class="d-flex align-items-center">
						    <a href="' . route('shop_view_by_id', ['id' => $d->id]) . '">
                                <img src="'.$image.'" class="rounded-lg me-2" width="24" alt="">
						        <span class="w-space-no">'.$d->shopname.'</span>
                            </a>
					    </div>';
            })        
        ->editColumn ('shop_type' ,function ($d)
            {
                if ($d->shop_type == 1) {
                    $details = 'Industrial';
                } elseif($d->shop_type == 2) {
                    $details = 'Door Step Buyer ';
                } elseif($d->shop_type == 3){
                    $details = 'Retailer';
                } else{
                    $details = 'Wholesaler';
                }
                return $details;
            })    
        ->addColumn('details',function ($d)
            {
                $details = '<p style="font-size: 12px ;width: 200px"><b>Address : </b><span class="text-wrap">'.$d->address.'</span><br><b>E-mail : </b>'.$d->email.'<br><b>Place : </b>'.$d->place.'</p>';
                return $details;
            })
        ->addColumn('action',function ($d)
            {
                // Use counts from API response (already included in shop data)
                $image_count = isset($d->image_count) ? $d->image_count : 0;
                $delivery_boys = isset($d->delivery_boys_count) ? $d->delivery_boys_count : 0;
                
                $details = '<div class="dropdown">
							<button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
								<svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="large_modal('.$d->id.','."'show_shop_images'".')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">Shop Images ('.$image_count.')</a>
                                <a class="dropdown-item" href="#">Delivery Boys ('.$delivery_boys.')</a>
								<a class="dropdown-item" href="javascript:;" onclick="custom_delete(\'/del_shop/' . $d->id . '\')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm">Delete</a>
							</div>
						</div>';

                return $details;
            })
        ->rawColumns(['action','status','details','shop_type','shopname'])
        ->make(true);
    }
    public function show_shop_images($id='')
    {
        Log::info('🔵 AgentController::show_shop_images called', ['id' => $id]);
        // Shop images are handled by ShopController in Node.js
        $apiResponse = $this->nodeApi->get('/agent/shop-images/' . $id);
        
        Log::info('🔵 show_shop_images Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data['shop_images'] = collect($apiResponse['data']);
            Log::info('✅ show_shop_images: Successfully retrieved shop images');
        } else {
            Log::error('❌ Node API failed for show_shop_images', ['response' => $apiResponse]);
            $data['shop_images'] = collect([]);
        }
        
        return view('agent/show_shop_images', $data);
    }
    
    public function shop_status_change($id)
    {
        Log::info('🔵 AgentController::shop_status_change called', ['id' => $id]);
        $apiResponse = $this->nodeApi->put('/agent/shop-status/' . $id);
        
        Log::info('🔵 shop_status_change Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success') {
            Log::info('✅ shop_status_change: Status changed successfully');
            return Redirect::back()->with('success','Accepted Successfully');
        } else {
            Log::error('❌ shop_status_change: Failed to change status', ['response' => $apiResponse]);
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Failed to change status');
        }
    }
    
    public function del_shop($id)
    {
        Log::info('🔵 AgentController::del_shop called', ['id' => $id]);
        $apiResponse = $this->nodeApi->delete('/agent/shop/' . $id);
        
        Log::info('🔵 del_shop Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success') {
            Log::info('✅ del_shop: Shop deleted successfully');
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            Log::error('❌ del_shop: Failed to delete shop', ['response' => $apiResponse]);
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Data Not Found');
        }
    }

    public function shop_view_by_id($id)
    {
        Log::info('🔵 AgentController::shop_view_by_id called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/agent/shop/' . $id);
        
        Log::info('🔵 shop_view_by_id Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            $data['shop'] = (object)$data['shop'];
            // Convert delivery_boy arrays to objects
            $data['delivery_boy'] = collect($data['delivery_boy'] ?? [])->map(function($delBoy) {
                return (object)$delBoy;
            });
            // Convert category arrays to objects
            $data['category'] = collect($data['category'] ?? [])->map(function($cat) {
                return (object)$cat;
            });
            
            // Fetch products for each category from Node API
            $shopId = $data['shop']->id ?? $id;
            $categoryProducts = [];
            foreach ($data['category'] as $cat) {
                try {
                    $productsResponse = $this->nodeApi->get('/shop_item_list/' . $shopId . '/' . ($cat->id ?? $cat->cat_id ?? ''));
                    if ($productsResponse['status'] === 'success' && isset($productsResponse['data'])) {
                        $categoryProducts[$cat->id ?? $cat->cat_id ?? ''] = collect($productsResponse['data'])->map(function($product) {
                            return (object)$product;
                        });
                    } else {
                        $categoryProducts[$cat->id ?? $cat->cat_id ?? ''] = collect([]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching products for category ' . ($cat->id ?? $cat->cat_id ?? '') . ': ' . $e->getMessage());
                    $categoryProducts[$cat->id ?? $cat->cat_id ?? ''] = collect([]);
                }
            }
            $data['categoryProducts'] = $categoryProducts;
            
            $data['pagename'] = 'Vendor Profile';
            Log::info('✅ shop_view_by_id: Successfully retrieved shop data');
            return view('agent/shop_view_by_id', $data);
        } else {
            Log::error('❌ Node API failed for shop_view_by_id', ['response' => $apiResponse]);
            $data = [
                'shop' => null,
                'delivery_boy' => collect([]),
                'category' => collect([]),
                'pagename' => 'Vendor Profile'
            ];
            return view('agent/shop_view_by_id', $data);
        }
    }
    public function createCategory(Request $req , $id)
    {
        Log::info('🔵 AgentController::createCategory called', ['method' => $req->method(), 'id' => $id]);
        
        if ($req->isMethod('post')) {
            $apiData = [
                'category' => $req->post('category')
            ];
            
            Log::info('🔵 createCategory POST: Calling Node.js API', ['id' => $id, 'apiData' => $apiData]);
            $apiResponse = $this->nodeApi->post('/agent/category/' . $id, $apiData);
            
            Log::info('🔵 createCategory POST Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ createCategory: Category added successfully');
                return Redirect::back()->with('success','Category Added Successfully!');
            } else {
                Log::error('❌ createCategory: Failed to add category', ['response' => $apiResponse]);
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Failed to add category');
            }
        }
        
        // GET request - get categories for form
        Log::info('🔵 createCategory GET: Calling Node.js API', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/agent/categories/' . $id);
        
        Log::info('🔵 createCategory GET Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $categories = collect($apiResponse['data']['categories'] ?? []);
            $added_cat = $apiResponse['data']['added_cat'] ?? [];
            Log::info('✅ createCategory: Successfully retrieved categories', ['count' => $categories->count()]);
        } else {
            Log::error('❌ Node API failed for createCategory form', ['response' => $apiResponse]);
            $categories = collect([]);
            $added_cat = [];
        }
        
        $display = '<div class="row">
                        <div class="col-md-12">
                            <form action="'.url('/createCategory/'.$id).'" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="_token" value="'.csrf_token().'">
                                <input type="hidden" name="shop_id" value="'.$id.'">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Category</label>
                                    <div class="col-sm-9">
                                        <select name="category" class="form-control">';
                                            foreach ($categories as $value) {
                                                $valueObj = (object)$value;
                                                $disabled = '';
                                                if (in_array($valueObj->category_name, $added_cat)) {
                                                    $disabled = 'disabled';
                                                }
                                                $display .= '<option value="'.$valueObj->id.'" '.$disabled.'>'.$valueObj->category_name.'</option>';
                                            }
        $display .= '            </select>
                                    </div>
                                </div></br>
                                <div class="form-group row">
                                    <div class="col-sm-12 text-center">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>';
        echo $display;
    }
    public function createItem(Request $req , $shop_id,$cat_id)
    {
        Log::info('🔵 AgentController::createItem called', ['method' => $req->method(), 'shop_id' => $shop_id, 'cat_id' => $cat_id]);
        
        if ($req->isMethod('post')) {
            $apiData = [
                'item' => $req->post('item'),
                'amount' => $req->post('amount')
            ];
            
            Log::info('🔵 createItem POST: Calling Node.js API', ['shop_id' => $shop_id, 'cat_id' => $cat_id, 'apiData' => $apiData]);
            $apiResponse = $this->nodeApi->post('/agent/item/' . $shop_id . '/' . $cat_id, $apiData);
            
            Log::info('🔵 createItem POST Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ createItem: Item added successfully');
                return Redirect::back()->with('success','Item Added Successfully!');
            } else {
                Log::error('❌ createItem: Failed to add item', ['response' => $apiResponse]);
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Failed to add item');
            }
        }
        
        $display = '<div class="row">
                        <div class="col-md-12">
                            <form action="'.url('/createItem/'.$shop_id.'/'.$cat_id).'" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="_token" value="'.csrf_token().'">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Item Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="item" placeholder="Enter Item Name" class="form-control">
                                    </div>
                                </div></br>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Amount</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="amount" placeholder="Enter Item Amount" class="form-control">
                                    </div>
                                </div></br>
                                <div class="form-group row">
                                    <div class="col-sm-12 text-center">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>';
        echo $display;
    }
    public function view_del_boy($id)
    {
        Log::info('🔵 AgentController::view_del_boy called', ['id' => $id]);
        $apiResponse = $this->nodeApi->get('/agent/delivery-boy/' . $id);
        
        Log::info('🔵 view_del_boy Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data['delivery_boy'] = (object)$apiResponse['data'];
            Log::info('✅ view_del_boy: Successfully retrieved delivery boy data');
        } else {
            Log::error('❌ Node API failed for view_del_boy', ['response' => $apiResponse]);
            $data['delivery_boy'] = null;
        }
        
        $data['pagename'] = 'View Delivery Boy';
        return view('agent/view_del_boy', $data);
    }
    
    public function agents_leads()
    {
        Log::info('🔵 AgentController::agents_leads called');
        $apiResponse = $this->nodeApi->get('/agent/leads');
        
        Log::info('🔵 agents_leads Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ agents_leads: Successfully retrieved data');
        } else {
            Log::error('❌ agents_leads: Failed to retrieve data', ['response' => $apiResponse]);
            $data = ['pagename' => 'Agent List'];
        }
        
        return view('agent/agents_leads', $data);
    }
    
    public function manage_leads()
    {
        Log::info('🔵 AgentController::manage_leads called');
        $data['pagename'] = 'Leads Management';
        return view('agent/manage_leads', $data);
    }
    
    public function agent_report()
    {
        Log::info('🔵 AgentController::agent_report called');
        $apiResponse = $this->nodeApi->get('/agent/report');
        
        Log::info('🔵 agent_report Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ agent_report: Successfully retrieved data');
        } else {
            Log::error('❌ agent_report: Failed to retrieve data', ['response' => $apiResponse]);
            $data = ['pagename' => 'Agent Report'];
        }
        
        return view('agent/agent_report', $data);
    }
    
    public function commission_track()
    {
        Log::info('🔵 AgentController::commission_track called');
        $apiResponse = $this->nodeApi->get('/agent/commission-track');
        
        Log::info('🔵 commission_track Response', ['status' => $apiResponse['status'] ?? 'unknown', 'response' => $apiResponse]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ commission_track: Successfully retrieved data');
        } else {
            Log::error('❌ commission_track: Failed to retrieve data', ['response' => $apiResponse]);
            $data = ['pagename' => 'Agent Commison Traking'];
        }
        
        return view('agent/commission_track', $data);
    }
}
