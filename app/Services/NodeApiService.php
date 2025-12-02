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
        // NODE_URL should be the base server URL (monolithic Lambda function URL)
        // We append /api to it for API endpoints
        // Using localhost:3000 for local development
        $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'http://localhost:3000'));
        // Server URL (commented out for local development):
        // $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws'));
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
     */
    public function clearCache($endpointPattern = null)
    {
        if (!$this->cacheEnabled) {
            return;
        }
        
        if ($endpointPattern) {
            // Clear cache for specific endpoint pattern
            $pattern = 'node_api:' . md5($endpointPattern);
            Cache::flush(); // Note: This clears all cache. For production, consider using cache tags if available
            Log::info('Cache cleared for pattern', ['pattern' => $endpointPattern]);
        } else {
            // Clear all API cache
            Cache::flush();
            Log::info('All API cache cleared');
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

