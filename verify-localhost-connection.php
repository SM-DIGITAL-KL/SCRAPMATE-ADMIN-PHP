<?php
/**
 * Verification Script - Check Localhost Connection to Node.js API
 * 
 * Run this script to verify that the admin panel is properly configured
 * to connect to the localhost Node.js API server.
 */

require __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Helpers\EnvReader;
use Illuminate\Support\Facades\Http;
use App\Services\ApiConfigurationService;

// Load API configuration from centralized service
$apiConfig = ApiConfigurationService::getInstance();
$nodeUrl = $apiConfig->getNodeBaseUrl();
$nodeApiKey = $apiConfig->getNodeApiKey();

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔍 LOCALHOST CONNECTION VERIFICATION\n";
echo str_repeat("=", 80) . "\n\n";

// Step 1: Check environment configuration
echo "📋 STEP 1: Checking Environment Configuration\n";
echo str_repeat("-", 80) . "\n";

$nodeUrl = $apiConfig->getNodeBaseUrl();
$nodeApiKey = $apiConfig->getNodeApiKey();

// Display configuration info
echo "   NODE_URL: " . ($nodeUrl ?: 'NOT SET') . "\n";
echo "   NODE_API_KEY: " . ($nodeApiKey ? 'SET (' . substr($nodeApiKey, 0, 10) . '...)' : 'NOT SET') . "\n";

// Check if localhost
if ($nodeUrl && strpos($nodeUrl, 'localhost') !== false) {
    echo "   ✅ Configured for LOCALHOST\n";
} elseif ($nodeUrl && strpos($nodeUrl, '127.0.0.1') !== false) {
    echo "   ✅ Configured for LOCALHOST (127.0.0.1)\n";
} elseif ($nodeUrl) {
    echo "   ⚠️  Configured for: " . parse_url($nodeUrl, PHP_URL_HOST) . "\n";
    echo "      (Not localhost - this is a remote server)\n";
} else {
    echo "   ❌ NODE_URL not configured!\n";
}

// Check configuration files
echo "\n   Configuration Files:\n";
$envTxt = base_path('env.txt');
$envFile = base_path('.env');

if (file_exists($envTxt)) {
    echo "   ✅ env.txt exists\n";
} else {
    echo "   ⚠️  env.txt not found\n";
}

if (file_exists($envFile)) {
    echo "   ✅ .env exists\n";
} else {
    echo "   ⚠️  .env not found\n";
}

// Step 2: Construct API URL
echo "\n📋 STEP 2: API URL Construction\n";
echo str_repeat("-", 80) . "\n";

if ($nodeUrl) {
    $baseUrl = rtrim($nodeUrl, '/') . '/api';
    echo "   Base URL: $baseUrl\n";
    echo "   Test Endpoint: $baseUrl/category_img_list\n";
} else {
    echo "   ❌ Cannot construct API URL - NODE_URL not set\n";
    exit(1);
}

// Step 3: Test Node.js Server Connection
echo "\n📋 STEP 3: Testing Node.js Server Connection\n";
echo str_repeat("-", 80) . "\n";

$testUrl = $baseUrl . '/category_img_list';
echo "   Testing: GET $testUrl\n";

try {
    $headers = [
        'api-key' => $nodeApiKey ?: 'your-api-key-here',
        'Content-Type' => 'application/json',
    ];
    
    echo "   Sending request...\n";
    $response = Http::withHeaders($headers)->timeout(5)->get($testUrl);
    $statusCode = $response->status();
    
    echo "   Response Status: $statusCode\n";
    
    if ($statusCode === 200) {
        echo "   ✅ SUCCESS! Node.js server is accessible\n";
        $data = $response->json();
        if (isset($data['status'])) {
            echo "   Response Status: " . $data['status'] . "\n";
            if (isset($data['data']) && is_array($data['data'])) {
                echo "   Categories Found: " . count($data['data']) . "\n";
            }
        }
    } elseif ($statusCode === 401 || $statusCode === 403) {
        echo "   ⚠️  Authentication failed (Status: $statusCode)\n";
        echo "      Check NODE_API_KEY configuration\n";
    } elseif ($statusCode === 404) {
        echo "   ⚠️  Endpoint not found (Status: $statusCode)\n";
        echo "      The server is running but endpoint doesn't exist\n";
    } else {
        echo "   ⚠️  Unexpected status code: $statusCode\n";
    }
    
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    echo "   ❌ CONNECTION FAILED!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "\n   💡 Troubleshooting:\n";
    echo "      1. Make sure Node.js server is running\n";
    echo "      2. Check if port 3000 is correct\n";
    echo "      3. Run: cd SCRAPMATE-NODE-LAMBDA && npm start\n";
    echo "      4. Verify server is accessible at: {$nodeUrl}\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERROR!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Type: " . get_class($e) . "\n";
}

// Step 4: Summary
echo "\n📋 STEP 4: Summary\n";
echo str_repeat("-", 80) . "\n";

if ($nodeUrl && strpos($nodeUrl, 'localhost') !== false) {
    echo "   ✅ Configuration: LOCALHOST\n";
} else {
    echo "   ⚠️  Configuration: REMOTE SERVER\n";
}

// Check if we can connect
try {
    $testResponse = Http::timeout(2)->get($nodeUrl . '/api/category_img_list');
    if ($testResponse->successful()) {
        echo "   ✅ Connection: WORKING\n";
    } else {
        echo "   ⚠️  Connection: Server responded but with error\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Connection: FAILED\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Verification Complete!\n";
echo str_repeat("=", 80) . "\n\n";

// Quick checklist
echo "📝 QUICK CHECKLIST:\n";
echo "   [ ] Node.js server running on port 3000\n";
echo "   [ ] NODE_URL configured in env.txt or .env\n";
echo "   [ ] NODE_API_KEY configured\n";
echo "   [ ] Can access {$nodeUrl}/api/category_img_list\n";
echo "   [ ] PHP server restarted after configuration changes\n";
echo "\n";






