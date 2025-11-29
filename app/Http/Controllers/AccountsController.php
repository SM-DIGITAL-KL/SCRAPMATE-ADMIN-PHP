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
}
