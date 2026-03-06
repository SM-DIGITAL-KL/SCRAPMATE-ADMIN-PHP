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

    private function getLoggedInZoneCode()
    {
        $email = strtolower((string) session('user_email', ''));
        if (preg_match('/^zone(\d{1,2})@scrapmate\.co\.in$/', $email, $matches)) {
            $zoneNumber = intval($matches[1]);
            if ($zoneNumber >= 1 && $zoneNumber <= 48) {
                return 'Z' . str_pad((string) $zoneNumber, 2, '0', STR_PAD_LEFT);
            }
        }
        return null;
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

    /**
     * Show pending bulk sell orders management page
     */
    public function pendingBulkSellOrders()
    {
        Log::info('🔵 AccountsController::pendingBulkSellOrders called');
        $data['pagename'] = 'Pending Bulk Sell Orders';
        return view('accounts/pendingBulkSellOrders', $data);
    }

    /**
     * Get pending bulk sell orders data for DataTable
     */
    public function viewPendingBulkSellOrders()
    {
        Log::info('🔵 AccountsController::viewPendingBulkSellOrders called');
        
        $apiResponse = $this->nodeApi->get('/accounts/pending-bulk-sell-orders');
        
        Log::info('🔵 Node.js API Response for viewPendingBulkSellOrders', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $orders = collect($apiResponse['data'])->map(function($order) {
                return (object)$order;
            });
            Log::info('✅ viewPendingBulkSellOrders: Successfully retrieved orders', ['count' => $orders->count()]);
        } else {
            Log::error('❌ Node API failed for viewPendingBulkSellOrders', ['response' => $apiResponse]);
            $orders = collect([]);
        }
        
        return datatables()->of($orders)
            ->addIndexColumn()
            ->editColumn('seller_name', function ($d) {
                return $d->seller_name ?? 'N/A';
            })
            ->editColumn('order_details', function ($d) {
                $quantity = isset($d->quantity) ? number_format($d->quantity, 0) . ' kg' : 'N/A';
                $askingPrice = isset($d->asking_price) ? '₹' . number_format($d->asking_price, 2) . '/kg' : 'N/A';
                $totalValue = (isset($d->quantity) && isset($d->asking_price)) ? '₹' . number_format($d->quantity * $d->asking_price, 2) : 'N/A';
                $location = $d->location ?? 'N/A';
                $scrapType = $d->scrap_type ?? 'N/A';
                $distance = isset($d->preferred_distance) ? $d->preferred_distance . ' km' : 'N/A';
                $whenAvailable = $d->when_available ?? 'N/A';
                
                // Parse subcategories if available
                $subcategories = '';
                if (isset($d->subcategories) && !empty($d->subcategories)) {
                    $subs = is_string($d->subcategories) ? json_decode($d->subcategories, true) : $d->subcategories;
                    if (is_array($subs) && count($subs) > 0) {
                        $subNames = array_map(function($sub) {
                            return $sub['subcategory_name'] ?? $sub['name'] ?? 'N/A';
                        }, array_slice($subs, 0, 3)); // Show first 3
                        $subcategories = '<br><strong>Subcategories:</strong> ' . implode(', ', $subNames);
                        if (count($subs) > 3) {
                            $subcategories .= ' (+' . (count($subs) - 3) . ' more)';
                        }
                    }
                }
                
                // Additional notes
                $notes = '';
                if (isset($d->additional_notes) && !empty($d->additional_notes)) {
                    $notes = '<br><strong>Notes:</strong> ' . substr($d->additional_notes, 0, 50) . (strlen($d->additional_notes) > 50 ? '...' : '');
                }
                
                // Payment Information
                $paymentInfo = '';
                if (isset($d->payment_status) && $d->payment_status === 'paid') {
                    $paymentAmount = isset($d->payment_amount) ? '₹' . number_format($d->payment_amount, 2) : 'N/A';
                    $paymentMojId = $d->payment_moj_id ?? 'N/A';
                    $orderValue = isset($d->order_value) ? '₹' . number_format($d->order_value, 2) : 'N/A';
                    $paymentInfo = '<br><br><strong style="color: #28a745;">✓ Payment Received</strong><br>' .
                                   '<strong>Order Value:</strong> ' . $orderValue . '<br>' .
                                   '<strong>Fee Paid:</strong> ' . $paymentAmount . '<br>' .
                                   '<strong>Payment ID:</strong> ' . $paymentMojId;
                } elseif (isset($d->payment_status) && $d->payment_status === 'pending') {
                    $paymentInfo = '<br><br><strong style="color: #ffc107;">⏳ Payment Pending</strong>';
                }
                
                return '<strong>Quantity:</strong> ' . $quantity . '<br>' .
                       '<strong>Asking Price:</strong> ' . $askingPrice . '<br>' .
                       '<strong>Total Value:</strong> ' . $totalValue . '<br>' .
                       '<strong>Location:</strong> ' . $location . '<br>' .
                       '<strong>Type:</strong> ' . $scrapType . '<br>' .
                       '<strong>Preferred Distance:</strong> ' . $distance . '<br>' .
                       '<strong>When Available:</strong> ' . $whenAvailable .
                       $subcategories . $notes . $paymentInfo;
            })
            ->editColumn('buyers_info', function ($d) {
                $acceptedBuyers = isset($d->accepted_buyers) ? (is_string($d->accepted_buyers) ? json_decode($d->accepted_buyers, true) : $d->accepted_buyers) : [];
                $totalCommitted = isset($d->total_committed_quantity) ? $d->total_committed_quantity : 0;
                $buyerCount = is_array($acceptedBuyers) ? count($acceptedBuyers) : 0;
                
                $info = '<strong>Interested Buyers:</strong> ' . $buyerCount . '<br>';
                $info .= '<strong>Total Committed:</strong> ' . number_format($totalCommitted, 0) . ' kg<br>';
                
                if ($buyerCount > 0) {
                    $info .= '<button onclick="viewBuyers(' . $d->id . ', ' . htmlspecialchars(json_encode($acceptedBuyers), ENT_QUOTES, 'UTF-8') . ')" class="btn btn-info btn-sm mt-1">';
                    $info .= '<i class="fas fa-users"></i> View Buyers</button>';
                } else {
                    $info .= '<span class="badge badge-warning">No buyers yet</span>';
                }
                
                return $info;
            })
            ->editColumn('created_at', function ($d) {
                return isset($d->created_at) ? date('Y-m-d H:i:s', strtotime($d->created_at)) : 'N/A';
            })
            ->editColumn('payment_info', function ($d) {
                $paymentStatus = $d->payment_status ?? 'pending';
                $paymentAmount = isset($d->payment_amount) ? '₹' . number_format($d->payment_amount, 2) : 'N/A';
                $orderValue = isset($d->order_value) ? '₹' . number_format($d->order_value, 2) : 'N/A';
                $paymentMojId = $d->payment_moj_id ?? 'N/A';
                
                if ($paymentStatus === 'paid') {
                    return '<span class="badge badge-success">Paid</span><br>' .
                           '<strong>Fee:</strong> ' . $paymentAmount . '<br>' .
                           '<strong>Order Value:</strong> ' . $orderValue . '<br>' .
                           '<small>ID: ' . $paymentMojId . '</small>';
                } elseif ($paymentStatus === 'pending') {
                    return '<span class="badge badge-warning">Pending</span>';
                } else {
                    return '<span class="badge badge-secondary">' . ucfirst($paymentStatus) . '</span>';
                }
            })
            ->editColumn('status', function ($d) {
                $status = $d->status ?? 'active';
                $badgeClass = $status === 'sold' ? 'badge-success' : 
                             ($status === 'cancelled' ? 'badge-danger' : 'badge-info');
                $committed = isset($d->total_committed_quantity) ? $d->total_committed_quantity : 0;
                $quantity = isset($d->quantity) ? $d->quantity : 0;
                $isFullyCommitted = $committed >= $quantity && $quantity > 0;
                
                $html = '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
                
                if ($isFullyCommitted && $status !== 'sold') {
                    $html .= '<br><span class="badge badge-warning mt-1">Fully Committed</span>';
                }
                
                return $html;
            })
            ->addColumn('action', function ($d) {
                $requestId = $d->id;
                $status = $d->status ?? 'active';
                $documents = isset($d->documents) ? (is_string($d->documents) ? json_decode($d->documents, true) : $d->documents) : [];
                
                $actions = '';
                
                // View documents button if documents exist
                if (!empty($documents)) {
                    $actions .= '<button onclick="viewDocuments(' . $requestId . ', ' . htmlspecialchars(json_encode($documents), ENT_QUOTES, 'UTF-8') . ')" class="btn btn-primary btn-sm mb-1" title="View Documents">';
                    $actions .= '<i class="fas fa-file-alt"></i> Docs</button> ';
                }
                
                // Show actions only for active orders
                if ($status === 'active') {
                    $actions .= '<button onclick="markAsSold(' . $requestId . ')" class="btn btn-success btn-sm mb-1" title="Mark as Sold">';
                    $actions .= '<i class="fas fa-check"></i> Sold</button> ';
                    
                    $actions .= '<button onclick="cancelRequest(' . $requestId . ')" class="btn btn-danger btn-sm mb-1" title="Cancel Request">';
                    $actions .= '<i class="fas fa-times"></i> Cancel</button>';
                } else {
                    $actions .= '<span class="badge badge-' . ($status === 'sold' ? 'success' : 'danger') . '">' . ucfirst($status) . '</span>';
                }
                
                return $actions;
            })
            ->rawColumns(['seller_name', 'order_details', 'buyers_info', 'payment_info', 'status', 'action'])
            ->make(true);
    }

    /**
     * Cancel a pending bulk sell order
     */
    public function cancelPendingBulkSellOrder(Request $request)
    {
        try {
            $apiData = [
                'request_id' => $request->request_id
            ];
            
            $apiResponse = $this->nodeApi->post('/accounts/pending-bulk-sell-order-cancel', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'Request cancelled successfully']);
            } else {
                return response()->json(['success' => false, 'message' => $apiResponse['msg'] ?? 'Failed to cancel request']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update pending bulk sell order status
     */
    public function updatePendingBulkSellOrderStatus(Request $request)
    {
        try {
            $apiData = [
                'request_id' => $request->request_id,
                'status' => $request->status
            ];
            
            $apiResponse = $this->nodeApi->post('/accounts/pending-bulk-sell-order-status', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                return response()->json(['success' => false, 'message' => $apiResponse['msg'] ?? 'Failed to update status']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Marketplace posts listing page (bulk sell + bulk buy).
     */
    public function marketplacePosts()
    {
        Log::info('🔵 AccountsController::marketplacePosts called');
        $data['pagename'] = 'Marketplace Posts';
        return view('accounts/marketplacePosts', $data);
    }

    /**
     * Marketplace posts data for DataTable.
     */
    public function viewMarketplacePosts()
    {
        Log::info('🔵 AccountsController::viewMarketplacePosts called');

        $sellResponse = $this->nodeApi->get('/accounts/pending-bulk-sell-orders');
        $buyResponse = $this->nodeApi->get('/accounts/pending-bulk-buy-orders');

        $sellOrders = ($sellResponse['status'] === 'success' && isset($sellResponse['data']) && is_array($sellResponse['data']))
            ? collect($sellResponse['data'])
            : collect([]);
        $buyOrders = ($buyResponse['status'] === 'success' && isset($buyResponse['data']) && is_array($buyResponse['data']))
            ? collect($buyResponse['data'])
            : collect([]);

        $rows = collect([]);

        $sellOrders->each(function ($order) use (&$rows) {
            $item = (array) $order;
            if (!$this->isMarketplaceUserType($item)) {
                return;
            }
            $item['post_type'] = 'sell';
            $rows->push((object) $item);
        });

        $buyOrders->each(function ($order) use (&$rows) {
            $item = (array) $order;
            if (!$this->isMarketplaceUserType($item)) {
                return;
            }
            $item['post_type'] = 'buy';
            $rows->push((object) $item);
        });

        $rows = $rows->sortByDesc(function ($item) {
            if (!isset($item->created_at) || empty($item->created_at)) {
                return 0;
            }
            return strtotime($item->created_at);
        })->values();

        return datatables()->of($rows)
            ->addIndexColumn()
            ->addColumn('post_type_badge', function ($d) {
                if (($d->post_type ?? '') === 'sell') {
                    return '<span class="badge badge-primary">Bulk Sell</span>';
                }
                return '<span class="badge badge-info">Bulk Buy</span>';
            })
            ->addColumn('user_details', function ($d) {
                $name = $d->seller_name ?? $d->shopname ?? $d->username ?? $d->user_name ?? $d->name ?? 'N/A';
                $phone = $d->phone ?? $d->contact ?? 'N/A';
                $userId = $d->user_id ?? $d->seller_id ?? 'N/A';

                return '<strong>' . e($name) . '</strong><br>' .
                    '<small>User ID: ' . e((string) $userId) . '</small><br>' .
                    '<small>Phone: ' . e((string) $phone) . '</small>';
            })
            ->addColumn('post_details', function ($d) {
                $quantity = isset($d->quantity) ? number_format((float) $d->quantity, 0) . ' kg' : 'N/A';
                $scrapType = $d->scrap_type ?? 'N/A';
                $locationParts = array_filter([
                    $d->location ?? null,
                    $d->district ?? null,
                    $d->state ?? null,
                    $d->street ?? null,
                    $d->landmark ?? null,
                ]);
                $location = !empty($locationParts) ? implode(', ', $locationParts) : 'N/A';
                $available = $d->when_available ?? 'N/A';
                $distance = isset($d->preferred_distance) ? $d->preferred_distance . ' km' : 'N/A';

                $subNames = [];
                $subcategories = $this->safeArray($d->subcategories ?? []);
                foreach ($subcategories as $sub) {
                    if (is_array($sub)) {
                        $subNames[] = $sub['subcategory_name'] ?? $sub['name'] ?? null;
                    } elseif (is_string($sub)) {
                        $subNames[] = $sub;
                    }
                }
                $subNames = array_values(array_filter($subNames));
                $subsText = !empty($subNames) ? implode(', ', $subNames) : 'N/A';

                return '<strong>Qty:</strong> ' . e($quantity) . '<br>' .
                    '<strong>Type:</strong> ' . e($scrapType) . '<br>' .
                    '<strong>Location:</strong> ' . e($location) . '<br>' .
                    '<strong>Subcategories:</strong> ' . e($subsText) . '<br>' .
                    '<strong>Preferred Distance:</strong> ' . e((string) $distance) . '<br>' .
                    '<strong>Available:</strong> ' . e((string) $available);
            })
            ->addColumn('media_details', function ($d) {
                $images = $this->safeArray($d->uploaded_images ?? ($d->images ?? []));
                $videos = $this->safeArray($d->videos ?? ($d->video_urls ?? ($d->uploaded_videos ?? [])));
                $documents = $this->safeArray($d->documents ?? []);

                $imageCount = count($images);
                $videoCount = count($videos);
                $docCount = count($documents);

                $links = [];
                if ($imageCount > 0 && !empty($images[0])) {
                    $links[] = '<a href="' . e($images[0]) . '" target="_blank">Image</a>';
                }
                if ($videoCount > 0 && !empty($videos[0])) {
                    $links[] = '<a href="' . e($videos[0]) . '" target="_blank">Video</a>';
                }
                if ($docCount > 0 && !empty($documents[0])) {
                    $links[] = '<a href="' . e($documents[0]) . '" target="_blank">Document</a>';
                }

                return '<strong>Images:</strong> ' . $imageCount . '<br>' .
                    '<strong>Videos:</strong> ' . $videoCount . '<br>' .
                    '<strong>Docs:</strong> ' . $docCount . '<br>' .
                    (!empty($links) ? implode(' | ', $links) : '<small>No media</small>');
            })
            ->addColumn('pricing_details', function ($d) {
                $isSell = ($d->post_type ?? '') === 'sell';
                $quantity = isset($d->quantity) ? (float) $d->quantity : null;

                if ($isSell) {
                    $askingPrice = isset($d->asking_price) ? (float) $d->asking_price : (isset($d->price) ? (float) $d->price : null);
                    $totalValue = isset($d->total_value) ? (float) $d->total_value : (($quantity !== null && $askingPrice !== null) ? ($quantity * $askingPrice) : null);
                    return '<strong>Asking Price:</strong> ' . ($askingPrice !== null ? '₹' . number_format($askingPrice, 2) : 'N/A') . '<br>' .
                        '<strong>Total Value:</strong> ' . ($totalValue !== null ? '₹' . number_format($totalValue, 2) : 'N/A');
                }

                $preferredPrice = isset($d->preferred_price) ? (float) $d->preferred_price : (isset($d->price) ? (float) $d->price : null);
                $paymentAmount = isset($d->payment_amount) ? (float) $d->payment_amount : null;
                return '<strong>Preferred Price:</strong> ' . ($preferredPrice !== null ? '₹' . number_format($preferredPrice, 2) : 'N/A') . '<br>' .
                    '<strong>Payment:</strong> ' . ($paymentAmount !== null ? '₹' . number_format($paymentAmount, 2) : 'N/A');
            })
            ->editColumn('status', function ($d) {
                $status = $d->status ?? 'N/A';
                $paymentStatus = $d->payment_status ?? null;
                $reviewStatus = $d->review_status ?? null;
                $reviewReason = $d->review_reason ?? null;
                $statusBadge = '<span class="badge badge-secondary">' . e(ucfirst(str_replace('_', ' ', (string) $status))) . '</span>';
                if ($paymentStatus) {
                    $paymentBadgeClass = $paymentStatus === 'approved' || $paymentStatus === 'paid'
                        ? 'badge-success'
                        : ($paymentStatus === 'rejected' ? 'badge-danger' : 'badge-warning');
                    $statusBadge .= '<br><span class="badge ' . $paymentBadgeClass . '">Payment: ' . e(ucfirst((string) $paymentStatus)) . '</span>';
                }
                if ($reviewStatus) {
                    $reviewBadgeClass = $reviewStatus === 'approve'
                        ? 'badge-success'
                        : ($reviewStatus === 'reject' ? 'badge-danger' : 'badge-warning');
                    $statusBadge .= '<br><span class="badge ' . $reviewBadgeClass . '">Review: ' . e(ucfirst((string) $reviewStatus)) . '</span>';
                }
                if ($reviewReason) {
                    $statusBadge .= '<br><small>Reason: ' . e((string) $reviewReason) . '</small>';
                }
                return $statusBadge;
            })
            ->editColumn('created_at', function ($d) {
                return isset($d->created_at) ? date('Y-m-d H:i:s', strtotime($d->created_at)) : 'N/A';
            })
            ->addColumn('action', function ($d) {
                $json = json_encode($d);
                $payload = base64_encode($json ?: '{}');
                return '<button class="btn btn-primary btn-sm" onclick="viewMarketplacePostDetails(\'' . $payload . '\')">View Details</button>';
            })
            ->rawColumns([
                'post_type_badge',
                'user_details',
                'post_details',
                'media_details',
                'pricing_details',
                'status',
                'action',
            ])
            ->make(true);
    }

    private function safeArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function isMarketplaceUserType(array $item)
    {
        $candidate = $item['user_type'] ?? $item['seller_user_type'] ?? $item['buyer_user_type'] ?? $item['type'] ?? null;
        if (!is_string($candidate) || trim($candidate) === '') {
            // Fallback for old API payloads not including user_type yet.
            return true;
        }
        return strtoupper(trim($candidate)) === 'M';
    }

    public function marketplacePostReview(Request $request)
    {
        try {
            $apiData = [
                'post_id' => $request->post_id,
                'post_type' => $request->post_type,
                'action' => $request->action,
                'reason' => $request->reason,
            ];

            $apiResponse = $this->nodeApi->post('/accounts/marketplace-post-review', $apiData);

            if (($apiResponse['status'] ?? 'error') === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => $apiResponse['msg'] ?? 'Marketplace post review updated',
                    'data' => $apiResponse['data'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $apiResponse['msg'] ?? 'Failed to update review status'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
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
        $params = [];
        $zone = $this->getLoggedInZoneCode();
        if ($zone) {
            $params['zone'] = $zone;
        }
        $apiResponse = $this->nodeApi->get('/accounts/view-subscribers', $params);
        
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
        $zone = $this->getLoggedInZoneCode();
        if ($zone) {
            $params['zone'] = $zone;
        }
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
                $userName = $d->shopname ?? $d->username ?? 'N/A';
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
