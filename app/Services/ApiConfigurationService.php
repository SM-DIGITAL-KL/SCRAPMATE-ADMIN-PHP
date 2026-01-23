<?php

namespace App\Services;

use App\Helpers\EnvReader;

/**
 * Centralized API Configuration Service
 * Handles all API endpoint configurations for the entire project
 * This ensures consistent URL management across all applications
 */
class ApiConfigurationService
{
    private static $instance = null;
    private $config;
    
    private function __construct()
    {
        $this->loadConfiguration();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration from environment
     */
    private function loadConfiguration()
    {
        // Node.js API Configuration
        $nodeUrl = EnvReader::get('NODE_URL', env('NODE_URL', 'http://localhost:3000'));
        $nodeApiKey = EnvReader::get('NODE_API_KEY', env('NODE_API_KEY', 'your-api-key-here'));
        
        $this->config = [
            'node' => [
                'base_url' => rtrim($nodeUrl, '/'),
                'api_url' => rtrim($nodeUrl, '/') . '/api',
                'api_key' => $nodeApiKey,
                'timeout' => (int) EnvReader::get('NODE_API_TIMEOUT', env('NODE_API_TIMEOUT', 30)),
            ],
            'google' => [
                'api_key' => EnvReader::get('APP_GOOGLE_API_KEY', env('APP_GOOGLE_API_KEY', '')),
                'maps_api_url' => 'https://maps.googleapis.com/maps/api',
            ],
            'sms' => [
                'api_url' => EnvReader::get('SMS_API_URL', env('SMS_API_URL', '')),
                'api_url_new' => EnvReader::get('SMS_API_URL_NEW', env('SMS_API_URL_NEW', '')),
                'entity_id' => EnvReader::get('SMS_API_ENITYID', env('SMS_API_ENITYID', '')),
                'token' => EnvReader::get('SMS_API_TOKEN', env('SMS_API_TOKEN', '')),
                'key' => EnvReader::get('SMS_API_KEY', env('SMS_API_KEY', '')),
            ],
            'firebase' => [
                'credentials' => EnvReader::get('FIREBASE_CREDENTIALS', env('FIREBASE_CREDENTIALS', '')),
            ]
        ];
    }
    
    /**
     * Get Node.js API base URL (without /api)
     */
    public function getNodeBaseUrl()
    {
        return $this->config['node']['base_url'];
    }
    
    /**
     * Get Node.js API URL (with /api)
     */
    public function getNodeApiUrl()
    {
        return $this->config['node']['api_url'];
    }
    
    /**
     * Get Node.js API key
     */
    public function getNodeApiKey()
    {
        return $this->config['node']['api_key'];
    }
    
    /**
     * Get Node.js API timeout
     */
    public function getNodeTimeout()
    {
        return $this->config['node']['timeout'];
    }
    
    /**
     * Get Google Maps API key
     */
    public function getGoogleApiKey()
    {
        return $this->config['google']['api_key'];
    }
    
    /**
     * Get Google Maps API base URL
     */
    public function getGoogleMapsApiUrl()
    {
        return $this->config['google']['maps_api_url'];
    }
    
    /**
     * Get SMS API configuration
     */
    public function getSmsConfig()
    {
        return $this->config['sms'];
    }
    
    /**
     * Get Firebase credentials path
     */
    public function getFirebaseCredentials()
    {
        return $this->config['firebase']['credentials'];
    }
    
    /**
     * Get all configuration
     */
    public function getAllConfig()
    {
        return $this->config;
    }
    
    /**
     * Refresh configuration (useful for testing)
     */
    public function refresh()
    {
        $this->loadConfiguration();
    }
    
    /**
     * Get configuration for a specific service
     */
    public function getServiceConfig($service)
    {
        return $this->config[$service] ?? null;
    }
}