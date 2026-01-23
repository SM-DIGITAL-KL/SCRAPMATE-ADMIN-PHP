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
use App\Helpers\EnvReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdminController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function dashboard(Request $request)
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

    // Cached dashboard API endpoints (10 minutes cache)
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

    public function users()
    {
        $data['pagename'] = 'Users';
        return view('admin/users', $data);
    }
    
    public function b2bUsers(Request $request)
    {
        // Reduced logging for performance
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $appVersion = $request->get('app_version', '');
        
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if (!empty($search)) {
            $params['search'] = $search;
        }
        
        if (!empty($appVersion)) {
            $params['app_version'] = $appVersion;
        }
        
        $apiResponse = $this->nodeApi->get('/admin/b2b-users', $params);
        
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
            
            $data['pagename'] = 'B2B Users';
            return view('admin/b2bUsers', $data);
        } else {
            Log::error('Node API failed for b2bUsers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'B2B Users',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('admin/b2bUsers', $data);
        }
    }

    public function b2cUsers(Request $request)
    {
        // Reduced logging for performance
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $appVersion = $request->get('app_version', '');
        $approvalStatus = $request->get('approval_status', '');
        
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if (!empty($search)) {
            $params['search'] = $search;
        }
        
        if (!empty($appVersion)) {
            $params['app_version'] = $appVersion;
        }
        
        if (!empty($approvalStatus)) {
            $params['approval_status'] = $approvalStatus;
        }
        
        $apiResponse = $this->nodeApi->get('/admin/b2c-users', $params);
        
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
            
            $data['pagename'] = 'B2C Users';
            return view('admin/b2cUsers', $data);
        } else {
            Log::error('Node API failed for b2cUsers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'B2C Users',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('admin/b2cUsers', $data);
        }
    }

    public function newUsers(Request $request)
    {
        // Reduced logging for performance
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $appVersion = $request->get('app_version', '');
        
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if (!empty($search)) {
            $params['search'] = $search;
        }
        
        if (!empty($appVersion)) {
            $params['app_version'] = $appVersion;
        }
        
        $apiResponse = $this->nodeApi->get('/admin/new-users', $params);
        
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
            
            $data['pagename'] = 'New Users Manage';
            return view('admin/newUsers', $data);
        } else {
            Log::error('Node API failed for newUsers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'New Users Manage',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('admin/newUsers', $data);
        }
    }

    public function exportB2CUsersExcel(Request $request)
    {
        try {
            // Get ALL B2C users regardless of filters (no search, no app_version, no approval_status filters)
            $params = [
                'page' => 1,
                'limit' => 999999 // Get all records
            ];
            
            // Don't apply any filters - export all B2C users
            $apiResponse = $this->nodeApi->get('/admin/b2c-users', $params);
            
            if ($apiResponse['status'] !== 'success' || !isset($apiResponse['data']['users'])) {
                Log::error('Node API failed for exportB2CUsersExcel', ['response' => $apiResponse]);
                return redirect()->route('b2cUsers')->with('error', 'Failed to fetch data for export');
            }
            
            $users = $apiResponse['data']['users'];
            
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = ['SL NO', 'USER NAME', 'EMAIL', 'CONTACT NO', 'ADDRESS', 'SIGN UP DATE', 'APP TYPE', 'STATUS', 'CONTACTED'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header row
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6C5CE7'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(50);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(12);
            
            // Add data rows
            $row = 2;
            $slNo = 1;
            foreach ($users as $user) {
                $userObj = (object)$user;
                
                // Handle shop data safely
                $shop = null;
                if (isset($userObj->shop)) {
                    $shop = is_array($userObj->shop) ? (object)$userObj->shop : $userObj->shop;
                }
                
                // Determine contact number
                $contact = $userObj->contact ?? ($shop->contact ?? $userObj->mob_num ?? 'N/A');
                
                // Determine address
                $address = $userObj->address ?? ($shop->address ?? 'N/A');
                
                // Format sign up date
                $signUpDate = 'N/A';
                if (isset($userObj->created_at) && $userObj->created_at) {
                    try {
                        $signUpDate = \Carbon\Carbon::parse($userObj->created_at)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $signUpDate = 'N/A';
                    }
                }
                
                // Determine app type
                $appVersionValue = $userObj->app_version ?? 'v1';
                $appType = $appVersionValue === 'v2' ? 'V2' : 'V1';
                
                // Determine status
                $status = 'N/A';
                if ($appVersionValue === 'v2') {
                    $approvalStatusValue = $userObj->approval_status ?? ($shop->approval_status ?? 'pending');
                    if ($approvalStatusValue === 'approved') {
                        $status = 'Approved';
                    } elseif ($approvalStatusValue === 'pending') {
                        $status = 'Pending';
                    } elseif ($approvalStatusValue === 'rejected') {
                        $status = 'Rejected';
                    } else {
                        $status = 'Pending';
                    }
                } else {
                    if (isset($userObj->del_status) && $userObj->del_status == 1) {
                        $status = 'Active';
                    } else {
                        $status = 'Inactive';
                    }
                }
                
                // Determine contacted status
                $isContacted = $userObj->is_contacted ?? false;
                $contactedStatus = $isContacted ? 'Yes' : 'No';
                
                $sheet->setCellValue('A' . $row, $slNo);
                $sheet->setCellValue('B' . $row, $userObj->name ?? 'N/A');
                $sheet->setCellValue('C' . $row, $userObj->email ?? 'N/A');
                $sheet->setCellValue('D' . $row, $contact);
                $sheet->setCellValue('E' . $row, $address);
                $sheet->setCellValue('F' . $row, $signUpDate);
                $sheet->setCellValue('G' . $row, $appType);
                $sheet->setCellValue('H' . $row, $status);
                $sheet->setCellValue('I' . $row, $contactedStatus);
                
                // Wrap text for address column
                $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
                
                $row++;
                $slNo++;
            }
            
            // Add borders to all cells with data
            $lastRow = $row - 1;
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:I' . $lastRow)->applyFromArray($styleArray);
            
            // Set filename - export all B2C users
            $filename = 'b2c_users_all_' . date('Y-m-d_His') . '.xlsx';
            
            // Create writer and save to temporary file
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'b2c_users_');
            $writer->save($tempFile);
            
            // Return file download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Error exporting B2C users to Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('b2cUsers')->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    public function updateB2CContactedStatus(Request $request, $userId)
    {
        try {
            $isContacted = $request->input('is_contacted', false);
            
            // Convert to boolean if it's a string
            if (is_string($isContacted)) {
                $isContacted = filter_var($isContacted, FILTER_VALIDATE_BOOLEAN);
            }
            
            $apiResponse = $this->nodeApi->post("/admin/b2c-users/{$userId}/contacted-status", [
                'is_contacted' => $isContacted
            ]);
            
            if ($apiResponse['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Contacted status updated successfully',
                    'data' => $apiResponse['data'] ?? null
                ]);
            } else {
                Log::error('Node API failed for updateB2CContactedStatus', ['response' => $apiResponse]);
                return response()->json([
                    'status' => 'error',
                    'msg' => $apiResponse['msg'] ?? 'Failed to update contacted status',
                    'data' => null
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error updating B2C contacted status', [
                'userId' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'msg' => 'Error updating contacted status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function viewB2CUserDocuments(Request $request, $userId)
    {
        Log::info('AdminController::viewB2CUserDocuments called', ['userId' => $userId]);
        
        $apiResponse = $this->nodeApi->get("/admin/b2c-users/{$userId}");
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $userDataArray = $apiResponse['data'];
            
            // Convert to object and ensure shop is also an object
            $userData = (object)$userDataArray;
            if (isset($userDataArray['shop']) && is_array($userDataArray['shop'])) {
                $userData->shop = (object)$userDataArray['shop'];
            }
            
            $data = [
                'pagename' => 'B2C User Details',
                'user' => $userData
            ];
            return view('admin/b2cUserDocuments', $data);
        } else {
            Log::error('Node API failed for viewB2CUserDocuments', ['response' => $apiResponse]);
            return redirect()->route('b2cUsers')->with('error', 'Failed to load user details');
        }
    }

    public function srUsers(Request $request)
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
        
        // Data fetched directly from database - no cache
        
        $apiResponse = $this->nodeApi->get('/admin/sr-users', $params);
        
        // Log the response for debugging
        Log::info('SR Users API Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'has_data' => isset($apiResponse['data']),
            'users_count' => isset($apiResponse['data']['users']) ? count($apiResponse['data']['users']) : 0,
            'total' => $apiResponse['data']['total'] ?? 0
        ]);
        
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
            
            $data['pagename'] = 'SR Users';
            return view('admin/srUsers', $data);
        } else {
            Log::error('Node API failed for srUsers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'SR Users',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('admin/srUsers', $data);
        }
    }

    public function viewSRUserDocuments(Request $request, $userId)
    {
        Log::info('AdminController::viewSRUserDocuments called', ['userId' => $userId]);
        
        // Data fetched directly from database - no cache
        
        $apiResponse = $this->nodeApi->get("/admin/sr-users/{$userId}");
        
        Log::info('SR User Documents API Response', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'hasShop' => isset($apiResponse['data']['shop']),
            'hasB2BShop' => isset($apiResponse['data']['b2bShop']),
            'hasB2CShop' => isset($apiResponse['data']['b2cShop']),
            'b2bShopType' => isset($apiResponse['data']['b2bShop']) ? gettype($apiResponse['data']['b2bShop']) : 'not set',
            'b2cShopType' => isset($apiResponse['data']['b2cShop']) ? gettype($apiResponse['data']['b2cShop']) : 'not set',
            'shopType' => isset($apiResponse['data']['shop']) ? gettype($apiResponse['data']['shop']) : 'not set'
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $userDataArray = $apiResponse['data'];
            
            // Convert to object and ensure shop is also an object
            $userData = (object)$userDataArray;
            
            // Handle shop (for backward compatibility)
            if (isset($userDataArray['shop']) && $userDataArray['shop'] !== null) {
                if (is_array($userDataArray['shop'])) {
                    $userData->shop = (object)$userDataArray['shop'];
                } else {
                    $userData->shop = $userDataArray['shop'];
                }
            } else {
                $userData->shop = null;
            }
            
            // Handle separate B2B and B2C shops
            if (isset($userDataArray['b2bShop']) && $userDataArray['b2bShop'] !== null) {
                if (is_array($userDataArray['b2bShop'])) {
                    $userData->b2bShop = (object)$userDataArray['b2bShop'];
                } else {
                    $userData->b2bShop = $userDataArray['b2bShop'];
                }
            } else {
                $userData->b2bShop = null;
            }
            
            if (isset($userDataArray['b2cShop']) && $userDataArray['b2cShop'] !== null) {
                if (is_array($userDataArray['b2cShop'])) {
                    $userData->b2cShop = (object)$userDataArray['b2cShop'];
                } else {
                    $userData->b2cShop = $userDataArray['b2cShop'];
                }
            } else {
                $userData->b2cShop = null;
            }
            
            // If shop is not set but b2cShop or b2bShop exists, use the first available one for backward compatibility
            if ((!isset($userData->shop) || !$userData->shop || $userData->shop === null) && 
                (isset($userData->b2cShop) && $userData->b2cShop !== null)) {
                $userData->shop = $userData->b2cShop;
            } elseif ((!isset($userData->shop) || !$userData->shop || $userData->shop === null) && 
                      (isset($userData->b2bShop) && $userData->b2bShop !== null)) {
                $userData->shop = $userData->b2bShop;
            }
            
            // Extract srApprovalStatus from API response
            if (isset($userDataArray['srApprovalStatus'])) {
                $userData->srApprovalStatus = $userDataArray['srApprovalStatus'];
            }
            
            // Log for debugging
            Log::info('SR User Documents - Shop data', [
                'hasShop' => isset($userData->shop) && $userData->shop !== null,
                'hasB2BShop' => isset($userData->b2bShop) && $userData->b2bShop !== null,
                'hasB2CShop' => isset($userData->b2cShop) && $userData->b2cShop !== null,
                'b2bShopId' => $userData->b2bShop->id ?? null,
                'b2cShopId' => $userData->b2cShop->id ?? null,
                'shopId' => $userData->shop->id ?? null,
                'b2bApprovalStatus' => $userData->b2bShop->approval_status ?? null,
                'b2cApprovalStatus' => $userData->b2cShop->approval_status ?? null,
                'srApprovalStatus' => $userData->srApprovalStatus ?? null
            ]);
            
            $data = [
                'pagename' => 'SR User Details',
                'user' => $userData
            ];
            return view('admin/srUserDocuments', $data);
        } else {
            Log::error('Node API failed for viewSRUserDocuments', ['response' => $apiResponse]);
            return redirect()->route('srUsers')->with('error', 'Failed to load user details');
        }
    }

    public function updateSRApprovalStatus(Request $request, $userId)
    {
        Log::info('AdminController::updateSRApprovalStatus called', [
            'userId' => $userId,
            'approval_status' => $request->input('approval_status'),
            'rejection_reason' => $request->input('rejection_reason'),
            'shop_type' => $request->input('shop_type')
        ]);
        
        $approvalStatus = $request->input('approval_status');
        $shopType = $request->input('shop_type'); // 'b2b', 'b2c', or null for both
        
        if (!in_array($approvalStatus, ['approved', 'rejected', 'pending'])) {
            return redirect()->back()->with('error', 'Invalid approval status');
        }
        
        $apiData = [
            'approval_status' => $approvalStatus
        ];
        
        // Add shop_type if specified
        if ($shopType) {
            $apiData['shop_type'] = $shopType;
        }
        
        // Add rejection reason if status is rejected
        if ($approvalStatus === 'rejected' && $request->has('rejection_reason')) {
            $apiData['rejection_reason'] = $request->input('rejection_reason');
        }
        
        $apiResponse = $this->nodeApi->post("/admin/sr-users/{$userId}/approval-status", $apiData);
        
        if ($apiResponse['status'] === 'success') {
            $shopTypeLabel = $shopType ? strtoupper($shopType) : 'SR';
            return redirect()->back()->with('success', "{$shopTypeLabel} approval status updated to {$approvalStatus}");
        } else {
            Log::error('Node API failed for updateSRApprovalStatus', ['response' => $apiResponse]);
            return redirect()->back()->with('error', 'Failed to update approval status');
        }
    }

    public function viewB2BUserDocuments(Request $request, $userId)
    {
        Log::info('AdminController::viewB2BUserDocuments called', ['userId' => $userId]);
        
        $apiResponse = $this->nodeApi->get("/admin/b2b-users/{$userId}");
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $userDataArray = $apiResponse['data'];
            
            // Convert to object and ensure shop is also an object
            $userData = (object)$userDataArray;
            if (isset($userDataArray['shop']) && is_array($userDataArray['shop'])) {
                $userData->shop = (object)$userDataArray['shop'];
            }
            
            $data = [
                'pagename' => 'B2B User Documents',
                'user' => $userData
            ];
            return view('admin/b2bUserDocuments', $data);
        } else {
            Log::error('Node API failed for viewB2BUserDocuments', ['response' => $apiResponse]);
            return redirect()->route('b2bUsers')->with('error', 'Failed to load user documents');
        }
    }

    public function updateB2BApprovalStatus(Request $request, $userId)
    {
        Log::info('AdminController::updateB2BApprovalStatus called', [
            'userId' => $userId,
            'approval_status' => $request->input('approval_status')
        ]);
        
        $approvalStatus = $request->input('approval_status');
        
        if (!in_array($approvalStatus, ['approved', 'rejected', 'pending'])) {
            return redirect()->back()->with('error', 'Invalid approval status');
        }
        
        $apiResponse = $this->nodeApi->post("/admin/b2b-users/{$userId}/approval-status", [
            'approval_status' => $approvalStatus,
            'rejection_reason' => $request->input('rejection_reason', '')
        ]);
        
        if ($apiResponse['status'] === 'success') {
            return redirect()->back()->with('success', "B2B approval status updated to {$approvalStatus}");
        } else {
            Log::error('Node API failed for updateB2BApprovalStatus', ['response' => $apiResponse]);
            return redirect()->back()->with('error', 'Failed to update approval status');
        }
    }

    public function deliveryUsers(Request $request)
    {
        // Reduced logging for performance
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $appVersion = $request->get('app_version', '');
        
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if (!empty($search)) {
            $params['search'] = $search;
        }
        
        if (!empty($appVersion)) {
            $params['app_version'] = $appVersion;
        }
        
        $apiResponse = $this->nodeApi->get('/admin/delivery-users', $params);
        
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
            
            $data['pagename'] = 'Delivery Users (Door Buyers)';
            return view('admin/deliveryUsers', $data);
        } else {
            Log::error('Node API failed for deliveryUsers', ['response' => $apiResponse]);
            $data = [
                'pagename' => 'Delivery Users (Door Buyers)',
                'users' => collect([]),
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0,
                'hasMore' => false
            ];
            return view('admin/deliveryUsers', $data);
        }
    }

    public function updateB2CApprovalStatus(Request $request, $userId)
    {
        Log::info('AdminController::updateB2CApprovalStatus called', [
            'userId' => $userId,
            'approval_status' => $request->input('approval_status'),
            'rejection_reason' => $request->input('rejection_reason')
        ]);
        
        $approvalStatus = $request->input('approval_status');
        
        if (!in_array($approvalStatus, ['approved', 'rejected', 'pending'])) {
            return redirect()->back()->with('error', 'Invalid approval status');
        }
        
        $apiData = [
            'approval_status' => $approvalStatus
        ];
        
        // Add rejection reason if status is rejected
        if ($approvalStatus === 'rejected' && $request->has('rejection_reason')) {
            $apiData['rejection_reason'] = $request->input('rejection_reason');
        }
        
        $apiResponse = $this->nodeApi->post("/admin/b2c-users/{$userId}/approval-status", $apiData);
        
        // Log the response for debugging
        Log::info('AdminController::updateB2CApprovalStatus response', [
            'userId' => $userId,
            'response' => $apiResponse,
            'response_status' => $apiResponse['status'] ?? 'not_set',
            'response_type' => gettype($apiResponse)
        ]);
        
        // Check if response is valid and has success status
        if (isset($apiResponse['status']) && $apiResponse['status'] === 'success') {
            return redirect()->back()->with('success', "B2C approval status updated to {$approvalStatus}");
        } else {
            Log::error('Node API failed for updateB2CApprovalStatus', [
                'userId' => $userId,
                'response' => $apiResponse,
                'response_status' => $apiResponse['status'] ?? 'not_set',
                'response_msg' => $apiResponse['msg'] ?? 'no message'
            ]);
            return redirect()->back()->with('error', 'Failed to update approval status');
        }
    }

    public function viewDeliveryUserDocuments(Request $request, $userId)
    {
        Log::info('AdminController::viewDeliveryUserDocuments called', ['userId' => $userId]);
        
        $apiResponse = $this->nodeApi->get("/admin/delivery-users/{$userId}");
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $userDataArray = $apiResponse['data'];
            
            // Convert to object and ensure delivery_boy is also an object
            $userData = (object)$userDataArray;
            if (isset($userDataArray['delivery_boy']) && is_array($userDataArray['delivery_boy'])) {
                $userData->delivery_boy = (object)$userDataArray['delivery_boy'];
            }
            
            $data = [
                'pagename' => 'Delivery User Documents',
                'user' => $userData
            ];
            return view('admin/deliveryUserDocuments', $data);
        } else {
            Log::error('Node API failed for viewDeliveryUserDocuments', ['response' => $apiResponse]);
            return redirect()->route('deliveryUsers')->with('error', 'Failed to load user details');
        }
    }

    public function updateDeliveryApprovalStatus(Request $request, $userId)
    {
        Log::info('AdminController::updateDeliveryApprovalStatus called', [
            'userId' => $userId,
            'approval_status' => $request->input('approval_status'),
            'rejection_reason' => $request->input('rejection_reason')
        ]);
        
        $approvalStatus = $request->input('approval_status');
        
        if (!in_array($approvalStatus, ['approved', 'rejected', 'pending'])) {
            return redirect()->back()->with('error', 'Invalid approval status');
        }
        
        $apiData = [
            'approval_status' => $approvalStatus
        ];
        
        // Add rejection reason if status is rejected
        if ($approvalStatus === 'rejected' && $request->has('rejection_reason')) {
            $apiData['rejection_reason'] = $request->input('rejection_reason');
        }
        
        $apiResponse = $this->nodeApi->post("/admin/delivery-users/{$userId}/approval-status", $apiData);
        
        if ($apiResponse['status'] === 'success') {
            return redirect()->back()->with('success', "Delivery approval status updated to {$approvalStatus}");
        } else {
            Log::error('Node API failed for updateDeliveryApprovalStatus', ['response' => $apiResponse]);
            return redirect()->back()->with('error', 'Failed to update approval status');
        }
    }

    public function manage_users(Request $req , $id ='')
    {
        if ($req->isMethod('post')){
            $apiData = [
                'user_id' => $req->post('user_id'),
                'names' => $req->post('names'),
                'email' => $req->post('email'),
                'password' => $req->post('password'),
                'phone' => $req->post('phone')
            ];
            
            if ($req->post('user_id') != '') {
                // Update existing user
                $apiResponse = $this->nodeApi->post('/admin/manage_users/' . $req->post('user_id'), $apiData);
            } else {
                // Create new user
                $apiResponse = $this->nodeApi->post('/admin/manage_users', $apiData);
            }
            
            if ($apiResponse['status'] === 'success') {
                if ($req->post('user_id') != '') {
                    return Redirect::to('/users')->with('success','Updated successfully!');
                } else {
                    return Redirect::to('/users')->with('success','Add User successfully!');
                }
            } else {
                return Redirect::to('/users')->with('error', $apiResponse['msg'] ?? 'Operation failed');
            }
        }
        
        // GET request - get user data if id provided
        if ($id) {
            $apiResponse = $this->nodeApi->get('/admin/users/' . $id);
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                $data['user'] = (object)$apiResponse['data'];
            } else {
                $data['user'] = null;
            }
        } else {
            $data['user'] = null;
        }
        
        $data['pagename'] = 'Users';
        return view('admin/manage_users',$data);
    }

    public function view_users()
    {
        Log::info('AdminController::view_users called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/admin/view_users');
        
        Log::info('Node.js API Response for view_users', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for DataTables compatibility
            $users = collect($apiResponse['data'])->map(function($user) {
                return (object)$user;
            });
        } else {
            Log::error('Node API failed for view_users', ['response' => $apiResponse]);
            $users = collect([]);
        }
        
        return datatables()->of($users)
        ->addIndexColumn()
        ->addColumn('action',function ($d)
            {
                $details = '<a href="javascript:;" onclick="large_modal('.$d->id.','."'manage_users'".')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary btn-sm" title="Edit User"><i class="fas fa-pencil-alt"></i></a>';

                $details .= '&nbsp;<a href="javascript:;" onclick="basic_modal('.$d->user_id.','."'user_password_reset'".')"  data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-success btn-sm" title="Password Reset" ><i class="fa fa-lock"></i></a>';

                $details .= '&nbsp;<a href="javascript:;" onclick="custom_delete(\'/del_user/' . $d->id . '\')"  data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm" class="btn btn-danger btn-sm" title="Delete User" ><i class="fa fa-trash"></i></a>';

                return $details;
            })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function user_password_reset(Request $req, $id)
    {
        if ($req->isMethod('post'))
        {
            $apiData = [
                'new_pass' => $req->post('new_pass')
            ];
            
            $apiResponse = $this->nodeApi->post('/admin/user_password_reset/' . $id, $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return Redirect::back()->with('success', 'Password reset successfully!');
            } else {
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Something went wrong!');
            }
        }
        $display = '<div class="card-body">
            <div class="form-validation">
                    <form action="' . route('user_password_reset', ['id' => $id]) . '" method="POST" class="needs-validation" validate>
                    ' . csrf_field() . '
                        <div class="row">
                            <label class="form-label" for="validationCustom01">New Password<span class="text-danger">*</span></label>
                            <div class="col-lg-8">
                                <input type="password" name="new_pass" class="form-control" id="validationCustom01" placeholder="Enter a new password.." required>
                                <div class="invalid-feedback">Please enter a new passworde.</div>
                            </div>
                            <div class="col-lg-4 ms-auto">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';
        echo $display;
    }
    public function del_user($id)
    {
        $apiResponse = $this->nodeApi->delete('/admin/users/' . $id);
        
        if ($apiResponse['status'] === 'success') {
            return Redirect::back()->with('success','Delete successfully!');
        } else {
            return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Data Not Found');
        }
    }
    public function set_permission($id='')
    {
        Log::info('🔵 AdminController::set_permission called', ['id' => $id]);
        $endpoint = '/admin/set_permission' . ($id ? '/' . $id : '');
        $apiResponse = $this->nodeApi->get($endpoint);
        
        $data = $apiResponse['data'] ?? null;
        $users = $data['users'] ?? null;
        $permissions = $data['permission'] ?? null;
        
        Log::info('🔵 Node.js API Response for set_permission', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'msg' => $apiResponse['msg'] ?? 'no message',
            'hasData' => $data !== null,
            'dataType' => $data !== null ? gettype($data) : 'null',
            'hasUsers' => $users !== null,
            'usersCount' => $users !== null && is_array($users) ? count($users) : 0,
            'hasPermissions' => $permissions !== null,
            'permissionsCount' => $permissions !== null && is_array($permissions) ? count($permissions) : 0,
            'fullResponse' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for Blade template compatibility
            $data = $apiResponse['data'];
            
            // Convert users array to collection of objects
            if (isset($data['users']) && is_array($data['users'])) {
                Log::info('🔵 Processing users array', [
                    'count' => count($data['users']),
                    'firstUser' => $data['users'][0] ?? null
                ]);
                $data['users'] = collect($data['users'])->map(function($user) {
                    return (object)$user;
                });
                Log::info('✅ Converted users array to objects', [
                    'count' => $data['users']->count(),
                    'sampleUser' => $data['users']->first()
                ]);
            } else {
                $data['users'] = collect([]);
                Log::warning('⚠️ Users array not found or empty', [
                    'hasUsersKey' => isset($data['users']),
                    'usersType' => isset($data['users']) ? gettype($data['users']) : 'not set',
                    'dataKeys' => array_keys($data)
                ]);
            }
            
            // Convert permissions array to collection of objects
            if (isset($data['permission']) && is_array($data['permission'])) {
                $data['permission'] = collect($data['permission'])->map(function($permission) {
                    return (object)$permission;
                });
                Log::info('✅ Converted permissions array to objects', ['count' => $data['permission']->count()]);
            } else {
                $data['permission'] = collect([]);
                Log::warning('⚠️ Permissions array not found or empty');
            }
            
            // Convert user_data to object if it exists
            if (isset($data['user_data']) && is_array($data['user_data'])) {
                $data['user_data'] = (object)$data['user_data'];
            }
            
            $data['pagename'] = 'Users Permission';
            Log::info('✅ set_permission: Successfully prepared data for view');
            return view('admin/set_permission', $data);
        } else {
            Log::error('❌ Node API failed for set_permission', ['response' => $apiResponse]);
            $data = [
                'user_data' => null,
                'permission' => collect([]),
                'user_id' => $id,
                'users' => collect([]),
                'pagename' => 'Users Permission'
            ];
            return view('admin/set_permission', $data);
        }
    }
    
    public function store_user_per(Request $req)
    {
        if ($req->isMethod('post'))
        {
            $permission = [];
            foreach ($req->post() as $key => $value) {
                if (strpos($key, 'permission-') !== false) {
                    $permission[] = $value;
                }
            }
            
            $apiData = $req->post();
            $apiData['permissions'] = $permission;
            
            $apiResponse = $this->nodeApi->post('/admin/store_user_per', $apiData);
            
            if ($apiResponse['status'] === 'success') {
                return Redirect::back()->with('success', 'Permissions set successfully!');
            } else {
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Something went wrong!');
            }
        }
    }
    public function signUpReport(Request $req)
    {
        if ($req->isMethod('post')) {
            Log::info('═══════════════════════════════════════════════════════════');
            Log::info('🔵 AdminController::signUpReport - CSV EXPORT REQUEST');
            Log::info('═══════════════════════════════════════════════════════════');
            
            $apiParams = [
                'start_date' => $req->post('start_date'),
                'end_date' => $req->post('end_date'),
                'user_type' => $req->post('user_type')
            ];
            
            // Map user_type to readable name
            $userTypeMap = [
                'S' => 'Vendors',
                'C' => 'Customers',
                'D' => 'Door Step Buyers'
            ];
            $userTypeName = $userTypeMap[$apiParams['user_type']] ?? 'Unknown';
            
            Log::info('📊 Report Request Parameters:');
            Log::info('   User Type: ' . $apiParams['user_type'] . ' (' . $userTypeName . ')');
            Log::info('   Start Date: ' . $apiParams['start_date']);
            Log::info('   End Date: ' . $apiParams['end_date']);
            Log::info('   Date Range: ' . $apiParams['start_date'] . ' to ' . $apiParams['end_date']);
            
            Log::info('🔵 Calling Node.js API: /admin/signUpReport');
            $apiStartTime = microtime(true);
            $apiResponse = $this->nodeApi->get('/admin/signUpReport', $apiParams);
            $apiDuration = round((microtime(true) - $apiStartTime) * 1000, 2);
            
            Log::info('🔵 Node.js API Response for signUpReport:');
            Log::info('   Status: ' . ($apiResponse['status'] ?? 'unknown'));
            Log::info('   Has Data: ' . (isset($apiResponse['data']) ? 'Yes' : 'No'));
            Log::info('   Data Count: ' . (isset($apiResponse['data']) ? count($apiResponse['data']) : 0));
            Log::info('   API Duration: ' . $apiDuration . 'ms');
            
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data']) && !empty($apiResponse['data'])) {
                // Convert arrays to objects for CSV generation
                $data = collect($apiResponse['data'])->map(function($item) {
                    return (object)$item;
                });
                
                Log::info('✅ signUpReport: Successfully retrieved report data');
                Log::info('   Total Records: ' . $data->count());
                Log::info('   User Type: ' . $userTypeName);
                Log::info('   Starting CSV generation...');
                
                $csvStartTime = microtime(true);
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="signUpReport_' . $apiParams['user_type'] . '_' . date('Y-m-d') . '.csv"',
                ];
                $callback = function() use ($data, $apiParams) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['S.No', 'Name', 'Email', 'Mobile', 'Address', 'Place', 'Created Date']);
                    $i = 1;
                    foreach ($data as $value) {
                        fputcsv($file, [
                            $i, 
                            $value->name ?? '', 
                            $value->email ?? '', 
                            $value->mob_num ?? '', 
                            $value->address ?? '', 
                            $value->place ?? '', 
                            isset($value->created_at) ? date('d-m-Y', strtotime($value->created_at)) : ''
                        ]);
                        $i++;
                    }
                    fclose($file);
                };
                $csvDuration = round((microtime(true) - $csvStartTime) * 1000, 2);
                
                Log::info('✅ CSV Generation Complete');
                Log::info('   CSV Generation Duration: ' . $csvDuration . 'ms');
                Log::info('   Total Duration: ' . ($apiDuration + $csvDuration) . 'ms');
                Log::info('   File: signUpReport_' . $apiParams['user_type'] . '_' . date('Y-m-d') . '.csv');
                Log::info('═══════════════════════════════════════════════════════════');
                
                return response()->stream($callback, 200, $headers);
            } else {
                Log::error('❌ signUpReport: No data found or API failed');
                Log::error('   Response Status: ' . ($apiResponse['status'] ?? 'unknown'));
                Log::error('   Response Message: ' . ($apiResponse['msg'] ?? 'No message'));
                Log::error('   Has Data: ' . (isset($apiResponse['data']) ? 'Yes' : 'No'));
                Log::error('   Data Count: ' . (isset($apiResponse['data']) ? count($apiResponse['data']) : 0));
                Log::error('═══════════════════════════════════════════════════════════');
                return Redirect::back()->with('error', 'Data Not Found');
            }
        }
        
        Log::info('🔵 AdminController::signUpReport - PAGE VIEW REQUEST');
        $data['pagename'] = 'Sign Up Report';
        return view('admin/signUpReport', $data);
    }

    public function custNotification(Request $req)
    {
        Log::info('🔵 AdminController::custNotification called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/admin/custNotification');
        
        Log::info('🔵 Node.js API Response for custNotification', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'dataCount' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for Blade template compatibility
            $data['customer'] = collect($apiResponse['data'])->map(function($customer) {
                return (object)$customer;
            });
            $data['pagename'] = 'Customer Notification';
            Log::info('✅ custNotification: Successfully retrieved customers', ['count' => $data['customer']->count()]);
        } else {
            Log::error('❌ Node API failed for custNotification', ['response' => $apiResponse]);
            $data['customer'] = collect([]);
            $data['pagename'] = 'Customer Notification';
        }
        
        return view('admin/custNotification', $data);
    }
    
    public function sendCustNotification(Request $req)
    {
        if ($req->isMethod('post')) {
            Log::info('🔵 AdminController::sendCustNotification called - attempting to call Node.js API');
            $apiData = [
                'cust_ids' => $req->post('cust_ids'),
                'message' => $req->post('message'),
                'title' => $req->post('title')
            ];
            
            Log::info('🔵 Sending customer notification', [
                'cust_ids' => $apiData['cust_ids'] ?? 'none',
                'hasMessage' => !empty($apiData['message']),
                'hasTitle' => !empty($apiData['title'])
            ]);
            
            $apiResponse = $this->nodeApi->post('/admin/sendCustNotification', $apiData);
            
            Log::info('🔵 sendCustNotification Response', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'response' => $apiResponse
            ]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ sendCustNotification: Notification sent successfully');
                return Redirect::back()->with('success', 'Notification sent successfully!');
            } else {
                Log::error('❌ sendCustNotification: Failed to send notification', ['response' => $apiResponse]);
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Failed to send notification');
            }
        }
    }
    public function vendorNotification(Request $req)
    {
        Log::info('🔵 AdminController::vendorNotification called - attempting to call Node.js API');
        $apiResponse = $this->nodeApi->get('/admin/vendorNotification');
        
        Log::info('🔵 Node.js API Response for vendorNotification', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'shopsCount' => isset($apiResponse['data']['shops_count']) ? $apiResponse['data']['shops_count'] : 0,
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            // Convert arrays to objects for Blade template compatibility
            $data = $apiResponse['data'];
            $data['shops'] = collect($data['shops'] ?? [])->map(function($shop) {
                return (object)$shop;
            });
            $data['pagename'] = 'Vendor Notification';
            
            // Get criteria counts from API response if available, otherwise set defaults
            $data['criteria_counts'] = [
                '1' => $data['criteria_counts'][1] ?? $data['criteria_counts']['1'] ?? 0, // No shop images
                '2' => $data['criteria_counts'][2] ?? $data['criteria_counts']['2'] ?? 0, // No categories
                '3' => $data['criteria_counts'][3] ?? $data['criteria_counts']['3'] ?? 0, // No items
            ];
            
            // Cache removed - data fetched directly from database
            
            Log::info('✅ vendorNotification: Successfully retrieved vendors', ['count' => $data['shops_count']]);
        } else {
            Log::error('❌ Node API failed for vendorNotification', ['response' => $apiResponse]);
            $data = [
                'shops' => collect([]),
                'shops_count' => 0,
                'criteria_counts' => ['1' => 0, '2' => 0, '3' => 0],
                'pagename' => 'Vendor Notification'
            ];
        }
        
        return view('admin/vendorNotification', $data);
    }
    
    public function sendVendorNotification(Request $req)
    {
        if ($req->isMethod('post')) {
            Log::info('🔵 AdminController::sendVendorNotification called - attempting to call Node.js API');
            $apiData = [
                'vendor_ids' => $req->post('vendor_ids'),
                'message' => $req->post('message'),
                'title' => $req->post('title'),
                'criteria' => $req->post('criteria')
            ];
            
            Log::info('🔵 Sending vendor notification', [
                'vendor_ids_count' => is_array($apiData['vendor_ids']) ? count($apiData['vendor_ids']) : ($apiData['vendor_ids'] ? 1 : 0),
                'hasMessage' => !empty($apiData['message']),
                'hasTitle' => !empty($apiData['title']),
                'criteria' => $apiData['criteria'] ?? 'none'
            ]);
            
            $apiResponse = $this->nodeApi->post('/admin/sendVendorNotification', $apiData);
            
            Log::info('🔵 sendVendorNotification Response', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'response' => $apiResponse
            ]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ sendVendorNotification: Notification sent successfully');
                return Redirect::back()->with('success', 'Notification sent successfully!');
            } else {
                Log::error('❌ sendVendorNotification: Failed to send notification', ['response' => $apiResponse]);
                return Redirect::back()->with('error', $apiResponse['msg'] ?? 'Failed to send notification');
            }
        }
    }
    public function check_distance(Request $req)
    {
        $apiData = [
            'lat1' => $req->post('lat1') ?? 8.4677,
            'lon1' => $req->post('lon1') ?? 76.9629,
            'lat2' => $req->post('lat2') ?? 8.3651,
            'lon2' => $req->post('lon2') ?? 77.0051
        ];
        
        $apiResponse = $this->nodeApi->post('/admin/check_distance', $apiData);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data']['distance'])) {
            return "The distance between the two places is " . round($apiResponse['data']['distance'], 1) . " km";
        } else {
            return "Error calculating distance";
        }
    }

    public function callLogSearch() {
        $data['pagename'] = 'callLogSearch';
        return view('admin/callLogSearch', $data);
    }

    public function getcallLogSearch(Request $req)
    {
        $apiResponse = $this->nodeApi->get('/admin/getcallLogSearch', $req->all());
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $users = collect($apiResponse['data']);
        } else {
            Log::error('Node API failed for getcallLogSearch', ['response' => $apiResponse]);
            $users = collect([]);
        }
        
        return datatables()->of($users)
        ->addIndexColumn()
        ->editColumn('created_at',function ($d)
        {
            $details = date('d-m-Y h:i A', strtotime($d->created_at)).'<br>';
            $details .= date('h:i A', strtotime($d->created_at));
            return $details;
        })
        ->rawColumns(['created_at'])
        ->make(true);
    }

    /**
     * Display subscription packages page
     */
    public function subscriptionPackages(Request $request)
    {
        try {
            // Clear cache for this request to ensure fresh data
            $this->nodeApi->clearCache('/subscription-packages');
            
            // Fetch packages directly from DynamoDB via Node.js API
            // This uses the same API endpoint that the React Native app uses
            $apiResponse = $this->nodeApi->get('/subscription-packages');
            
            // Debug logging
            Log::info('Subscription packages API response', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'has_data' => isset($apiResponse['data']),
                'data_count' => isset($apiResponse['data']) ? count($apiResponse['data']) : 0,
            ]);
            
            if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
                $packages = $apiResponse['data'];
                
                // Ensure packages is an array
                if (!is_array($packages)) {
                    $packages = [];
                }
                
                // Display packages from DynamoDB
                return view('admin.subscriptionPackages', [
                    'pagename' => 'Subscription Packages',
                    'packages' => $packages,
                ]);
            }
            
            // If API call failed or returned error
            Log::warning('Subscription packages API returned error or no data', [
                'response' => $apiResponse
            ]);
            
            return view('admin.subscriptionPackages', [
                'pagename' => 'Subscription Packages',
                'packages' => [],
                'error' => isset($apiResponse['message']) ? $apiResponse['message'] : 'No packages found. Please run the seed script to create default packages.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching subscription packages: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return view('admin.subscriptionPackages', [
                'pagename' => 'Subscription Packages',
                'packages' => [],
                'error' => 'Failed to load subscription packages: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update subscription package
     */
    public function updateSubscriptionPackage(Request $request, $id)
    {
        try {
            // Handle DELETE request
            if ($request->isMethod('delete') || $request->has('_method') && strtoupper($request->input('_method')) === 'DELETE') {
                $apiResponse = $this->nodeApi->delete("/subscription-packages/{$id}");
                
                if ($apiResponse['status'] === 'success') {
                    $this->nodeApi->clearCache('/subscription-packages');
                    return redirect()->route('subscriptionPackages')
                        ->with('success', 'Subscription package deleted successfully');
                }
                
                return redirect()->route('subscriptionPackages')
                    ->with('error', $apiResponse['message'] ?? 'Failed to delete subscription package');
            }
            
            $data = $request->only([
                'id',
                'name',
                'price',
                'duration',
                'description',
                'features',
                'popular',
                'userType',
                'upiId',
                'merchantName',
                'isActive',
                'pricePercentage',
                'isPercentageBased',
            ]);
            
            // Convert features string to array if needed
            if (isset($data['features']) && is_string($data['features'])) {
                $data['features'] = array_filter(array_map('trim', explode("\n", $data['features'])));
            }
            
            // Convert popular to boolean
            if (isset($data['popular'])) {
                $data['popular'] = filter_var($data['popular'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Convert isActive to boolean
            if (isset($data['isActive'])) {
                $data['isActive'] = filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Convert isPercentageBased to boolean
            if (isset($data['isPercentageBased'])) {
                $data['isPercentageBased'] = filter_var($data['isPercentageBased'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Convert price to number
            if (isset($data['price'])) {
                $data['price'] = (float) $data['price'];
            }
            
            // Convert pricePercentage to number if provided
            if (isset($data['pricePercentage']) && $data['pricePercentage'] !== '') {
                $data['pricePercentage'] = (float) $data['pricePercentage'];
            } else {
                unset($data['pricePercentage']);
            }
            
            // Handle CREATE (new package)
            if ($id === 'new' || empty($id)) {
                // Ensure id is provided
                if (empty($data['id'])) {
                    return redirect()->route('subscriptionPackages')
                        ->with('error', 'Package ID is required');
                }
                
                $apiResponse = $this->nodeApi->post('/subscription-packages', $data);
                
                if ($apiResponse['status'] === 'success') {
                    $this->nodeApi->clearCache('/subscription-packages');
                    return redirect()->route('subscriptionPackages')
                        ->with('success', 'Subscription package created successfully');
                }
                
                return redirect()->route('subscriptionPackages')
                    ->with('error', $apiResponse['message'] ?? 'Failed to create subscription package');
            }
            
            // Handle UPDATE (existing package)
            $apiResponse = $this->nodeApi->put("/subscription-packages/{$id}", $data);
            
            if ($apiResponse['status'] === 'success') {
                // Clear PHP-side cache for subscription packages
                $this->nodeApi->clearCache('/subscription-packages');
                
                return redirect()->route('subscriptionPackages')
                    ->with('success', 'Subscription package updated successfully');
            }
            
            return redirect()->route('subscriptionPackages')
                ->with('error', $apiResponse['message'] ?? 'Failed to update subscription package');
        } catch (\Exception $e) {
            Log::error('Error managing subscription package: ' . $e->getMessage());
            return redirect()->route('subscriptionPackages')
                ->with('error', 'Failed to manage subscription package: ' . $e->getMessage());
        }
    }

    /**
     * Cache Management Page
     */
    public function cacheManagement()
    {
        $data = [
            'pagename' => 'Cache Management'
        ];
        return view('admin/cacheManagement', $data);
    }

    /**
     * Clear cache for specific user type
     * POST /admin/cache/clear
     */
    public function clearCache(Request $request)
    {
        try {
            $userType = $request->input('userType');
            
            if (!in_array($userType, ['b2b', 'b2c', 'sr', 'd', 'all'])) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Invalid user type. Must be one of: b2b, b2c, sr, d, all',
                    'data' => null
                ], 400);
            }

            // Call Node.js API to clear cache
            $apiResponse = $this->nodeApi->post('/admin/cache/clear', [
                'userType' => $userType
            ]);

            if ($apiResponse['status'] === 'success') {
                Log::info('Cache cleared successfully', [
                    'userType' => $userType,
                    'deletedCount' => $apiResponse['data']['deletedCount'] ?? 0
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'msg' => $apiResponse['msg'] ?? 'Cache cleared successfully',
                    'data' => $apiResponse['data'] ?? null
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => $apiResponse['msg'] ?? 'Failed to clear cache',
                    'data' => null
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to clear cache: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Proxy endpoint to add nearby 'N' type users to order
    public function addNearbyNUsersToOrder(Request $request, $orderId)
    {
        try {
            $radius = $request->get('radius', 20);
            // Use GET-style query parameter in the endpoint URL
            $endpoint = "/admin/order/{$orderId}/add-nearby-n-users?radius={$radius}";
            $response = $this->nodeApi->post($endpoint, []);
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error adding nearby N users to order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to add nearby users',
                'data' => null
            ], 500);
        }
    }

    // Proxy endpoint to add nearby 'D' type users to order
    public function addNearbyDUsersToOrder(Request $request, $orderId)
    {
        try {
            $radius = $request->get('radius', 20);
            // Use GET-style query parameter in the endpoint URL
            $endpoint = "/admin/order/{$orderId}/add-nearby-d-users?radius={$radius}";
            $response = $this->nodeApi->post($endpoint, []);
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error adding nearby D users to order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to add nearby users',
                'data' => null
            ], 500);
        }
    }

    // Proxy endpoint to add bulk notified vendors from bulk_message_notifications
    public function addBulkNotifiedVendors(Request $request, $orderId)
    {
        try {
            $endpoint = "/admin/order/{$orderId}/add-bulk-notified-vendors";
            $response = $this->nodeApi->post($endpoint, []);
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error adding bulk notified vendors: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to add bulk notified vendors',
                'data' => null
            ], 500);
        }
    }

    // Proxy endpoint to add a single vendor to order's notified_vendor_ids
    public function addVendorToOrder(Request $request, $orderId, $vendorId)
    {
        try {
            $endpoint = "/admin/order/{$orderId}/add-vendor/{$vendorId}";
            $response = $this->nodeApi->post($endpoint, []);
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error adding vendor to order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to add vendor to order',
                'data' => null
            ], 500);
        }
    }
}
