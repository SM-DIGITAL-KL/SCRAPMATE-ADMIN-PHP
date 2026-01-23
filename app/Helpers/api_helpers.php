<?php

if (!function_exists('get_api_config')) {
    /**
     * Get API configuration - centralized function for all projects
     * This function provides easy access to API configurations from anywhere
     * 
     * @param string|null $service Service name (node, google, sms, firebase)
     * @param string|null $key Specific configuration key
     * @return mixed Configuration value or array
     */
    function get_api_config($service = null, $key = null)
    {
        $apiConfig = \App\Services\ApiConfigurationService::getInstance();
        
        if ($service === null) {
            return $apiConfig->getAllConfig();
        }
        
        if ($key === null) {
            return $apiConfig->getServiceConfig($service);
        }
        
        // Return specific configuration value
        switch ($service) {
            case 'node':
                switch ($key) {
                    case 'base_url':
                        return $apiConfig->getNodeBaseUrl();
                    case 'api_url':
                        return $apiConfig->getNodeApiUrl();
                    case 'api_key':
                        return $apiConfig->getNodeApiKey();
                    case 'timeout':
                        return $apiConfig->getNodeTimeout();
                }
                break;
                
            case 'google':
                switch ($key) {
                    case 'api_key':
                        return $apiConfig->getGoogleApiKey();
                    case 'maps_api_url':
                        return $apiConfig->getGoogleMapsApiUrl();
                }
                break;
                
            case 'sms':
                $smsConfig = $apiConfig->getSmsConfig();
                return $smsConfig[$key] ?? null;
                
            case 'firebase':
                switch ($key) {
                    case 'credentials':
                        return $apiConfig->getFirebaseCredentials();
                }
                break;
        }
        
        return null;
    }
}

if (!function_exists('get_node_api_url')) {
    /**
     * Get Node.js API base URL
     * 
     * @return string Node.js API base URL
     */
    function get_node_api_url()
    {
        return get_api_config('node', 'base_url');
    }
}

if (!function_exists('get_node_api_endpoint')) {
    /**
     * Get full Node.js API endpoint URL
     * 
     * @param string $endpoint API endpoint (e.g., '/users', '/products')
     * @return string Full API URL
     */
    function get_node_api_endpoint($endpoint = '')
    {
        $baseUrl = get_api_config('node', 'api_url');
        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }
}