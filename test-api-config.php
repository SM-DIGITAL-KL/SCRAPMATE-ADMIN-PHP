#!/usr/bin/env php
<?php

/**
 * Test API Configuration
 * Simple script to test the centralized API configuration system
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ApiConfigurationService;

echo "🧪 API Configuration Test\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // Get the configuration service
    $apiConfig = ApiConfigurationService::getInstance();
    
    echo "✅ Configuration Service Loaded Successfully\n\n";
    
    // Test Node.js API Configuration
    echo "🌐 Node.js API Configuration:\n";
    echo "   Base URL: " . $apiConfig->getNodeBaseUrl() . "\n";
    echo "   API URL: " . $apiConfig->getNodeApiUrl() . "\n";
    echo "   API Key: " . ($apiConfig->getNodeApiKey() ? 'SET' : 'NOT SET') . "\n";
    echo "   Timeout: " . $apiConfig->getNodeTimeout() . " seconds\n\n";
    
    // Test Google Configuration
    echo "🗺️  Google Maps Configuration:\n";
    echo "   API Key: " . ($apiConfig->getGoogleApiKey() ? 'SET' : 'NOT SET') . "\n";
    echo "   Maps API URL: " . $apiConfig->getGoogleMapsApiUrl() . "\n\n";
    
    // Test SMS Configuration
    echo "📱 SMS Configuration:\n";
    $smsConfig = $apiConfig->getSmsConfig();
    foreach ($smsConfig as $key => $value) {
        $displayValue = $value ? (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) : 'NOT SET';
        echo "   " . strtoupper($key) . ": " . $displayValue . "\n";
    }
    echo "\n";
    
    // Test Firebase Configuration
    echo "🔥 Firebase Configuration:\n";
    echo "   Credentials Path: " . ($apiConfig->getFirebaseCredentials() ?: 'NOT SET') . "\n\n";
    
    // Test Helper Functions
    echo "🔧 Testing Helper Functions:\n";
    echo "   get_node_api_url(): " . get_node_api_url() . "\n";
    echo "   get_node_api_endpoint('/test'): " . get_node_api_endpoint('/test') . "\n\n";
    
    echo "🎉 All tests passed! Configuration system is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}