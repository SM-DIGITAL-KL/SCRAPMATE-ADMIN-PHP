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

class AccountsController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function subPackages()
    {
        Log::info('🔵 AccountsController::subPackages called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/accounts/sub-packages');
        
        Log::info('🔵 Node.js API Response for subPackages', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for Blade template compatibility
            $packages = collect($apiResponse['data'])
                ->where('status', '!=', '3')
                ->map(function($package) {
                    return (object)$package;
                })
                ->values();
            $data['packages'] = $packages;
            Log::info('✅ subPackages: Successfully retrieved packages', ['count' => $packages->count()]);
        } else {
            Log::error('❌ Node API failed for subPackages', ['response' => $apiResponse]);
            $data['packages'] = collect([]);
        }
        
        $data['pagename'] = 'Subscription Packages List';
        return view('accounts/subPackages', $data);
    }
    
    public function createSubPackage(Request $request)
    {
        if ($request->isMethod('post')) {
            $type = $request->post('type');
            
            // Check if free package already exists
            if ($type == 1) {
                $packagesResponse = $this->nodeApi->get('/accounts/sub-packages');
                if ($packagesResponse['status'] === 'success' && isset($packagesResponse['data'])) {
                    $freePackages = collect($packagesResponse['data'])->where('type', 1)->count();
                    if ($freePackages > 0) {
                        return redirect()->route('subPackages.index')->with('error', 'Free Package Already Exists');
                    }
                }
            }
            
            $apiData = [
                'name' => $request->post('name'),
                'displayname' => $request->post('displayname'),
                'type' => $request->post('type'),
                'orders' => $request->post('order'),
                'price' => $request->post('price'),
                'duration' => $request->post('duration')
            ];
            
            $apiResponse = $this->nodeApi->post('/accounts/sub-package', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return redirect()->route('subPackages.index')->with('success', 'Package Added Successfully');
            } else {
                return redirect()->route('subPackages.index')->with('error', $apiResponse['msg'] ?? 'Failed to add package');
            }
        }
        $data['pagename'] = 'Add Subscription Packages ';
        return view('accounts/createSubPackage', $data);
    }

    public function editSubPackage(Request $request ,$id)
    {
        if ($request->isMethod('post')) {
            $apiData = [
                'name' => $request->post('name'),
                'displayname' => $request->post('displayname'),
                'type' => $request->post('type'),
                'orders' => $request->post('order'),
                'price' => $request->post('price'),
                'duration' => $request->post('duration')
            ];
            
            $apiResponse = $this->nodeApi->put('/accounts/sub-package/' . $id, $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return redirect()->route('subPackages.index')->with('success', 'Package Updated Successfully');
            } else {
                return redirect()->route('subPackages.index')->with('error', $apiResponse['msg'] ?? 'Failed to update package');
            }
        }
        
        $apiResponse = $this->nodeApi->get('/accounts/sub-package/' . $id);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data['package'] = (object)$apiResponse['data'];
        } else {
            Log::error('Node API failed for editSubPackage', ['response' => $apiResponse]);
            $data['package'] = null;
        }
        
        $data['pagename'] = 'Edit Subscription Packages ';
        return view('accounts/editSubPackage', $data);
    }
    public function delSubPackage($id)
    {
        $apiResponse = $this->nodeApi->delete('/accounts/sub-package/' . $id);
        
        if ($apiResponse['status'] === 'success') {
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Data Not Found');
        }
    }

    public function updateSubPackageStatus(Request $request)
    {
        try {
            $apiData = [
                'planId' => $request->planId
            ];
            
            $apiResponse = $this->nodeApi->put('/accounts/sub-package-status', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => $apiResponse['msg'] ?? 'Failed to update status']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Show pending bulk buy orders management page
     */
    public function pendingBulkBuyOrders()
    {
        Log::info('🔵 AccountsController::pendingBulkBuyOrders called');
        $data['pagename'] = 'Pending Bulk Buy Orders';
        return view('accounts/pendingBulkBuyOrders', $data);
    }

    /**
     * Get pending bulk buy orders data for DataTable
     */
    public function viewPendingBulkBuyOrders()
    {
        Log::info('🔵 AccountsController::viewPendingBulkBuyOrders called');
        
        $apiResponse = $this->nodeApi->get('/accounts/pending-bulk-buy-orders');
        
        Log::info('🔵 Node.js API Response for viewPendingBulkBuyOrders', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $orders = collect($apiResponse['data'])->map(function($order) {
                return (object)$order;
            });
            Log::info('✅ viewPendingBulkBuyOrders: Successfully retrieved orders', ['count' => $orders->count()]);
        } else {
            Log::error('❌ Node API failed for viewPendingBulkBuyOrders', ['response' => $apiResponse]);
            $orders = collect([]);
        }
        
        return datatables()->of($orders)
            ->addIndexColumn()
            ->editColumn('user_name', function ($d) {
                $userName = $d->shopname ?? $d->username ?? 'N/A';
                return $userName;
            })
            ->editColumn('order_details', function ($d) {
                $quantity = isset($d->quantity) ? number_format($d->quantity, 0) . ' kg' : 'N/A';
                $location = $d->location ?? 'N/A';
                $scrapType = $d->scrap_type ?? 'N/A';
                $distance = isset($d->preferred_distance) ? $d->preferred_distance . ' km' : 'N/A';
                
                // Parse subcategories if available
                $subcategories = '';
                if (isset($d->subcategories) && !empty($d->subcategories)) {
                    $subs = is_string($d->subcategories) ? json_decode($d->subcategories, true) : $d->subcategories;
                    if (is_array($subs) && count($subs) > 0) {
                        $subNames = array_map(function($sub) {
                            return $sub['subcategory_name'] ?? 'N/A';
                        }, array_slice($subs, 0, 3)); // Show first 3
                        $subcategories = '<br><strong>Subcategories:</strong> ' . implode(', ', $subNames);
                        if (count($subs) > 3) {
                            $subcategories .= ' (+' . (count($subs) - 3) . ' more)';
                        }
                    }
                }
                
                return '<strong>Quantity:</strong> ' . $quantity . '<br>' .
                       '<strong>Location:</strong> ' . $location . '<br>' .
                       '<strong>Type:</strong> ' . $scrapType . '<br>' .
                       '<strong>Distance:</strong> ' . $distance . $subcategories;
            })
            ->editColumn('created_at', function ($d) {
                return isset($d->created_at) ? date('Y-m-d H:i:s', strtotime($d->created_at)) : 'N/A';
            })
            ->editColumn('payment_info', function ($d) {
                $amount = isset($d->payment_amount) ? '₹' . number_format($d->payment_amount, 2) : 'N/A';
                $txId = $d->transaction_id ?? 'N/A';
                $paymentStatus = $d->payment_status ?? 'pending';
                $statusBadge = $paymentStatus === 'approved' ? 'badge-success' : 
                              ($paymentStatus === 'rejected' ? 'badge-danger' : 'badge-warning');
                return '<strong>Amount:</strong> ' . $amount . '<br>' .
                       '<strong>Transaction ID:</strong> ' . $txId . '<br>' .
                       '<span class="badge ' . $statusBadge . '">' . ucfirst($paymentStatus) . '</span>';
            })
            ->editColumn('status', function ($d) {
                $status = $d->status ?? 'pending_payment';
                $badgeClass = $status === 'payment_approved' ? 'badge-success' : 
                             ($status === 'submitted' ? 'badge-info' : 'badge-warning');
                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('action', function ($d) {
                $orderId = $d->id;
                $paymentStatus = $d->payment_status ?? 'pending';
                
                $actions = '';
                
                // Only show approve/reject if payment is pending
                if ($paymentStatus === 'pending') {
                    $actions .= '<button onclick="approveOrder(\'' . $orderId . '\')" class="btn btn-success btn-sm" title="Approve"><i class="fas fa-check"></i> Approve</button> ';
                    $actions .= '<button onclick="rejectOrder(\'' . $orderId . '\')" class="btn btn-danger btn-sm" title="Reject"><i class="fas fa-times"></i> Reject</button>';
                } else {
                    $actions .= '<span class="badge badge-' . ($paymentStatus === 'approved' ? 'success' : 'danger') . '">' . ucfirst($paymentStatus) . '</span>';
                }
                
                return $actions;
            })
            ->rawColumns(['user_name', 'order_details', 'payment_info', 'status', 'action'])
            ->make(true);
    }

    /**
     * Update pending bulk buy order approval status
     */
    public function updatePendingBulkBuyOrderApproval(Request $request)
    {
        try {
            $apiData = [
                'order_id' => $request->order_id,
                'action' => $request->action,
                'notes' => $request->notes ?? null
            ];
            
            $apiResponse = $this->nodeApi->post('/accounts/pending-bulk-buy-order-approval', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'Order ' . $request->action . 'd successfully']);
            } else {
                return response()->json(['success' => false, 'message' => $apiResponse['msg'] ?? 'Failed to update order']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function subcribersList()
    {
        Log::info('🔵 AccountsController::subcribersList called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/accounts/subscribers');
        
        Log::info('🔵 Node.js API Response for subcribersList', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            Log::info('✅ subcribersList: Successfully retrieved data');
        } else {
            Log::error('❌ Node API failed for subcribersList', ['response' => $apiResponse]);
            $data = ['pagename' => 'Subcribers List'];
        }
        
        return view('accounts/subcribersList', $data);
    }

    public function view_subcribersList()
    {
        Log::info('🔵 AccountsController::view_subcribersList called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/accounts/view-subscribers');
        
        Log::info('🔵 Node.js API Response for view_subcribersList', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for DataTables compatibility
            $users = collect($apiResponse['data'])->map(function($invoice) {
                return (object)$invoice;
            });
            Log::info('✅ view_subcribersList: Successfully retrieved invoices', ['count' => $users->count()]);
        } else {
            Log::error('❌ Node API failed for view_subcribersList', ['response' => $apiResponse]);
            $users = collect([]);
        }
        
        return datatables()->of($users)
        ->addIndexColumn()
        ->editColumn('user_name',function ($d)
        {
            // Shop name is already included in the response from Node.js (joined query)
            return $d->shopname ?? '';
        })
        ->editColumn('name',function ($d)
        {
            $name = $d->name . '<small>(' . $d->displayname . ')</small><br><span class="text-success">' . $d->type . '</span>';
            return $name;
        })
        ->addColumn('period',function ($d)
        {
            // Format dates to ensure Y-m-d format
            $fromDate = $d->from_date ? date('Y-m-d', strtotime($d->from_date)) : '';
            $toDate = $d->to_date ? date('Y-m-d', strtotime($d->to_date)) : '';
            
            if ($fromDate && $toDate) {
                $period = '<span class="badge badge-success rounded-pill">' . $fromDate . ' to ' . $toDate . '</span>';
            } else {
                $period = '<span class="badge badge-warning rounded-pill">No period set</span>';
            }
            return $period;
        })
        // ->addColumn('action',function ($d)
        //     {
        //         $details = '<a href="javascript:;" onclick="large_modal('.$d->id.','."'manage_users'".')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary btn-sm" title="Edit User"><i class="fas fa-pencil-alt"></i></a>';

        //         $details .= '&nbsp;<a href="javascript:;" onclick="basic_modal('.$d->user_id.','."'user_password_reset'".')"  data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-success btn-sm" title="Password Reset" ><i class="fa fa-lock"></i></a>';

        //         $details .= '&nbsp;<a href="javascript:;" onclick="custom_delete(\'/del_user/' . $d->id . '\')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm" class="btn btn-danger btn-sm" title="Delete User" ><i class="fa fa-trash"></i></a>';

        //         return $details;
        //     })
        ->rawColumns(['user_name','name','period'])
        ->make(true);
    }

    /**
     * Show paid subscriptions management page with approval functionality
     */
    public function paidSubscriptions()
    {
        Log::info('🔵 AccountsController::paidSubscriptions called');
        $data['pagename'] = 'Paid Subscriptions Management';
        return view('accounts/paidSubscriptions', $data);
    }

    /**
     * Get paid subscriptions data for DataTable
     * Shows B2B and B2C paid subscriptions with payment details
     */
    public function viewPaidSubscriptions()
    {
        Log::info('🔵 AccountsController::viewPaidSubscriptions called');
        
        // Check if refresh is requested (to bypass PHP cache)
        $refresh = request()->get('refresh') === 'true' || request()->has('_');
        
        // Clear PHP cache if refresh is requested
        if ($refresh) {
            $this->nodeApi->clearCache('/accounts/paid-subscriptions');
            Log::info('🔄 Cache cleared - fetching fresh data');
        }
        
        // Call Node.js API to get paid subscriptions
        // Add timestamp to bypass cache if refresh requested
        $params = $refresh ? ['refresh' => 'true', '_' => time()] : [];
        $apiResponse = $this->nodeApi->get('/accounts/paid-subscriptions', $params);
        
        Log::info('🔵 Node.js API Response for viewPaidSubscriptions', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $subscriptions = collect($apiResponse['data'])->map(function($subscription) {
                return (object)$subscription;
            });
            Log::info('✅ viewPaidSubscriptions: Successfully retrieved subscriptions', ['count' => $subscriptions->count()]);
        } else {
            Log::error('❌ Node API failed for viewPaidSubscriptions', ['response' => $apiResponse]);
            $subscriptions = collect([]);
        }
        
        return datatables()->of($subscriptions)
            ->addIndexColumn()
            ->editColumn('user_name', function ($d) {
                $userName = $d->shopname ?? $d->name ?? 'N/A';
                $userType = isset($d->user_type) ? strtoupper($d->user_type) : '';
                $badgeClass = $userType === 'B2B' ? 'badge-primary' : 'badge-info';
                return $userName . '<br><span class="badge ' . $badgeClass . ' badge-sm">' . $userType . '</span>';
            })
            ->editColumn('package_name', function ($d) {
                $name = ($d->name ?? 'N/A') . ' <small>(' . ($d->displayname ?? 'N/A') . ')</small>';
                $duration = isset($d->duration) ? '<br><span class="badge badge-secondary badge-sm">' . ucfirst($d->duration) . '</span>' : '';
                return $name . $duration;
            })
            ->editColumn('payment_info', function ($d) {
                $paymentId = $d->payment_moj_id ?? 'N/A';
                $paymentReqId = $d->payment_req_id ?? 'N/A';
                
                // Get payment date and time from created_at or pay_details timestamp
                $paymentDateTime = 'N/A';
                $paymentDate = 'N/A';
                $paymentTime = 'N/A';
                
                if (isset($d->created_at)) {
                    $paymentTimestamp = strtotime($d->created_at);
                    $paymentDate = date('Y-m-d', $paymentTimestamp);
                    $paymentTime = date('H:i:s', $paymentTimestamp);
                    $paymentDateTime = date('Y-m-d H:i:s', $paymentTimestamp);
                } else if (isset($d->pay_details)) {
                    // Try to get timestamp from pay_details
                    if (is_string($d->pay_details)) {
                        $details = json_decode($d->pay_details, true);
                        if ($details && isset($details['timestamp'])) {
                            $paymentTimestamp = strtotime($details['timestamp']);
                            $paymentDate = date('Y-m-d', $paymentTimestamp);
                            $paymentTime = date('H:i:s', $paymentTimestamp);
                            $paymentDateTime = date('Y-m-d H:i:s', $paymentTimestamp);
                        }
                    }
                }
                
                // Try to parse pay_details if it's a JSON string
                $payDetails = '';
                $paymentMethod = 'N/A';
                if (isset($d->pay_details)) {
                    if (is_string($d->pay_details)) {
                        $details = json_decode($d->pay_details, true);
                        if ($details) {
                            $paymentMethod = $details['paymentMethod'] ?? 'N/A';
                            $payDetails = '<br><small class="text-muted">Method: ' . $paymentMethod . '</small>';
                            if (isset($details['amount'])) {
                                $payDetails .= '<br><small class="text-muted">Amount: ₹' . $details['amount'] . '</small>';
                            }
                        }
                    }
                }
                
                return '<strong>Transaction ID:</strong> ' . $paymentId . 
                       '<br><strong>Request ID:</strong> ' . $paymentReqId . 
                       '<br><strong>Payment Date:</strong> ' . $paymentDate .
                       '<br><strong>Payment Time:</strong> ' . $paymentTime .
                       $payDetails;
            })
            ->editColumn('period', function ($d) {
                $fromDate = $d->from_date ? date('Y-m-d', strtotime($d->from_date)) : 'N/A';
                $toDate = $d->to_date ? date('Y-m-d', strtotime($d->to_date)) : 'N/A';
                
                if ($fromDate !== 'N/A' && $toDate !== 'N/A') {
                    $period = '<span class="badge badge-success rounded-pill">' . $fromDate . ' to ' . $toDate . '</span>';
                } else {
                    $period = '<span class="badge badge-warning rounded-pill">No period set</span>';
                }
                return $period;
            })
            ->editColumn('status', function ($d) {
                $approvalStatus = $d->approval_status ?? 'pending';
                $statusClass = $approvalStatus === 'approved' ? 'badge-success' : 
                              ($approvalStatus === 'rejected' ? 'badge-danger' : 'badge-warning');
                return '<span class="badge ' . $statusClass . '">' . ucfirst($approvalStatus) . '</span>';
            })
            ->addColumn('action', function ($d) {
                $approvalStatus = $d->approval_status ?? 'pending';
                
                if ($approvalStatus === 'approved') {
                    return '<button class="btn btn-success btn-sm" disabled title="Already Approved">
                                <i class="fas fa-check-circle"></i> Approved
                            </button>';
                } else {
                    $approveBtn = '<button class="btn btn-success btn-sm" onclick="approveSubscription(' . $d->id . ')" title="Approve Subscription">
                                       <i class="fas fa-check"></i> Approve
                                   </button>';
                    $rejectBtn = '<button class="btn btn-danger btn-sm ml-1" onclick="rejectSubscription(' . $d->id . ')" title="Reject Subscription">
                                      <i class="fas fa-times"></i> Reject
                                  </button>';
                    return $approveBtn . $rejectBtn;
                }
            })
            ->editColumn('price', function ($d) {
                return '₹' . number_format($d->price ?? 0, 2);
            })
            ->rawColumns(['user_name', 'package_name', 'payment_info', 'period', 'status', 'action'])
            ->make(true);
    }

    /**
     * Approve or reject a subscription
     */
    public function updateSubscriptionApproval(Request $request)
    {
        try {
            $subscriptionId = $request->post('subscription_id');
            $action = $request->post('action'); // 'approve' or 'reject'
            $notes = $request->post('notes', '');
            
            if (empty($subscriptionId) || empty($action)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription ID and action are required'
                ]);
            }
            
            if (!in_array($action, ['approve', 'reject'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action. Must be "approve" or "reject"'
                ]);
            }
            
            Log::info('🔵 AccountsController::updateSubscriptionApproval called', [
                'subscription_id' => $subscriptionId,
                'action' => $action,
                'notes' => $notes
            ]);
            
            $apiData = [
                'subscription_id' => $subscriptionId,
                'action' => $action,
                'notes' => $notes
            ];
            
            $apiResponse = $this->nodeApi->post('/accounts/subscription-approval', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                // Clear PHP cache to ensure fresh data on next load
                $this->nodeApi->clearCache('/accounts/paid-subscriptions');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Subscription ' . $action . 'd successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $apiResponse['msg'] ?? 'Failed to update subscription approval status'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error updating subscription approval:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Receive UPI payment transaction verification from React Native app
     * This endpoint receives transaction reference after successful UPI payment
     */
    public function receivePaymentTransaction(Request $request)
    {
        Log::info('🔵 AccountsController::receivePaymentTransaction called');
        
        try {
            $validator = Validator::make($request->all(), [
                'userId' => 'required|string',
                'packageId' => 'required|string',
                'transactionId' => 'required|string',
                'transactionRef' => 'nullable|string',
                'amount' => 'required|string',
                'responseCode' => 'nullable|string',
                'approvalRefNo' => 'nullable|string',
                'paymentMethod' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation failed for receivePaymentTransaction', [
                    'errors' => $validator->errors()->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 400);
            }

            $transactionData = [
                'user_id' => $request->userId,
                'package_id' => $request->packageId,
                'transaction_id' => $request->transactionId,
                'transaction_ref' => $request->transactionRef ?? $request->transactionId,
                'amount' => $request->amount,
                'response_code' => $request->responseCode ?? '00',
                'approval_ref_no' => $request->approvalRefNo ?? $request->transactionId,
                'payment_method' => $request->paymentMethod ?? 'UPI',
                'received_at' => now()->toISOString(),
            ];

            Log::info('✅ Received UPI payment transaction verification', $transactionData);

            // Forward to Node.js API to save the subscription
            // This ensures the subscription is created in the backend
            $apiData = [
                'user_id' => $request->userId,
                'package_id' => $request->packageId,
                'payment_moj_id' => $request->transactionId,
                'payment_req_id' => $request->approvalRefNo ?? $request->transactionId,
                'pay_details' => json_encode([
                    'transactionId' => $request->transactionId,
                    'transactionRef' => $request->transactionRef ?? $request->transactionId,
                    'responseCode' => $request->responseCode ?? '00',
                    'approvalRefNo' => $request->approvalRefNo ?? $request->transactionId,
                    'amount' => $request->amount,
                    'paymentMethod' => $request->paymentMethod ?? 'UPI',
                    'timestamp' => now()->toISOString(),
                ]),
            ];

            $apiResponse = $this->nodeApi->post('/saveUserPackages', $apiData);

            if ($apiResponse['status'] === 'success') {
                Log::info('✅ Transaction forwarded to Node.js API successfully', [
                    'transaction_id' => $request->transactionId,
                    'user_id' => $request->userId,
                    'package_id' => $request->packageId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment transaction received and processed successfully',
                    'data' => $transactionData
                ]);
            } else {
                Log::error('❌ Node.js API failed to process transaction', [
                    'response' => $apiResponse,
                    'transaction_data' => $transactionData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $apiResponse['msg'] ?? 'Failed to process transaction in backend',
                    'data' => $transactionData
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error receiving payment transaction:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing transaction: ' . $e->getMessage()
            ], 500);
        }
    }
}
