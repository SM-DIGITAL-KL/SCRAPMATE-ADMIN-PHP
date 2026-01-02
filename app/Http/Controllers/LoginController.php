<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\NodeApiService;
use App\Helpers\EnvReader;

// use App\Models\User;

class LoginController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function login()
    {
        // Check if user is already logged in via session
        if (session()->has('user_id')) {
            $userType = session('user_type');
            if ($userType == 'A' || $userType == 'U') {
                return Redirect::to('/admin/dashboard');
            }
        }
        return view('login'); 
    }
    
    public function dologin(Request $request)
    {
        // validate the info, create rules for the inputs
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $res = [];
        
        // Call Node.js API for authentication
        // Note: Login endpoint is at root level, not under /api
        try {
            // Read from env.txt first, fallback to .env, then env() helper
            // NODE_URL should be the base server URL (AWS Lambda Function URL)
            // Default to production Lambda Function URL if not configured
            $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'https://gpn6vt3mlkm6zq7ibxdtu6bphi0onexr.lambda-url.ap-south-1.on.aws'));
            $loginUrl = rtrim($nodeUrl, '/') . '/dologin';
            
            // Get API key from environment (read from env.txt)
            $apiKey = EnvReader::get('NODE_API_KEY', env('NODE_API_KEY', 'your-api-key-here'));
            
            // Debug: Log what we're reading from env files
            $envTxtExists = file_exists(base_path('env.txt'));
            $envExists = file_exists(base_path('.env'));
            
            // Read directly from files for debugging
            $envTxtValue = null;
            if ($envTxtExists) {
                $lines = file(base_path('env.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, 'NODE_URL=') === 0) {
                        $parts = explode('=', $line, 2);
                        if (count($parts) === 2) {
                            $envTxtValue = trim(trim($parts[1]), '"\'');
                            break;
                        }
                    }
                }
            }
            
            $envValue = null;
            if ($envExists) {
                $lines = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, 'NODE_URL=') === 0) {
                        $parts = explode('=', $line, 2);
                        if (count($parts) === 2) {
                            $envValue = trim(trim($parts[1]), '"\'');
                            break;
                        }
                    }
                }
            }
            
            Log::info('Login API Request', [
                'method' => 'POST',
                'url' => $loginUrl,
                'email' => $request['email'],
                'has_password' => !empty($request['password']),
                'has_api_key' => !empty($apiKey),
                'api_key_preview' => !empty($apiKey) ? substr($apiKey, 0, 10) . '...' : 'empty',
                'node_url_source' => $nodeUrl,
                'env_txt_exists' => $envTxtExists,
                'env_exists' => $envExists,
                'env_txt_value' => $envTxtValue,
                'env_value' => $envValue
            ]);
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'api-key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->timeout(30)->post($loginUrl, [
                'email' => $request['email'],
                'password' => $request['password']
            ]);
            
            $statusCode = $response->status();
            $apiResponse = $response->json();
            
            // Extract cookies from response - Lambda uses session cookies for authentication
            $responseCookies = $response->cookies()->toArray();
            $cookieHeader = $response->header('Set-Cookie');
            
            // Check if response is successful
            // Lambda returns: {msg: 'success', status: 'success'} on success
            // Lambda returns: {msg: 'invalid', status: 'error'} on failure
            $isSuccess = $response->successful() && 
                        isset($apiResponse['status']) && 
                        $apiResponse['status'] === 'success' && 
                        isset($apiResponse['msg']) && 
                        $apiResponse['msg'] === 'success';
            
            Log::info('Login API Response Check', [
                'status_code' => $statusCode,
                'response_successful' => $response->successful(),
                'api_status' => $apiResponse['status'] ?? 'missing',
                'api_msg' => $apiResponse['msg'] ?? 'missing',
                'is_success' => $isSuccess,
                'debug' => $apiResponse['debug'] ?? 'none'
            ]);
            
            if ($isSuccess) {
                Log::info('Login API Success', [
                    'url' => $loginUrl,
                    'status' => $statusCode,
                    'email' => $request['email'],
                    'user_id' => $apiResponse['data']['user']['id'] ?? 'unknown',
                    'has_cookies' => !empty($responseCookies)
                ]);
                
                // Store user data in session
                if (isset($apiResponse['data']['user'])) {
                    $user = $apiResponse['data']['user'];
                    session([
                        'user_id' => $user['id'],
                        'user_email' => $user['email'],
                        'user_name' => $user['name'],
                        'user_type' => $user['user_type']
                    ]);
                    
                    // Explicitly save the session to ensure it persists
                    session()->save();
                    
                    Log::info('✅ Session saved after login', [
                        'user_id' => $user['id'],
                        'user_email' => $user['email'],
                        'user_type' => $user['user_type'],
                        'session_id' => session()->getId(),
                        'has_user_id' => session()->has('user_id'),
                        'has_user_email' => session()->has('user_email')
                    ]);
                } else {
                    // Fallback: store basic info from email
                    session([
                        'user_email' => $request['email'],
                        'user_type' => 'A' // Default to admin if not specified
                    ]);
                    session()->save();
                    Log::info('✅ Session saved (fallback)', [
                        'user_email' => $request['email'],
                        'session_id' => session()->getId()
                    ]);
                }
                
                // Store authentication token if provided
                if (isset($apiResponse['data']['token'])) {
                    session(['api_token' => $apiResponse['data']['token']]);
                    session()->save();
                    Log::info('API token stored in session');
                }
                
                // Store cookies for subsequent API calls (Lambda uses session-based auth)
                if (!empty($responseCookies)) {
                    session(['api_cookies' => $responseCookies]);
                    Log::info('API cookies stored in session', ['cookie_count' => count($responseCookies)]);
                } elseif ($cookieHeader) {
                    // Fallback: parse cookie header string
                    $cookies = [];
                    if (is_array($cookieHeader)) {
                        $cookies = $cookieHeader;
                    } else {
                        $cookies[] = $cookieHeader;
                    }
                    
                    // Parse cookie strings to name=value format
                    $parsedCookies = [];
                    foreach ($cookies as $cookie) {
                        if (is_string($cookie) && strpos($cookie, '=') !== false) {
                            $parts = explode(';', $cookie);
                            $nameValue = trim($parts[0]);
                            if (strpos($nameValue, '=') !== false) {
                                list($name, $value) = explode('=', $nameValue, 2);
                                $parsedCookies[] = ['name' => trim($name), 'value' => trim($value)];
                            }
                        }
                    }
                    
                    if (!empty($parsedCookies)) {
                        session(['api_cookies' => $parsedCookies]);
                        Log::info('API cookies parsed and stored', ['cookie_count' => count($parsedCookies)]);
                    }
                }
                
                $res['msg'] = 'success';
            } else {
                $errorDetails = [
                    'method' => 'POST',
                    'url' => $loginUrl,
                    'status_code' => $statusCode,
                    'response_body' => $response->body(),
                    'response_json' => $apiResponse,
                    'email' => $request['email'],
                    'node_url_used' => $nodeUrl,
                    'api_key_used' => !empty($apiKey) ? substr($apiKey, 0, 10) . '...' : 'empty',
                    'response_headers' => $response->headers()
                ];
                
                Log::error('❌ Login API Error - Invalid Credentials or Failed Response', $errorDetails);
                error_log('❌ Login API Error: ' . json_encode($errorDetails, JSON_PRETTY_PRINT));
                
                // Return more detailed error for debugging
                $res['msg'] = 'invalid';
                $res['status'] = 'error';
                
                // Include debug info if available from Lambda
                if (isset($apiResponse['debug'])) {
                    $res['debug'] = $apiResponse['debug'];
                    Log::error('Lambda API Debug Info', ['debug' => $apiResponse['debug']]);
                }
                
                // Include debug info in development mode
                if (config('app.debug')) {
                    $res['debug_details'] = [
                        'status_code' => $statusCode,
                        'url' => $loginUrl,
                        'api_response' => $apiResponse,
                        'node_url_used' => $nodeUrl
                    ];
                }
            }
        } catch (\Exception $e) {
            // Default to production Lambda Function URL if not configured
            $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'https://gpn6vt3mlkm6zq7ibxdtu6bphi0onexr.lambda-url.ap-south-1.on.aws'));
            $loginUrl = rtrim($nodeUrl, '/') . '/dologin';
            
            $errorDetails = [
                'method' => 'POST',
                'url' => $loginUrl,
                'email' => $request['email'],
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ];
            
            Log::error('❌ Login API Exception', $errorDetails);
            error_log('❌ Login API Exception: ' . json_encode($errorDetails, JSON_PRETTY_PRINT));
            
            $res['msg'] = 'invalid';
        }
        
        return $res;
    }

    public function logout()
    {
        // Clear session data
        session()->forget(['user_id', 'user_email', 'user_name', 'user_type']);
        session()->flush();
        return redirect('/login');
    }
    // public function updatepss($newPassword)
    // {
    //     $password = Hash::make($newPassword);
    //     echo $password;
    // }
}
