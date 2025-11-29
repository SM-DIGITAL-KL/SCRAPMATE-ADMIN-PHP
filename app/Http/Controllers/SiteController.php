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

use App\Models\AdminProfile;
use App\Models\file_upload;
use App\Models\Pushsms;

use App\Services\NodeApiService;

class SiteController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function manage_site(Request $request)
    {
        if ($request->isMethod('post')) {
            Log::info('🔵 SiteController::manage_site - POST request (updating site)');
            
            // Handle logo upload in Laravel (file handling)
            $logoUrl = null;
            if(!empty($request->file('logo'))){
                Log::info('🔵 Logo file uploaded, processing...');
                $data = AdminProfile::find(1);
                $logo = $request->file('logo');
                $path = public_path('assets/images/logo/');
                if ($data && file_exists($path . basename($data->logo))) {
                    unlink($path . basename($data->logo));
                }
                $filename = file_upload::upload_image($logo, $path);
                $logoUrl = url("/assets/images/logo/$filename");
                Log::info('✅ Logo uploaded successfully', ['filename' => $filename, 'url' => $logoUrl]);
            }
            
            // Prepare API data
            $apiData = [
                'name' => $request->name,
                'email' => $request->email,
                'contact' => $request->contact,
                'address' => $request->address,
                'location' => $request->location
            ];
            
            if ($logoUrl) {
                $apiData['logo'] = $logoUrl;
            }
            
            Log::info('🔵 Calling Node.js API: /site (PUT)', ['data' => array_merge($apiData, ['logo' => $logoUrl ? 'provided' : 'not provided'])]);
            $apiResponse = $this->nodeApi->put('/site', $apiData);
            
            Log::info('🔵 Node.js API Response for updateSite', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'response' => $apiResponse
            ]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ manage_site: Site updated successfully');
                return Redirect::to('/manage_site')->with('success', 'Updated successfully!');
            } else {
                Log::error('❌ manage_site: Failed to update site', ['response' => $apiResponse]);
                return Redirect::to('/manage_site')->with('error', $apiResponse['msg'] ?? 'Failed to update site');
            }
        }

        Log::info('🔵 SiteController::manage_site - GET request (loading page)');
        $apiResponse = $this->nodeApi->get('/site');
        
        Log::info('🔵 Node.js API Response for getSite', [
            'status' => $apiResponse['status'] ?? 'unknown',
            'hasData' => isset($apiResponse['data']),
            'hasProfile' => isset($apiResponse['data']['profile']),
            'response' => $apiResponse
        ]);
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
            $data = $apiResponse['data'];
            // Convert profile array to object if needed
            if (isset($data['profile']) && is_array($data['profile'])) {
                $data['profile'] = (object)$data['profile'];
            }
            Log::info('✅ manage_site: Successfully retrieved site data');
        } else {
            Log::error('❌ Node API failed for manage_site', ['response' => $apiResponse]);
            $data = [
                'profile' => null,
                'pagename' => 'Manage Site'
            ];
        }
        
        return view('site/manage_site', $data);
    }
    
    public function updateAppVersion(Request $request)
    {
        if ($request->isMethod('post')) {
            Log::info('🔵 SiteController::updateAppVersion - POST request');
            $apiData = [
                'version' => $request->version
            ];
            
            Log::info('🔵 Calling Node.js API: /site/app-version (PUT)', ['version' => $request->version]);
            $apiResponse = $this->nodeApi->put('/site/app-version', $apiData);
            
            Log::info('🔵 Node.js API Response for updateAppVersion', [
                'status' => $apiResponse['status'] ?? 'unknown',
                'response' => $apiResponse
            ]);
            
            if ($apiResponse['status'] === 'success') {
                Log::info('✅ updateAppVersion: App version updated successfully');
                return Redirect::to('/manage_site')->with('success', 'Updated successfully!');
            } else {
                Log::error('❌ updateAppVersion: Failed to update app version', ['response' => $apiResponse]);
                return Redirect::to('/manage_site')->with('error', $apiResponse['msg'] ?? 'Failed to update app version');
            }
        }
        
        Log::info('🔵 SiteController::updateAppVersion - GET request (loading modal)');
        $apiResponse = $this->nodeApi->get('/site/app-version');
        
        if ($apiResponse['status'] === 'success' && isset($apiResponse['data']['appVersion'])) {
            $appVersion = $apiResponse['data']['appVersion'];
        } else {
            Log::error('❌ Node API failed for getAppVersion', ['response' => $apiResponse]);
            $appVersion = '1.0.0';
        }
        
        $display = '<div class="card-body">
            <div class="form-validation">
                    <form action="'. route('updateAppVersion') .'" method="POST" class="needs-validation" validate>
                    ' . csrf_field() . '
                        <div class="row">
                            <label class="form-label" for="validationCustom01">Version<span class="text-danger">*</span></label>
                            <div class="col-lg-8">
                                <input type="text" name="version" class="form-control" id="validationCustom01" value="' . $appVersion . '" required>
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
}
