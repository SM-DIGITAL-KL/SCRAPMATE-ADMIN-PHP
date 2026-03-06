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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CustomerController extends Controller
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
    /**
     * DataTables server-side endpoint for Users (Customers V1 & V2) page.
     * Same UI as vendors manage / users. Returns customers with app_type v1 or v2 only.
     */
    public function view_users_customers(Request $request)
    {
        // For DataTables server-side processing, we need to fetch all data first
        // then let DataTables handle pagination on the client side
        $params = ['page' => 1, 'limit' => 99999];
        
        \Illuminate\Support\Facades\Log::info('🔵 CustomerController::view_users_customers called', [
            'params' => $params,
            'endpoint' => '/admin/customers',
            'datatables_params' => $request->all()
        ]);
        
        // Use longer timeout for large data fetches (60 seconds)
        $apiResponse = $this->nodeApi->get('/admin/customers', $params, 60);

        \Illuminate\Support\Facades\Log::info('🔵 CustomerController::view_users_customers API response', [
            'status' => $apiResponse['status'] ?? 'none',
            'msg' => $apiResponse['msg'] ?? 'no message',
            'has_data' => isset($apiResponse['data']),
            'data_type' => isset($apiResponse['data']) ? gettype($apiResponse['data']) : 'null',
        ]);

        $data = $apiResponse['data'] ?? null;
        $usersRaw = $data['users'] ?? null;

        if ($apiResponse['status'] !== 'success' || !$usersRaw || !is_array($usersRaw)) {
            \Illuminate\Support\Facades\Log::warning('⚠️ view_users_customers: no data or API error', [
                'status' => $apiResponse['status'] ?? 'none',
                'has_data' => !empty($data),
                'has_users' => !empty($usersRaw),
                'users_count' => is_array($usersRaw) ? count($usersRaw) : 0,
                'users_type' => gettype($usersRaw),
                'msg' => $apiResponse['msg'] ?? '',
                'error' => $apiResponse['error'] ?? null,
            ]);
            return datatables()->of(collect([]))
                ->addIndexColumn()
                ->addColumn('app_type', function ($d) { return ''; })
                ->addColumn('date_joined', function ($d) { return ''; })
                ->addColumn('address', function ($d) { return ''; })
                ->addColumn('is_contacted', function ($d) { return ''; })
                ->addColumn('action', function ($d) { return ''; })
                ->rawColumns(['app_type', 'action'])
                ->make(true);
        }

        $users = collect($usersRaw);
        $normalize = function ($u) {
            $arr = is_array($u) ? $u : (array) $u;
            $customer = $arr['customer'] ?? null;
            $cust = is_array($customer) ? $customer : (array) ($customer ?? []);
            $address = $arr['address'] ?? ($cust['address'] ?? '');
            $createdAt = $arr['created_at'] ?? ($cust['created_at'] ?? null);
            $isContacted = $arr['is_contacted'] ?? $cust['is_contacted'] ?? false;
            if (is_string($isContacted)) {
                $isContacted = in_array(strtolower(trim($isContacted)), ['1', 'true', 'yes'], true);
            }
            return [
                'id' => $arr['id'] ?? null,
                'name' => $arr['name'] ?? 'N/A',
                'email' => $arr['email'] ?? ($cust['email'] ?? 'N/A'),
                'contact' => $arr['contact'] ?? ($arr['mob_num'] ?? ($cust['contact'] ?? 'N/A')),
                'app_version' => isset($arr['app_version']) ? trim((string) $arr['app_version']) : '',
                'address' => is_string($address) ? trim($address) : '',
                'created_at' => $createdAt,
                'is_contacted' => (bool) $isContacted,
            ];
        };

        // Keep all customer records from API; non-v2 app_version values are treated as V1 in UI.
        $filtered = $users->values();

        // Sort: V2 users first (newest first), then V1 users (newest first)
        $filtered = $filtered->sort(function ($a, $b) use ($normalize) {
            $na = $normalize($a);
            $nb = $normalize($b);
            $isV2A = (strtolower(trim($na['app_version'] ?? '')) === 'v2') ? 1 : 0;
            $isV2B = (strtolower(trim($nb['app_version'] ?? '')) === 'v2') ? 1 : 0;
            if ($isV2A !== $isV2B) {
                return $isV2B - $isV2A; // V2 first, then V1
            }
            $tsA = 0;
            $tsB = 0;
            if (!empty($na['created_at'])) {
                try { $tsA = (new \DateTime($na['created_at']))->getTimestamp(); } catch (\Exception $e) {}
            }
            if (!empty($nb['created_at'])) {
                try { $tsB = (new \DateTime($nb['created_at']))->getTimestamp(); } catch (\Exception $e) {}
            }
            return $tsB - $tsA; // newest first within each app type
        })->values();

        $list = $filtered->map(function ($u) use ($normalize) {
            $n = $normalize($u);
            $dateJoined = '';
            if (!empty($n['created_at'])) {
                try {
                    $dt = new \DateTime($n['created_at']);
                    $dateJoined = $dt->format('d-M-Y');
                } catch (\Exception $e) {
                    $dateJoined = $n['created_at'];
                }
            }
            return (object) [
                'id' => $n['id'],
                'name' => $n['name'],
                'email' => $n['email'],
                'phone' => $n['contact'],
                'app_version' => $n['app_version'] !== '' ? strtolower($n['app_version']) : 'v1',
                'address' => $n['address'],
                'date_joined' => $dateJoined,
                'is_contacted' => $n['is_contacted'],
            ];
        });

        \Illuminate\Support\Facades\Log::info('✅ view_users_customers: processing data', [
            'total_from_api' => $users->count(),
            'after_filter' => $list->count(),
            'first_user_sample' => $list->first(),
        ]);

        return datatables()->of($list)
            ->addIndexColumn()
            ->addColumn('app_type', function ($d) {
                $v = strtolower($d->app_version ?? 'v1');
                if ($v === 'v2') {
                    return '<span class="badge bg-primary">V2</span>';
                }
                return '<span class="badge bg-secondary">V1</span>';
            })
            ->addColumn('date_joined', function ($d) {
                return e($d->date_joined ?? '—');
            })
            ->addColumn('address', function ($d) {
                $addr = trim($d->address ?? '');
                if ($addr === '') {
                    return '—';
                }
                if (strlen($addr) > 60) {
                    return '<span title="' . e($addr) . '">' . e(substr($addr, 0, 57)) . '…</span>';
                }
                return e($addr);
            })
            ->addColumn('is_contacted', function ($d) {
                $contacted = $d->is_contacted ?? false;
                if ($contacted) {
                    return '<span class="badge bg-success">Yes</span>';
                }
                return '<span class="badge bg-secondary">No</span>';
            })
            ->addColumn('action', function ($d) {
                $id = (int) ($d->id ?? 0);
                return '<div class="dropdown">
                    <button type="button" class="btn btn-success light sharp" data-bs-toggle="dropdown">
                        <svg width="20px" height="20px" viewBox="0 0 24 24"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:;" onclick="large_modal(' . $id . ',\'show_recent_orders\',\'Recent Orders\')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">Recent Orders</a>
                        <a class="dropdown-item" href="javascript:;" onclick="custom_delete(\'/del_customer/' . $id . '\')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm">Delete</a>
                    </div>
                </div>';
            })
            ->rawColumns(['app_type', 'address', 'is_contacted', 'action'])
            ->make(true);
    }

    /**
     * Export total users (customers V1 & V2) to XLSX.
     * Same data as /users page (view_users_customers).
     */
    public function exportTotalUsersExcel(Request $request)
    {
        try {
            $params = ['page' => 1, 'limit' => 99999];
            $apiResponse = $this->nodeApi->get('/admin/customers', $params, 60);

            $data = $apiResponse['data'] ?? null;
            $usersRaw = $data['users'] ?? null;

            if ($apiResponse['status'] !== 'success' || !$usersRaw || !is_array($usersRaw)) {
                Log::warning('exportTotalUsersExcel: no data or API error', [
                    'status' => $apiResponse['status'] ?? 'none',
                    'has_users' => !empty($usersRaw),
                ]);
                return redirect()->route('users')->with('error', 'Failed to fetch data for export');
            }

            $users = collect($usersRaw);
            $normalize = function ($u) {
                $arr = is_array($u) ? $u : (array) $u;
                $customer = $arr['customer'] ?? null;
                $cust = is_array($customer) ? $customer : (array) ($customer ?? []);
                $address = $arr['address'] ?? ($cust['address'] ?? '');
                $createdAt = $arr['created_at'] ?? ($cust['created_at'] ?? null);
                $isContacted = $arr['is_contacted'] ?? $cust['is_contacted'] ?? false;
                if (is_string($isContacted)) {
                    $isContacted = in_array(strtolower(trim($isContacted)), ['1', 'true', 'yes'], true);
                }
                return [
                    'id' => $arr['id'] ?? null,
                    'name' => $arr['name'] ?? 'N/A',
                    'email' => $arr['email'] ?? ($cust['email'] ?? 'N/A'),
                    'contact' => $arr['contact'] ?? ($arr['mob_num'] ?? ($cust['contact'] ?? 'N/A')),
                    'app_version' => isset($arr['app_version']) ? trim((string) $arr['app_version']) : '',
                    'address' => is_string($address) ? trim($address) : '',
                    'created_at' => $createdAt,
                    'is_contacted' => (bool) $isContacted,
                ];
            };

            // Keep all customer records from API; non-v2 app_version values are treated as V1 in export.
            $filtered = $users->values();

            $filtered = $filtered->sort(function ($a, $b) use ($normalize) {
                $na = $normalize($a);
                $nb = $normalize($b);
                $isV2A = (strtolower(trim($na['app_version'] ?? '')) === 'v2') ? 1 : 0;
                $isV2B = (strtolower(trim($nb['app_version'] ?? '')) === 'v2') ? 1 : 0;
                if ($isV2A !== $isV2B) {
                    return $isV2B - $isV2A;
                }
                $tsA = 0;
                $tsB = 0;
                if (!empty($na['created_at'])) {
                    try { $tsA = (new \DateTime($na['created_at']))->getTimestamp(); } catch (\Exception $e) {}
                }
                if (!empty($nb['created_at'])) {
                    try { $tsB = (new \DateTime($nb['created_at']))->getTimestamp(); } catch (\Exception $e) {}
                }
                return $tsB - $tsA;
            })->values();

            $list = $filtered->map(function ($u) use ($normalize) {
                $n = $normalize($u);
                $dateJoined = '';
                if (!empty($n['created_at'])) {
                    try {
                        $dt = new \DateTime($n['created_at']);
                        $dateJoined = $dt->format('d-M-Y');
                    } catch (\Exception $e) {
                        $dateJoined = $n['created_at'];
                    }
                }
                return (object) [
                    'name' => $n['name'],
                    'email' => $n['email'],
                    'phone' => $n['contact'],
                    'app_version' => $n['app_version'] !== '' ? strtolower($n['app_version']) : 'v1',
                    'address' => $n['address'],
                    'date_joined' => $dateJoined,
                    'is_contacted' => $n['is_contacted'],
                ];
            });

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['SL NO', 'NAME', 'EMAIL', 'PHONE', 'APP', 'DATE JOINED', 'ADDRESS', 'IS CONTACTED'];
            $sheet->fromArray($headers, null, 'A1');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6C5CE7'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(8);
            $sheet->getColumnDimension('F')->setWidth(14);
            $sheet->getColumnDimension('G')->setWidth(50);
            $sheet->getColumnDimension('H')->setWidth(14);

            $row = 2;
            $slNo = 1;
            foreach ($list as $d) {
                $sheet->setCellValue('A' . $row, $slNo);
                $sheet->setCellValue('B' . $row, $d->name ?? 'N/A');
                $sheet->setCellValue('C' . $row, $d->email ?? 'N/A');
                $sheet->setCellValue('D' . $row, $d->phone ?? 'N/A');
                $sheet->setCellValue('E' . $row, strtoupper($d->app_version ?? 'v1'));
                $sheet->setCellValue('F' . $row, $d->date_joined ?? '—');
                $sheet->setCellValue('G' . $row, $d->address ?? '—');
                $sheet->setCellValue('H' . $row, ($d->is_contacted ?? false) ? 'Yes' : 'No');
                $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);
                $row++;
                $slNo++;
            }

            $lastRow = $row - 1;
            if ($lastRow >= 1) {
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ];
                $sheet->getStyle('A1:H' . $lastRow)->applyFromArray($styleArray);
            }

            $filename = 'total_users_' . date('Y-m-d_His') . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'total_users_');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error exporting total users to Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('users')->with('error', 'Failed to export: ' . $e->getMessage());
        }
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
                    // Extract shop name from plain string (format: "shopname, address, ...")
                    if (!str_contains($order->shopdetails, '{') && !str_contains($order->shopdetails, '[')) {
                        // Try to extract shop name (first part before comma)
                        $parts = explode(',', $order->shopdetails);
                        if (!empty($parts[0])) {
                            $shopName = trim($parts[0]);
                        } else {
                            // If no comma, use the whole string (but limit length)
                            $shopName = strlen($order->shopdetails) > 100 
                                ? substr($order->shopdetails, 0, 100) . '...' 
                                : $order->shopdetails;
                        }
                    }
                }
                
                // Additional fallback: if we have shop_id but no shop name, try to fetch from API
                if ($shopName === 'N/A' && isset($order->shop_id) && $order->shop_id) {
                    try {
                        // Try to get shop name from shop_id via API
                        $shopResponse = $this->nodeApi->get('/agent/shop/' . $order->shop_id);
                        if (isset($shopResponse['status']) && $shopResponse['status'] === 'success' 
                            && isset($shopResponse['data']['shop']['shopname'])) {
                            $shopName = $shopResponse['data']['shop']['shopname'];
                            $shopId = $order->shop_id;
                        }
                    } catch (\Exception $apiErr) {
                        // Silently fail - we'll just show N/A
                        Log::debug('Could not fetch shop from API: ' . $apiErr->getMessage());
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
        ->addColumn('app_type',function ($order)
        {
            $appVersion = 'v1'; // Default to v1
            
            // Try to get app_version from order object directly
            if (isset($order->app_version)) {
                $appVersion = $order->app_version;
            } 
            // Try to get from customerdetails if it's an object/array
            elseif (isset($order->customerdetails)) {
                try {
                    if (is_object($order->customerdetails)) {
                        $appVersion = $order->customerdetails->app_version ?? ($order->customerdetails->appVersion ?? 'v1');
                    } elseif (is_array($order->customerdetails)) {
                        $appVersion = $order->customerdetails['app_version'] ?? ($order->customerdetails['appVersion'] ?? 'v1');
                    } elseif (is_string($order->customerdetails)) {
                        // Try to decode JSON
                        $json = json_decode($order->customerdetails, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $appVersion = $json['app_version'] ?? ($json['appVersion'] ?? 'v1');
                        } else {
                            // Try with stripslashes
                            $cleaned = stripslashes($order->customerdetails);
                            $json = json_decode($cleaned, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                $appVersion = $json['app_version'] ?? ($json['appVersion'] ?? 'v1');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // If parsing fails, default to v1
                    $appVersion = 'v1';
                }
            }
            
            // Normalize app version
            $appVersion = strtolower(trim($appVersion));
            if ($appVersion === 'v2') {
                return '<span class="badge bg-primary">V2</span>';
            } else {
                return '<span class="badge bg-secondary">V1</span>';
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
        ->rawColumns(['customerdetails' ,'status' ,'shopdetails' ,'action','callStatus','app_type'])
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
            
            // Parse orderdetails if it's a string
            if (isset($order->orderdetails) && is_string($order->orderdetails)) {
                try {
                    $json = json_decode($order->orderdetails, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $order->orderdetails = $json;
                    } else {
                        $cleaned = stripslashes($order->orderdetails);
                        $json = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $order->orderdetails = $json;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing orderdetails: ' . $e->getMessage());
                }
            }
            
            // Enrich customer details from Customer table if customerdetails is missing or incomplete
            $needsCustomerEnrichment = false;
            $wasStringAddress = false;
            $savedAddressString = null;
            
            // Check if customerdetails is missing or empty
            if (!isset($order->customerdetails) || empty($order->customerdetails)) {
                $needsCustomerEnrichment = true;
                Log::info('Customer enrichment needed: customerdetails is missing or empty');
            } elseif (is_string($order->customerdetails)) {
                // If it's just a string (address), we need to enrich
                $wasStringAddress = true;
                $savedAddressString = $order->customerdetails;
                $needsCustomerEnrichment = true;
                Log::info('Customer enrichment needed: customerdetails is a string (address only)', ['address' => $savedAddressString]);
            } elseif (is_object($order->customerdetails) || is_array($order->customerdetails)) {
                $cd = is_object($order->customerdetails) ? $order->customerdetails : (object)$order->customerdetails;
                $hasName = !empty($cd->name) || !empty($cd->customer_name) || !empty($cd->full_name);
                $hasContact = !empty($cd->contact) || !empty($cd->phone) || !empty($cd->mobile) || !empty($cd->mob_num) || !empty($cd->phone_number);
                
                if (!$hasName || !$hasContact) {
                    $needsCustomerEnrichment = true;
                    Log::info('Customer enrichment needed: missing name or contact', [
                        'hasName' => $hasName,
                        'hasContact' => $hasContact,
                        'customerdetails_keys' => is_object($cd) ? array_keys((array)$cd) : []
                    ]);
                }
            }
            
            if ($needsCustomerEnrichment && isset($order->customer_id) && !empty($order->customer_id)) {
                try {
                    Log::info('Fetching customer details from API', ['customer_id' => $order->customer_id]);
                    $customerResponse = $this->nodeApi->get('/customer/' . $order->customer_id);
                    
                    if ($customerResponse['status'] === 'success' && isset($customerResponse['data']) && !empty($customerResponse['data'])) {
                        $customer = (object)$customerResponse['data'];
                        Log::info('Customer data fetched successfully', [
                            'customer_id' => $order->customer_id,
                            'customer_name' => $customer->name ?? 'N/A',
                            'customer_contact' => $customer->contact ?? ($customer->phone ?? ($customer->mobile ?? ($customer->mob_num ?? 'N/A')))
                        ]);
                        
                        // Initialize customerdetails as object if it's not already
                        if ($wasStringAddress) {
                            // If it was a string (address), create object with address
                            $order->customerdetails = (object)['address' => $savedAddressString];
                        } elseif (!isset($order->customerdetails)) {
                            $order->customerdetails = (object)[];
                        } elseif (is_array($order->customerdetails)) {
                            $order->customerdetails = (object)$order->customerdetails;
                        }
                        
                        // Populate customer details from Customer table
                        if (empty($order->customerdetails->name) && empty($order->customerdetails->customer_name)) {
                            $order->customerdetails->name = $customer->name ?? $customer->customer_name ?? '';
                        }
                        if (empty($order->customerdetails->contact) && empty($order->customerdetails->phone) && empty($order->customerdetails->mobile)) {
                            $order->customerdetails->contact = $customer->contact ?? $customer->phone ?? $customer->mobile ?? $customer->mob_num ?? '';
                            $order->customerdetails->phone = $order->customerdetails->contact;
                        }
                        if (empty($order->customerdetails->address) && !$wasStringAddress) {
                            $order->customerdetails->address = $customer->address ?? ($savedAddressString ?? '');
                        }
                        
                        Log::info('Customer details enriched successfully', [
                            'name' => $order->customerdetails->name ?? 'N/A',
                            'contact' => $order->customerdetails->contact ?? 'N/A'
                        ]);
                    } else {
                        Log::warning('Customer API response was not successful or empty, trying User table', [
                            'status' => $customerResponse['status'] ?? 'unknown',
                            'msg' => $customerResponse['msg'] ?? 'N/A',
                            'has_data' => isset($customerResponse['data']),
                            'data_empty' => empty($customerResponse['data'] ?? null)
                        ]);
                        
                        // For v2 orders, customer_id might be a user_id instead
                        // Try fetching from User table via admin API
                        try {
                            Log::info('Trying User table for customer_id (might be user_id)', ['customer_id' => $order->customer_id]);
                            $userResponse = $this->nodeApi->get('/admin/users/' . $order->customer_id);
                            
                            if ($userResponse['status'] === 'success' && isset($userResponse['data']) && !empty($userResponse['data'])) {
                                $user = (object)$userResponse['data'];
                                Log::info('User data fetched successfully', [
                                    'user_id' => $order->customer_id,
                                    'user_name' => $user->name ?? 'N/A',
                                    'user_mobile' => $user->mob_num ?? ($user->mobile ?? ($user->phone ?? 'N/A'))
                                ]);
                                
                                // Initialize customerdetails as object if it's not already
                                if ($wasStringAddress) {
                                    $order->customerdetails = (object)['address' => $savedAddressString];
                                } elseif (!isset($order->customerdetails)) {
                                    $order->customerdetails = (object)[];
                                } elseif (is_array($order->customerdetails)) {
                                    $order->customerdetails = (object)$order->customerdetails;
                                }
                                
                                // Populate customer details from User table
                                if (empty($order->customerdetails->name) && empty($order->customerdetails->customer_name)) {
                                    $order->customerdetails->name = $user->name ?? '';
                                    $order->customerdetails->customer_name = $user->name ?? '';
                                }
                                if (empty($order->customerdetails->contact) && empty($order->customerdetails->phone) && empty($order->customerdetails->mobile)) {
                                    $order->customerdetails->contact = $user->mob_num ?? $user->mobile ?? $user->phone ?? '';
                                    $order->customerdetails->phone = $order->customerdetails->contact;
                                }
                                
                                Log::info('Customer details enriched from User table successfully', [
                                    'name' => $order->customerdetails->name ?? 'N/A',
                                    'contact' => $order->customerdetails->contact ?? 'N/A'
                                ]);
                            }
                        } catch (\Exception $userErr) {
                            Log::error('Error fetching user details: ' . $userErr->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching customer details: ' . $e->getMessage(), [
                        'customer_id' => $order->customer_id ?? 'N/A',
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                if (!$needsCustomerEnrichment) {
                    Log::info('Customer enrichment not needed - customerdetails already has data', [
                        'has_name' => !empty($order->customerdetails->name ?? $order->customerdetails->customer_name ?? null),
                        'has_contact' => !empty($order->customerdetails->contact ?? $order->customerdetails->phone ?? null)
                    ]);
                } else {
                    Log::warning('Customer enrichment needed but customer_id is missing', [
                        'customer_id' => $order->customer_id ?? 'N/A',
                        'has_customerdetails' => isset($order->customerdetails),
                        'customerdetails_type' => isset($order->customerdetails) ? gettype($order->customerdetails) : 'N/A'
                    ]);
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

    public function createOrder()
    {
        $params = ['page' => 1, 'limit' => 99999];
        $zone = $this->getLoggedInZoneCode();
        if ($zone) {
            $params['zone'] = $zone;
        }
        $usersResponse = $this->nodeApi->get('/admin/customers', $params, 60);
        $groupedSubcategoriesResponse = $this->nodeApi->get('/subcategories/grouped', [], 30);

        $users = [];
        if (($usersResponse['status'] ?? '') === 'success') {
            $rawUsers = $usersResponse['data']['users'] ?? [];
            $users = collect($rawUsers)->map(function ($user) {
                $arr = is_array($user) ? $user : (array) $user;
                $customer = $arr['customer'] ?? [];
                $customerArr = is_array($customer) ? $customer : (array) $customer;
                $address = trim((string) ($arr['address'] ?? ''));
                if ($address === '') {
                    $address = trim((string) ($customerArr['address'] ?? ''));
                }
                if ($address === '') {
                    $address = trim((string) ($arr['location'] ?? ($customerArr['location'] ?? '')));
                }
                if ($address === '') {
                    $parts = [
                        trim((string) ($arr['place'] ?? ($customerArr['place'] ?? ''))),
                        trim((string) ($arr['state'] ?? ($customerArr['state'] ?? ''))),
                        trim((string) ($arr['pincode'] ?? ($customerArr['pincode'] ?? ''))),
                    ];
                    $parts = array_values(array_filter($parts, function ($v) {
                        return $v !== '';
                    }));
                    $address = implode(', ', $parts);
                }
                $latitude = '';
                $longitude = '';

                $latLogRaw = $arr['lat_log'] ?? ($arr['latlng'] ?? ($arr['location_lat_lng'] ?? ($customerArr['lat_log'] ?? '')));
                if (is_string($latLogRaw) && strpos($latLogRaw, ',') !== false) {
                    $parts = array_map('trim', explode(',', $latLogRaw));
                    if (count($parts) >= 2) {
                        $latitude = $parts[0];
                        $longitude = $parts[1];
                    }
                }

                if ($latitude === '') {
                    $latitude = (string) ($arr['latitude'] ?? ($arr['lat'] ?? ($customerArr['latitude'] ?? ($customerArr['lat'] ?? ''))));
                }
                if ($longitude === '') {
                    $longitude = (string) ($arr['longitude'] ?? ($arr['lng'] ?? ($arr['long'] ?? ($customerArr['longitude'] ?? ($customerArr['lng'] ?? ($customerArr['long'] ?? ''))))));
                }

                return [
                    'id' => $arr['id'] ?? null,
                    'name' => trim((string) ($arr['name'] ?? ($customerArr['name'] ?? ''))),
                    'email' => trim((string) ($arr['email'] ?? ($customerArr['email'] ?? ''))),
                    'phone' => (string) ($arr['mob_num'] ?? ($arr['contact'] ?? ($customerArr['contact'] ?? ''))),
                    'address' => $address,
                    'latitude' => trim($latitude),
                    'longitude' => trim($longitude),
                    'app_type' => (string) ($arr['app_type'] ?? ''),
                    'user_type' => (string) ($arr['user_type'] ?? ''),
                ];
            })
            ->filter(function ($user) {
                return !empty($user['id']);
            })
            ->sortBy(function ($user) {
                return strtolower($user['name'] ?: ('user-' . $user['id']));
            })
            ->values()
            ->all();
        }

        $categories = [];
        $subcategories = [];
        if (($groupedSubcategoriesResponse['status'] ?? '') === 'success') {
            $groups = $groupedSubcategoriesResponse['data'] ?? [];

            foreach ($groups as $group) {
                $mainCategory = $group['main_category'] ?? [];
                $mainCategoryId = $mainCategory['id'] ?? null;
                $mainCategoryName = $mainCategory['name'] ?? ($mainCategory['category_name'] ?? ($mainCategory['cat_name'] ?? 'Main Category'));
                $items = $group['subcategories'] ?? [];

                if ($mainCategoryId) {
                    $categories[] = [
                        'id' => $mainCategoryId,
                        'name' => $mainCategoryName,
                    ];
                }

                foreach ($items as $item) {
                    $subcategoryId = $item['id'] ?? null;
                    if (!$subcategoryId) {
                        continue;
                    }

                    $subcategories[] = [
                        'id' => $subcategoryId,
                        'name' => $item['subcategory_name'] ?? 'Subcategory',
                        'main_category_id' => $mainCategoryId,
                        'main_category_name' => $mainCategoryName,
                        'default_price' => (float) ($item['default_price'] ?? 0),
                        'price_unit' => (string) ($item['price_unit'] ?? 'kg'),
                    ];
                }
            }
        }

        usort($subcategories, function ($a, $b) {
            return strcmp(
                strtolower($a['main_category_name'] . ' - ' . $a['name']),
                strtolower($b['main_category_name'] . ' - ' . $b['name'])
            );
        });
        $categories = collect($categories)
            ->unique('id')
            ->sortBy(function ($item) {
                return strtolower($item['name'] ?? '');
            })
            ->values()
            ->all();

        return view('customers/create_order', [
            'pagename' => 'Create Order',
            'users' => $users,
            'categories' => $categories,
            'subcategories' => $subcategories,
        ]);
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|numeric',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'numeric',
            'subcategory_ids' => 'required|array|min:1',
            'subcategory_ids.*' => 'numeric',
            'subcategory_weights' => 'nullable|array',
            'subcategory_weights.*' => 'nullable|numeric|min:0',
            'address' => 'required|string|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'estim_weight' => 'nullable|numeric|min:0',
            'estim_price' => 'nullable|numeric|min:0',
            'photos' => 'nullable|array|max:6',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $zone = $this->getLoggedInZoneCode();
        if ($zone) {
            $zoneUserCheck = $this->nodeApi->get('/admin/customer-autofill/' . (int) $request->input('user_id'), ['zone' => $zone], 15);
            if (($zoneUserCheck['status'] ?? '') !== 'success') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $zoneUserCheck['msg'] ?? 'Selected user is outside your zone.');
            }
        }

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();

        $selectedSubcategoryIds = collect($request->input('subcategory_ids', []))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();

        $rawSubcategoryWeights = $request->input('subcategory_weights', []);
        $subcategoryWeights = [];
        if (is_array($rawSubcategoryWeights)) {
            foreach ($rawSubcategoryWeights as $subId => $weight) {
                $normalizedSubId = (int) $subId;
                if ($normalizedSubId <= 0) {
                    continue;
                }
                $weightValue = trim((string) $weight);
                if ($weightValue === '') {
                    continue;
                }
                $subcategoryWeights[$normalizedSubId] = max(0, (float) $weightValue);
            }
        }

        $globalEstimWeight = max(0, (float) $request->input('estim_weight', 0));
        $orderDetails = [];

        $groupedSubcategoriesResponse = $this->nodeApi->get('/subcategories/grouped', [], 30);
        if (($groupedSubcategoriesResponse['status'] ?? '') === 'success') {
            $groups = $groupedSubcategoriesResponse['data'] ?? [];
            $subcategoryMap = [];

            foreach ($groups as $group) {
                $mainCategory = $group['main_category'] ?? [];
                $mainCategoryId = (int) ($mainCategory['id'] ?? 0);
                $mainCategoryName = $mainCategory['name'] ?? ($mainCategory['category_name'] ?? ($mainCategory['cat_name'] ?? 'Main Category'));
                $items = $group['subcategories'] ?? [];
                foreach ($items as $item) {
                    $subId = (int) ($item['id'] ?? 0);
                    if ($subId <= 0) {
                        continue;
                    }
                    $subcategoryMap[$subId] = [
                        'subcategory_id' => $subId,
                        'subcategory_name' => $item['subcategory_name'] ?? 'Selected Subcategory',
                        'main_category_id' => $mainCategoryId,
                        'main_category_name' => $mainCategoryName,
                        'default_price' => (float) ($item['default_price'] ?? 0),
                        'price_unit' => (string) ($item['price_unit'] ?? 'kg'),
                    ];
                }
            }

            foreach ($selectedSubcategoryIds as $subId) {
                if (!isset($subcategoryMap[$subId])) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'One or more selected subcategories are invalid.');
                }
                $meta = $subcategoryMap[$subId];
                if (!in_array((int) $meta['main_category_id'], $selectedCategoryIds, true)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Selected subcategories must belong to selected categories.');
                }
                $orderDetails[] = [
                    'subcategory_id' => $meta['subcategory_id'],
                    'subcategory_name' => $meta['subcategory_name'],
                    'main_category_id' => $meta['main_category_id'],
                    'main_category_name' => $meta['main_category_name'],
                    'material_name' => $meta['subcategory_name'],
                    'name' => $meta['subcategory_name'],
                    'quantity' => array_key_exists($subId, $subcategoryWeights)
                        ? (float) $subcategoryWeights[$subId]
                        : $globalEstimWeight,
                    'preferred_price' => (float) ($meta['default_price'] ?? 0),
                    'price_unit' => (string) ($meta['price_unit'] ?? 'kg'),
                ];
            }
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to load subcategories from API.');
        }

        $estimWeight = round(array_reduce($orderDetails, function ($sum, $detail) {
            return $sum + (float) ($detail['quantity'] ?? 0);
        }, 0), 2);
        if ($estimWeight <= 0) {
            $estimWeight = $globalEstimWeight;
        }

        $requestedEstimPrice = trim((string) $request->input('estim_price', ''));
        if ($requestedEstimPrice !== '') {
            $estimPrice = (float) $requestedEstimPrice;
        } else {
            $estimPrice = round(array_reduce($orderDetails, function ($sum, $detail) {
                $quantity = (float) ($detail['quantity'] ?? 0);
                $price = (float) ($detail['preferred_price'] ?? 0);
                return $sum + ($quantity * $price);
            }, 0), 2);
        }

        foreach ($orderDetails as $index => $detail) {
            $qty = (float) ($detail['quantity'] ?? 0);
            $unitPrice = (float) ($detail['preferred_price'] ?? 0);
            $lineTotal = round($qty * $unitPrice, 2);
            $orderDetails[$index]['expected_weight_kg'] = $qty;
            $orderDetails[$index]['weight'] = $qty;
            $orderDetails[$index]['price_per_kg'] = $unitPrice;
            $orderDetails[$index]['amount_per_kg'] = $unitPrice;
            $orderDetails[$index]['price'] = $lineTotal;
            $orderDetails[$index]['total_price'] = $lineTotal;
        }

        if ($requestedEstimPrice !== '' && count($orderDetails) > 0) {
            $requestedTotal = (float) $requestedEstimPrice;
            $totalQty = array_reduce($orderDetails, function ($sum, $detail) {
                return $sum + (float) ($detail['quantity'] ?? 0);
            }, 0);

            if ($totalQty > 0) {
                $uniformUnitPrice = round($requestedTotal / $totalQty, 2);
                $runningTotal = 0.0;

                foreach ($orderDetails as $idx => $detail) {
                    $qty = (float) ($detail['quantity'] ?? 0);
                    $lineTotal = round($qty * $uniformUnitPrice, 2);

                    // Keep exact total aligned with requested amount by adjusting final line.
                    if ($idx === count($orderDetails) - 1) {
                        $lineTotal = round($requestedTotal - $runningTotal, 2);
                    }

                    $orderDetails[$idx]['preferred_price'] = $uniformUnitPrice;
                    $orderDetails[$idx]['price_per_kg'] = $uniformUnitPrice;
                    $orderDetails[$idx]['amount_per_kg'] = $uniformUnitPrice;
                    $orderDetails[$idx]['price'] = $lineTotal;
                    $orderDetails[$idx]['total_price'] = $lineTotal;
                    $runningTotal += $lineTotal;
                }
            } else {
                $orderDetails[0]['price'] = $requestedTotal;
                $orderDetails[0]['total_price'] = $requestedTotal;
            }
        }

        $payload = [
            'customer_id' => (string) $request->input('user_id'),
            'orderdetails' => json_encode($orderDetails),
            'customerdetails' => trim((string) $request->input('address')),
            'latitude' => (string) $request->input('latitude'),
            'longitude' => (string) $request->input('longitude'),
            'estim_weight' => (string) $estimWeight,
            'estim_price' => (string) $estimPrice,
            'preferred_pickup_time' => now()->toIso8601String(),
        ];

        $files = [];
        $photos = $request->file('photos', []);
        $photos = is_array($photos) ? array_slice($photos, 0, 6) : [];
        foreach ($photos as $index => $photo) {
            if ($photo && $photo->isValid()) {
                $files['image' . ($index + 1)] = $photo;
            }
        }

        $response = $this->nodeApi->postMultipartWithFiles('/v2/orders/pickup-request', $payload, $files, 90);

        if (($response['status'] ?? '') === 'success') {
            $orderNumber = $response['data']['order_number'] ?? null;
            $successMessage = $orderNumber
                ? 'Order created successfully. Order Number: ' . $orderNumber
                : 'Order created successfully.';
            return redirect()->route('orders')->with('success', $successMessage);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $response['msg'] ?? 'Failed to create order.');
    }

    public function userAutofill($id)
    {
        $address = '';
        $latitude = '';
        $longitude = '';
        $customerRecordId = null;
        $zone = $this->getLoggedInZoneCode();
        $zoneParams = $zone ? ['zone' => $zone] : [];

        // Primary source: dedicated autofill endpoint (if deployed in Node backend)
        $response = $this->nodeApi->get('/admin/customer-autofill/' . $id, $zoneParams, 15);
        if ($zone && ($response['status'] ?? '') !== 'success') {
            return response()->json([
                'status' => 'error',
                'msg' => $response['msg'] ?? 'Access denied for this zone',
                'data' => null,
            ], 403);
        }
        if (($response['status'] ?? '') === 'success' && isset($response['data'])) {
            $address = trim((string) ($response['data']['address'] ?? ''));
            $latitude = trim((string) ($response['data']['latitude'] ?? ''));
            $longitude = trim((string) ($response['data']['longitude'] ?? ''));
        }

        // Fallback 1: derive from /admin/customers list (works on older Node builds)
        if ($address === '' || $latitude === '' || $longitude === '') {
            $customersParams = ['page' => 1, 'limit' => 99999];
            if ($zone) {
                $customersParams['zone'] = $zone;
            }
            $customersResponse = $this->nodeApi->get('/admin/customers', $customersParams, 60);
            if (($customersResponse['status'] ?? '') === 'success') {
                $users = $customersResponse['data']['users'] ?? [];
                foreach ($users as $user) {
                    $arr = is_array($user) ? $user : (array) $user;
                    if ((string) ($arr['id'] ?? '') !== (string) $id) {
                        continue;
                    }

                    $customer = $arr['customer'] ?? [];
                    $customerArr = is_array($customer) ? $customer : (array) $customer;
                    $customerRecordId = $customerArr['id'] ?? null;

                    if ($address === '') {
                        $address = trim((string) ($arr['address'] ?? ($customerArr['address'] ?? ($arr['location'] ?? ($customerArr['location'] ?? '')))));
                    }

                    $latLogRaw = $arr['lat_log'] ?? ($arr['latlng'] ?? ($arr['location_lat_lng'] ?? ($customerArr['lat_log'] ?? '')));
                    if (is_string($latLogRaw) && strpos($latLogRaw, ',') !== false) {
                        $parts = array_map('trim', explode(',', $latLogRaw));
                        if (count($parts) >= 2) {
                            if ($latitude === '') $latitude = (string) $parts[0];
                            if ($longitude === '') $longitude = (string) $parts[1];
                        }
                    }

                    if ($latitude === '') {
                        $latitude = trim((string) ($arr['latitude'] ?? ($arr['lat'] ?? ($customerArr['latitude'] ?? ($customerArr['lat'] ?? '')))));
                    }
                    if ($longitude === '') {
                        $longitude = trim((string) ($arr['longitude'] ?? ($arr['lng'] ?? ($arr['long'] ?? ($customerArr['longitude'] ?? ($customerArr['lng'] ?? ($customerArr['long'] ?? '')))))));
                    }
                    break;
                }
            }
        }

        // Fallback 2: use v2 saved addresses API (works for address book entries)
        if ($address === '' || $latitude === '' || $longitude === '') {
            $addressLookupIds = array_values(array_unique(array_filter([
                $customerRecordId ? (string) $customerRecordId : null,
                (string) $id,
            ])));

            foreach ($addressLookupIds as $lookupId) {
                $addressResponse = $this->nodeApi->get('/v2/addresses/customer/' . $lookupId, [], 20);
                if (($addressResponse['status'] ?? '') !== 'success' || empty($addressResponse['data']) || !is_array($addressResponse['data'])) {
                    continue;
                }

                $savedAddresses = $addressResponse['data'];
                $selectedAddress = $savedAddresses[0] ?? [];
                $addr = is_array($selectedAddress) ? $selectedAddress : (array) $selectedAddress;

                if ($address === '') {
                    $address = trim((string) ($addr['address'] ?? ($addr['full_address'] ?? ($addr['location'] ?? ''))));
                }

                if ($latitude === '' || $longitude === '') {
                    $addrLatLog = (string) ($addr['lat_log'] ?? '');
                    if ($addrLatLog !== '' && strpos($addrLatLog, ',') !== false) {
                        $parts = array_map('trim', explode(',', $addrLatLog));
                        if (count($parts) >= 2) {
                            if ($latitude === '') $latitude = (string) $parts[0];
                            if ($longitude === '') $longitude = (string) $parts[1];
                        }
                    }

                    if ($latitude === '') {
                        $latitude = trim((string) ($addr['latitude'] ?? ($addr['lat'] ?? '')));
                    }
                    if ($longitude === '') {
                        $longitude = trim((string) ($addr['longitude'] ?? ($addr['lng'] ?? ($addr['long'] ?? ''))));
                    }
                }

                if ($address !== '' || ($latitude !== '' && $longitude !== '')) {
                    break;
                }
            }
        }

        // Fallback 3: get from v2 profile (customer profile data)
        if ($address === '' || $latitude === '' || $longitude === '') {
            $profileResponse = $this->nodeApi->get('/v2/profile/' . $id, [], 20);
            if (($profileResponse['status'] ?? '') === 'success' && !empty($profileResponse['data'])) {
                $profile = is_array($profileResponse['data']) ? $profileResponse['data'] : (array) $profileResponse['data'];
                $customerProfile = $profile['customer'] ?? [];
                $customerArr = is_array($customerProfile) ? $customerProfile : (array) $customerProfile;

                if ($address === '') {
                    $address = trim((string) ($customerArr['address'] ?? ($profile['address'] ?? ($customerArr['location'] ?? ($profile['location'] ?? '')))));
                }

                if ($latitude === '' || $longitude === '') {
                    $profileLatLog = (string) ($customerArr['lat_log'] ?? ($profile['lat_log'] ?? ''));
                    if ($profileLatLog !== '' && strpos($profileLatLog, ',') !== false) {
                        $parts = array_map('trim', explode(',', $profileLatLog));
                        if (count($parts) >= 2) {
                            if ($latitude === '') $latitude = (string) $parts[0];
                            if ($longitude === '') $longitude = (string) $parts[1];
                        }
                    }

                    if ($latitude === '') {
                        $latitude = trim((string) ($customerArr['latitude'] ?? ($customerArr['lat'] ?? ($profile['latitude'] ?? ($profile['lat'] ?? '')))));
                    }
                    if ($longitude === '') {
                        $longitude = trim((string) ($customerArr['longitude'] ?? ($customerArr['lng'] ?? ($customerArr['long'] ?? ($profile['longitude'] ?? ($profile['lng'] ?? ($profile['long'] ?? '')))))));
                    }
                }
            }
        }

        // Fallback 4: derive from most recent order (customerdetails/lat_log)
        if ($address === '' || $latitude === '' || $longitude === '') {
            $recentOrdersResponse = $this->nodeApi->get('/customer/recent-orders/' . $id, [], 20);
            if (($recentOrdersResponse['status'] ?? '') === 'success' && !empty($recentOrdersResponse['data']) && is_array($recentOrdersResponse['data'])) {
                $firstOrder = $recentOrdersResponse['data'][0] ?? [];
                $order = is_array($firstOrder) ? $firstOrder : (array) $firstOrder;

                if ($address === '') {
                    $cd = $order['customerdetails'] ?? '';
                    if (is_string($cd)) {
                        $json = json_decode($cd, true);
                        if (is_array($json)) {
                            $address = trim((string) ($json['address'] ?? ($json['customerdetails'] ?? '')));
                        } else {
                            $address = trim($cd);
                        }
                    } elseif (is_array($cd)) {
                        $address = trim((string) ($cd['address'] ?? ($cd['customerdetails'] ?? '')));
                    } elseif (is_object($cd)) {
                        $address = trim((string) ($cd->address ?? ($cd->customerdetails ?? '')));
                    }
                }

                if ($latitude === '' || $longitude === '') {
                    $orderLatLog = (string) ($order['lat_log'] ?? '');
                    if ($orderLatLog !== '' && strpos($orderLatLog, ',') !== false) {
                        $parts = array_map('trim', explode(',', $orderLatLog));
                        if (count($parts) >= 2) {
                            if ($latitude === '') $latitude = (string) $parts[0];
                            if ($longitude === '') $longitude = (string) $parts[1];
                        }
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'msg' => 'Autofill data retrieved',
            'data' => [
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ]);
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
