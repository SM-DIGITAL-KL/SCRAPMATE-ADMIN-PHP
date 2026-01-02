<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\EnvReader;

class NodeApiService
{
    private $baseUrl;
    private $apiKey;
    private $cacheEnabled;
    private $cacheTtl; // Time to live in minutes

    public function __construct()
    {
        // Read from env.txt first, fallback to .env, then env() helper
        // NODE_URL should be the base server URL (AWS Lambda Function URL)
        // We append /api to it for API endpoints
        // Production Lambda Function URL
        $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'https://gpn6vt3mlkm6zq7ibxdtu6bphi0onexr.lambda-url.ap-south-1.on.aws'));
        
        // Local development URL (commented - use Lambda URL instead)
        // $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'http://localhost:3000'));
        $this->baseUrl = rtrim($nodeUrl, '/') . '/api';
        $this->apiKey = EnvReader::get('NODE_API_KEY', env('NODE_API_KEY', 'your-api-key-here'));
        $this->cacheEnabled = EnvReader::get('API_CACHE_ENABLED', env('API_CACHE_ENABLED', true));
        $this->cacheTtl = EnvReader::get('API_CACHE_TTL', env('API_CACHE_TTL', 30)); // Default 30 minutes
    }
    
    /**
     * Generate a cache key based on endpoint and parameters
     */
    private function getCacheKey($endpoint, $params = [])
    {
        $key = 'node_api:' . md5($endpoint . serialize($params));
        
        // Include user ID in cache key if available (for user-specific data)
        if (session()->has('user_id')) {
            $key .= ':user_' . session('user_id');
        }
        
        return $key;
    }
    
    /**
     * Clear cache for a specific endpoint pattern
     * This clears both PHP cache and Node.js Redis cache
     */
    public function clearCache($endpointPattern = null)
    {
        if (!$this->cacheEnabled) {
            return;
        }
        
        // Clear PHP cache first
        if ($endpointPattern) {
            // Clear PHP cache for specific endpoint pattern
            $pattern = 'node_api:' . md5($endpointPattern);
            Cache::forget($pattern);
            
            // Also clear all PHP cache to be safe (for now)
            Cache::flush();
            
            Log::info('PHP cache cleared for pattern', ['pattern' => $endpointPattern]);
        } else {
            // Clear all PHP API cache
            Cache::flush();
            Log::info('All PHP API cache cleared');
        }
        
        // Clear Node.js Redis cache by calling the backend API
        try {
            $headers = $this->getAuthHeaders();
            
            // Determine which cache keys to clear based on endpoint pattern
            $keysToDelete = [];
            
            if ($endpointPattern) {
                // Map endpoint patterns to specific cache keys
                // Note: Cache keys must match the format used in Node.js RedisCache.listKey()
                if (strpos($endpointPattern, 'category_img_list') !== false || strpos($endpointPattern, 'category') !== false) {
                    // Clear category-related cache keys
                    // Format: list:{type}:{param1}:{param2}:...
                    $keysToDelete[] = 'list:category_img_list:version:s3';
                    $keysToDelete[] = 'list:subcategories_grouped';
                }
                
                if (strpos($endpointPattern, 'subcategories') !== false) {
                    $keysToDelete[] = 'list:subcategories_grouped';
                }
                
                // Clear paid subscriptions cache
                if (strpos($endpointPattern, 'paid-subscriptions') !== false || strpos($endpointPattern, 'paidSubscriptions') !== false) {
                    // Format: list:paid_subscriptions
                    $keysToDelete[] = 'list:paid_subscriptions';
                }
            }
            
            // Call Node.js backend to clear Redis cache
            $clearCacheUrl = $this->baseUrl . '/clear_redis_cache';
            
            if (!empty($keysToDelete)) {
                // Delete specific keys
                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->post($clearCacheUrl, ['keys' => $keysToDelete]);
            } else {
                // Use type-based clearing
                $cacheType = 'list'; // Default to list type for category endpoints
                if ($endpointPattern) {
                    if (strpos($endpointPattern, 'dashboard') !== false) {
                        $cacheType = 'dashboard';
                    }
                }
                
                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->post($clearCacheUrl, ['type' => $cacheType]);
            }
            
            if ($response->successful()) {
                Log::info('Node.js Redis cache cleared successfully', [
                    'pattern' => $endpointPattern,
                    'keys_deleted' => !empty($keysToDelete) ? $keysToDelete : 'type-based'
                ]);
            } else {
                Log::warning('Failed to clear Node.js Redis cache', [
                    'pattern' => $endpointPattern,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail - PHP cache is already cleared
            Log::warning('Error calling Node.js cache clear endpoint', [
                'pattern' => $endpointPattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get authentication headers for API requests
     */
    private function getAuthHeaders()
    {
        $headers = [
            'api-key' => $this->apiKey,
            'Accept' => 'application/json',
        ];
        
        // Add authentication token if available
        if (session()->has('api_token')) {
            $token = session('api_token');
            $headers['Authorization'] = 'Bearer ' . $token;
            $headers['X-Auth-Token'] = $token;
        }
        
        // Add user session info if available (for Lambda authentication)
        if (session()->has('user_id')) {
            $headers['X-User-ID'] = session('user_id');
            $headers['X-User-Email'] = session('user_email', '');
            $headers['X-User-Type'] = session('user_type', '');
        }
        
        // Add Cookie header if we have stored cookies (Lambda uses session-based auth)
        $cookieHeader = $this->buildCookieHeader();
        if ($cookieHeader) {
            $headers['Cookie'] = $cookieHeader;
        }
        
        return $headers;
    }
    
    /**
     * Build Cookie header string from stored cookies
     */
    private function buildCookieHeader()
    {
        if (!session()->has('api_cookies')) {
            return null;
        }
        
        $storedCookies = session('api_cookies');
        $cookieStrings = [];
        
        if (is_array($storedCookies)) {
            foreach ($storedCookies as $cookie) {
                if (is_array($cookie)) {
                    // Handle array format: ['name' => 'value'] or ['Name' => '...', 'Value' => '...']
                    if (isset($cookie['name']) && isset($cookie['value'])) {
                        $cookieStrings[] = $cookie['name'] . '=' . $cookie['value'];
                    } elseif (isset($cookie['Name']) && isset($cookie['Value'])) {
                        $cookieStrings[] = $cookie['Name'] . '=' . $cookie['Value'];
                    }
                } elseif (is_string($cookie)) {
                    // If it's already a cookie string, extract name=value part
                    if (strpos($cookie, '=') !== false) {
                        $parts = explode(';', $cookie);
                        $nameValue = trim($parts[0]);
                        if (strpos($nameValue, '=') !== false) {
                            $cookieStrings[] = $nameValue;
                        }
                    }
                }
            }
        }
        
        return !empty($cookieStrings) ? implode('; ', $cookieStrings) : null;
    }
    

    /**
     * Make a GET request to Node.js API
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param int $timeout Timeout in seconds (default: 10)
     */
    public function get($endpoint, $params = [], $timeout = 10)
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'GET';
        $cacheKey = $this->getCacheKey($endpoint, $params);
        
        // Check cache first
        if ($this->cacheEnabled) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData !== null) {
                // Reduced logging for performance
                return $cachedData;
            }
        }
        
        try {
            $headers = $this->getAuthHeaders();
            
            // Reduced logging for performance - only log errors

            // Use configurable timeout
            $response = Http::withHeaders($headers)->timeout($timeout)->get($fullUrl, $params);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                // Cache successful responses
                if ($this->cacheEnabled && isset($responseData['status']) && $responseData['status'] === 'success') {
                    Cache::put($cacheKey, $responseData, now()->addMinutes($this->cacheTtl));
                }
                
                return $responseData;
            }

            // Log detailed error information
            $headersForLog = [];
            foreach ($headers as $key => $value) {
                if ($key === 'api-key' || $key === 'Cookie') {
                    $headersForLog[$key] = substr($value, 0, 50) . '...';
                } elseif (strpos(strtolower($key), 'auth') !== false || strpos(strtolower($key), 'token') !== false) {
                    $headersForLog[$key] = substr($value, 0, 20) . '...';
                } else {
                    $headersForLog[$key] = $value;
                }
            }
            
            $errorDetails = [
                'method' => $method,
                'url' => $fullUrl,
                'endpoint' => $endpoint,
                'params' => $params,
                'status_code' => $statusCode,
                'response_body' => $response->body(),
                'response_json' => $responseData,
                'headers_sent' => $headersForLog,
                'has_cookie_header' => isset($headers['Cookie'])
            ];

            Log::error('❌ Node API GET Error', $errorDetails);
            error_log('❌ API GET Error: ' . json_encode($errorDetails, JSON_PRETTY_PRINT));

            return [
                'status' => 'error',
                'msg' => 'API request failed',
                'data' => null
            ];
        } catch (\Exception $e) {
            $errorDetails = [
                'method' => $method,
                'url' => $fullUrl,
                'endpoint' => $endpoint,
                'params' => $params,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ];

            Log::error('❌ Node API GET Exception', $errorDetails);
            error_log('❌ API GET Exception: ' . json_encode($errorDetails, JSON_PRETTY_PRINT));

            return [
                'status' => 'error',
                'msg' => 'API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Make a POST request to Node.js API
     */
    public function post($endpoint, $data = [])
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'POST';
        
        try {
            $headers = $this->getAuthHeaders();
            $headers['Content-Type'] = 'application/json';
            
            // Reduced logging for performance
            $response = Http::withHeaders($headers)->timeout(10)->post($fullUrl, $data);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                // Handle null or empty response data
                if ($responseData === null || !is_array($responseData)) {
                    Log::warning('⚠️ Node API POST returned null or non-array response', [
                        'endpoint' => $endpoint,
                        'status_code' => $statusCode,
                        'response_body' => $response->body()
                    ]);
                    return [
                        'status' => 'error',
                        'msg' => 'Invalid response format from API',
                        'data' => null
                    ];
                }
                
                // Invalidate related cache on successful POST (data modification)
                if ($this->cacheEnabled) {
                    $this->invalidateRelatedCache($endpoint);
                }
                return $responseData;
            }

            // Only log errors, not successful requests
            Log::error('❌ Node API POST Error', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown'
            ]);

            return [
                'status' => 'error',
                'msg' => 'API request failed',
                'data' => null
            ];
        } catch (\Exception $e) {
            // Only log essential error info
            Log::error('❌ Node API POST Exception', [
                'endpoint' => $endpoint,
                'error_message' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'msg' => 'API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Make a POST request with multipart form data (for file uploads)
     */
    public function postMultipart($endpoint, $data = [], $fileField = null, $filePath = null, $originalFileName = null)
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'POST';
        $timestamp = now()->toIso8601String();
        
        Log::info('📤 [ADMIN PANEL] Starting POST Multipart Request to Node.js API', [
            'timestamp' => $timestamp,
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'has_file' => !empty($fileField) && !empty($filePath),
            'file_field' => $fileField,
            'file_path' => $filePath,
            'file_exists' => $filePath ? file_exists($filePath) : false,
            'file_size' => $filePath && file_exists($filePath) ? filesize($filePath) : 0,
            'data_keys' => array_keys($data),
            'data' => $data
        ]);
        
        try {
            $headers = $this->getAuthHeaders();
            // Don't set Content-Type for multipart - Laravel will set it automatically
            
            $request = Http::withHeaders($headers)->timeout(30);
            
            // Prepare multipart form data
            $multipartData = [];
            
            // Add regular form fields first
            foreach ($data as $key => $value) {
                $multipartData[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
            
            // Add file if provided
            if ($fileField && $filePath && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                // Use provided original filename, or get from request, or use basename as fallback
                if (!$originalFileName && request()->hasFile($fileField)) {
                    $originalFileName = request()->file($fileField)->getClientOriginalName();
                }
                if (!$originalFileName) {
                    $originalFileName = basename($filePath);
                }
                $fileContent = file_get_contents($filePath);
                
                Log::info('📎 [ADMIN PANEL] Preparing file for multipart request', [
                    'file_field' => $fileField,
                    'original_file_name' => $originalFileName,
                    'file_path' => $filePath,
                    'file_size_bytes' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    'file_exists' => file_exists($filePath),
                    'content_size' => strlen($fileContent)
                ]);
                
                // Add file to multipart data
                $multipartData[] = [
                    'name' => $fileField,
                    'contents' => $fileContent,
                    'filename' => $originalFileName
                ];
                
                Log::info('✅ [ADMIN PANEL] File added to multipart data', [
                    'file_field' => $fileField,
                    'file_name' => $originalFileName,
                    'file_size' => $fileSize
                ]);
            }
            
            Log::info('🚀 [ADMIN PANEL] Sending POST multipart request to Node.js API...', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'multipart_fields_count' => count($multipartData),
                'has_file' => !empty($fileField) && !empty($filePath)
            ]);
            
            // Use asMultipart() for proper multipart/form-data handling
            $requestStartTime = microtime(true);
            $response = $request->asMultipart()->post($fullUrl, $multipartData);
            $requestDuration = round((microtime(true) - $requestStartTime) * 1000, 2);
            
            $statusCode = $response->status();
            $responseBody = $response->body();
            
            // Try to parse JSON response - handle both success and error cases
            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $jsonErr) {
                Log::error('❌ [ADMIN PANEL] Failed to parse JSON response', [
                    'status_code' => $statusCode,
                    'response_body_preview' => substr($responseBody, 0, 500),
                    'json_error' => $jsonErr->getMessage()
                ]);
                $responseData = [
                    'status' => 'error',
                    'msg' => 'Invalid response from API: ' . $jsonErr->getMessage()
                ];
            }
            
            Log::info('📥 [ADMIN PANEL] Response received from Node.js API', [
                'duration_ms' => $requestDuration,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown',
                'response_msg' => $responseData['msg'] ?? 'N/A',
                'has_data' => isset($responseData['data']),
                'response_body_preview' => substr($responseBody, 0, 200)
            ]);

            if ($response->successful()) {
                Log::info('✅ [ADMIN PANEL] POST Multipart request successful', [
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'response_status' => $responseData['status'] ?? 'unknown',
                    'response_msg' => $responseData['msg'] ?? 'N/A',
                    'has_data' => isset($responseData['data']),
                    'response_data_keys' => isset($responseData['data']) ? array_keys($responseData['data']) : [],
                    'category_img_url' => isset($responseData['data']['category_img']) 
                        ? substr($responseData['data']['category_img'], 0, 100) . '...' 
                        : (isset($responseData['data']['cat_img']) 
                            ? substr($responseData['data']['cat_img'], 0, 100) . '...' 
                            : 'N/A'),
                    'full_response_data' => $responseData
                ]);

                // Invalidate related cache on successful POST (data modification)
                if ($this->cacheEnabled) {
                    $this->invalidateRelatedCache($endpoint);
                }
                return $responseData;
            }

            // Log errors with more details
            $errorMsg = $responseData['msg'] ?? 'API request failed';
            
            // Try to get more details from response body if available
            $responseBody = $response->body();
            $responseText = is_string($responseBody) ? $responseBody : '';
            
            Log::error('❌ [ADMIN PANEL] Node API POST Multipart Error', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown',
                'response_msg' => $errorMsg,
                'response_data' => $responseData,
                'response_body' => substr($responseText, 0, 500), // First 500 chars of response body
                'has_file' => !empty($fileField) && !empty($filePath)
            ]);

            return [
                'status' => 'error',
                'msg' => $errorMsg,
                'data' => null
            ];
        } catch (\Exception $e) {
            // Log exception with full details
            $errorMsg = $e->getMessage();
            $isConnectionError = strpos(strtolower($errorMsg), 'connection') !== false || 
                                 strpos(strtolower($errorMsg), 'timeout') !== false ||
                                 strpos(strtolower($errorMsg), 'refused') !== false;
            
            Log::error('❌ [ADMIN PANEL] Node API POST Multipart Exception', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'error_message' => $errorMsg,
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'is_connection_error' => $isConnectionError,
                'has_file' => !empty($fileField) && !empty($filePath),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'msg' => 'API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Make a PUT request to Node.js API
     */
    public function put($endpoint, $data = [])
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'PUT';
        
        try {
            $headers = $this->getAuthHeaders();
            $headers['Content-Type'] = 'application/json';
            
            // Reduced logging for performance
            $response = Http::withHeaders($headers)->timeout(10)->put($fullUrl, $data);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                // Invalidate related cache on successful PUT (data modification)
                if ($this->cacheEnabled) {
                    $this->invalidateRelatedCache($endpoint);
                }
                return $responseData;
            }

            // Only log errors
            Log::error('❌ Node API PUT Error', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown'
            ]);

            return [
                'status' => 'error',
                'msg' => 'API request failed',
                'data' => null
            ];
        } catch (\Exception $e) {
            // Only log essential error info
            Log::error('❌ Node API PUT Exception', [
                'endpoint' => $endpoint,
                'error_message' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'msg' => 'API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Make a PUT request with multipart form data (for file uploads)
     */
    public function putMultipart($endpoint, $data = [], $fileField = null, $filePath = null, $originalFileName = null)
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'PUT';
        $timestamp = now()->toIso8601String();
        
        Log::info('📤 [ADMIN PANEL] Starting PUT Multipart Request to Node.js API', [
            'timestamp' => $timestamp,
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'has_file' => !empty($fileField) && !empty($filePath),
            'file_field' => $fileField,
            'file_path' => $filePath,
            'file_exists' => $filePath ? file_exists($filePath) : false,
            'file_size' => $filePath && file_exists($filePath) ? filesize($filePath) : 0,
            'data_keys' => array_keys($data),
            'data' => $data
        ]);
        
        try {
            $headers = $this->getAuthHeaders();
            // Don't set Content-Type for multipart - Laravel will set it automatically
            
            Log::info('📋 [ADMIN PANEL] Request Details', [
                'method' => $method,
                'url' => $fullUrl,
                'headers_count' => count($headers),
                'has_auth_header' => isset($headers['api-key'])
            ]);
            
            $request = Http::withHeaders($headers)->timeout(30);
            
            // Prepare multipart form data
            $multipartData = [];
            
            // Add regular form fields first
            foreach ($data as $key => $value) {
                $multipartData[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
            
            // Add file if provided
            if ($fileField && $filePath && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                // Use provided original filename, or get from request, or use basename as fallback
                if (!$originalFileName && request()->hasFile($fileField)) {
                    $originalFileName = request()->file($fileField)->getClientOriginalName();
                }
                if (!$originalFileName) {
                    $originalFileName = basename($filePath);
                }
                $fileContent = file_get_contents($filePath);
                
                Log::info('📎 [ADMIN PANEL] Preparing file for multipart request', [
                    'file_field' => $fileField,
                    'original_file_name' => $originalFileName,
                    'file_path' => $filePath,
                    'file_size_bytes' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    'file_exists' => file_exists($filePath),
                    'content_size' => strlen($fileContent)
                ]);
                
                // Add file to multipart data
                $multipartData[] = [
                    'name' => $fileField,
                    'contents' => $fileContent,
                    'filename' => $originalFileName
                ];
                
                Log::info('✅ [ADMIN PANEL] File added to multipart data', [
                    'file_field' => $fileField,
                    'file_name' => $originalFileName,
                    'file_size' => $fileSize
                ]);
            } else {
                Log::info('ℹ️  [ADMIN PANEL] No file to attach', [
                    'file_field' => $fileField,
                    'file_path' => $filePath,
                    'file_exists' => $filePath ? file_exists($filePath) : false
                ]);
            }
            
            Log::info('🚀 [ADMIN PANEL] Sending PUT multipart request to Node.js API...', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'multipart_fields_count' => count($multipartData),
                'has_file' => !empty($fileField) && !empty($filePath)
            ]);
            
            // Use asMultipart() for proper multipart/form-data handling
            $requestStartTime = microtime(true);
            $response = $request->asMultipart()->put($fullUrl, $multipartData);
            $requestDuration = round((microtime(true) - $requestStartTime) * 1000, 2);
            
            $statusCode = $response->status();
            $responseBody = $response->body();
            
            // Try to parse JSON response - handle both success and error cases
            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $jsonErr) {
                Log::error('❌ [ADMIN PANEL] Failed to parse JSON response', [
                    'status_code' => $statusCode,
                    'response_body_preview' => substr($responseBody, 0, 500),
                    'json_error' => $jsonErr->getMessage()
                ]);
                $responseData = [
                    'status' => 'error',
                    'msg' => 'Invalid response from API: ' . $jsonErr->getMessage()
                ];
            }
            
            Log::info('📥 [ADMIN PANEL] Response received from Node.js API', [
                'duration_ms' => $requestDuration,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown',
                'response_msg' => $responseData['msg'] ?? 'N/A',
                'has_data' => isset($responseData['data']),
                'response_body_preview' => substr($responseBody, 0, 200)
            ]);

            if ($response->successful()) {
                Log::info('✅ [ADMIN PANEL] PUT Multipart request successful', [
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'response_status' => $responseData['status'] ?? 'unknown',
                    'response_msg' => $responseData['msg'] ?? 'N/A',
                    'has_data' => isset($responseData['data']),
                    'response_data_keys' => isset($responseData['data']) ? array_keys($responseData['data']) : [],
                    'category_img_url' => isset($responseData['data']['category_img']) 
                        ? substr($responseData['data']['category_img'], 0, 100) . '...' 
                        : (isset($responseData['data']['cat_img']) 
                            ? substr($responseData['data']['cat_img'], 0, 100) . '...' 
                            : 'N/A'),
                    'full_response_data' => $responseData
                ]);
                
                // Invalidate related cache on successful PUT (data modification)
                if ($this->cacheEnabled) {
                    $this->invalidateRelatedCache($endpoint);
                }
                return $responseData;
            }

            // Log errors with more details
            $errorMsg = $responseData['msg'] ?? 'API request failed';
            
            // Try to get more details from response body if available
            $responseBody = $response->body();
            $responseText = is_string($responseBody) ? $responseBody : '';
            
            Log::error('❌ [ADMIN PANEL] Node API PUT Multipart Error', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown',
                'response_msg' => $errorMsg,
                'response_data' => $responseData,
                'response_body' => substr($responseText, 0, 500), // First 500 chars of response body
                'has_file' => !empty($fileField) && !empty($filePath)
            ]);

            return [
                'status' => 'error',
                'msg' => $errorMsg, // Return the actual error message from API
                'data' => null
            ];
        } catch (\Exception $e) {
            // Log exception with full details
            $errorMsg = $e->getMessage();
            $isConnectionError = strpos(strtolower($errorMsg), 'connection') !== false || 
                                 strpos(strtolower($errorMsg), 'timeout') !== false ||
                                 strpos(strtolower($errorMsg), 'refused') !== false;
            
            Log::error('❌ [ADMIN PANEL] Node API PUT Multipart Exception', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'error_message' => $errorMsg,
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'is_connection_error' => $isConnectionError,
                'has_file' => !empty($fileField) && !empty($filePath),
                'trace' => $e->getTraceAsString()
            ]);

            // Provide more specific error message
            if ($isConnectionError) {
                $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'https://gpn6vt3mlkm6zq7ibxdtu6bphi0onexr.lambda-url.ap-south-1.on.aws'));
                $errorMsg = "Cannot connect to API server. Please ensure Node.js API is accessible at {$nodeUrl}. Error: {$errorMsg}";
            } elseif (strpos(strtolower($errorMsg), 'upload') !== false || strpos(strtolower($errorMsg), 'image') !== false) {
                $errorMsg = "The category image failed to upload: {$errorMsg}";
            } else {
                $errorMsg = "API connection failed: {$errorMsg}";
            }

            return [
                'status' => 'error',
                'msg' => $errorMsg,
                'data' => null
            ];
        }
    }

    /**
     * Make a DELETE request to Node.js API
     */
    public function delete($endpoint)
    {
        $fullUrl = $this->baseUrl . $endpoint;
        $method = 'DELETE';
        
        try {
            $headers = $this->getAuthHeaders();
            
            // Reduced logging for performance
            $response = Http::withHeaders($headers)->timeout(10)->delete($fullUrl);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                // Invalidate related cache on successful DELETE (data modification)
                if ($this->cacheEnabled) {
                    $this->invalidateRelatedCache($endpoint);
                }
                return $responseData;
            }

            // Only log errors
            Log::error('❌ Node API DELETE Error', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? 'unknown'
            ]);

            return [
                'status' => 'error',
                'msg' => 'API request failed',
                'data' => null
            ];
        } catch (\Exception $e) {
            // Only log essential error info
            Log::error('❌ Node API DELETE Exception', [
                'endpoint' => $endpoint,
                'error_message' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'msg' => 'API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Invalidate cache related to an endpoint
     * This is called after POST/PUT/DELETE operations to ensure data consistency
     */
    private function invalidateRelatedCache($endpoint)
    {
        if (!$this->cacheEnabled) {
            return;
        }
        
        // Extract base path from endpoint (e.g., /customer/list -> /customer)
        $basePath = dirname($endpoint);
        if ($basePath === '.') {
            $basePath = $endpoint;
        }
        
        // Clear cache for all endpoints under the same base path
        // Note: This is a simple implementation. For production, consider using cache tags
        // For now, we'll clear all cache when data is modified (safe but less efficient)
        Cache::flush();
        
        Log::info('Cache invalidated after data modification', [
            'endpoint' => $endpoint,
            'base_path' => $basePath
        ]);
    }
    
    /**
     * Sanitize sensitive data for logging (remove passwords, tokens, etc.)
     */
    private function sanitizeDataForLogging($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveKeys = ['password', 'api_key', 'apiKey', 'token', 'secret', 'authorization', 'auth_token'];
        $sanitized = $data;

        foreach ($sanitized as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $sensitiveKeys)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeDataForLogging($value);
            }
        }

        return $sanitized;
    }
}

