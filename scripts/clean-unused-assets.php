<?php
/**
 * Script to remove unused assets from admin panel
 * Only keeps assets referenced by the specified routes
 */

// Get the admin-panel directory (parent of scripts)
$basePath = realpath(__DIR__ . '/../');
$assetsPath = $basePath . '/public/assets';
$viewsPath = $basePath . '/resources/views';

// Views used by the specified routes
$usedViews = [
    'login.blade.php',
    'admin/dashboard.blade.php',
    'admin/users.blade.php',
    'admin/set_permission.blade.php',
    'agent/agents.blade.php',
    'customers/customers.blade.php',
    'customers/orders.blade.php',
    'accounts/subPackages.blade.php',
    'accounts/subcribersList.blade.php',
    'admin/vendorNotification.blade.php',
    'admin/custNotification.blade.php',
    'admin/signUpReport.blade.php',
    'site/manage_site.blade.php',
    // Layout files (always included)
    'layouts/top.blade.php',
    'layouts/footer.blade.php',
    'layouts/sidemenu.blade.php',
    'layouts/header.blade.php',
    'layouts/modal.blade.php',
    'layouts/flashmessage.blade.php',
];

// Collect all asset references from views
$usedAssets = [];

function scanFileForAssets($filePath, &$usedAssets) {
    if (!file_exists($filePath)) {
        return;
    }
    
    $content = file_get_contents($filePath);
    
    // Match asset() helper: asset('assets/...')
    preg_match_all("/asset\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $asset) {
            $usedAssets[$asset] = true;
        }
    }
    
    // Match direct paths: /assets/... or assets/...
    preg_match_all("/(?:['\"]|src=|href=)(?:\\/)?assets\\/([^'\"\\s>]+)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $asset) {
            $usedAssets['assets/' . $asset] = true;
        }
    }
    
    // Match CSS/JS includes in layouts
    preg_match_all("/(?:href|src)=['\"]([^'\"]*\\.(?:css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot))['\"]/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $asset) {
            if (strpos($asset, 'assets/') !== false || strpos($asset, '/assets/') !== false) {
                $asset = preg_replace('/^.*?assets\//', 'assets/', $asset);
                $usedAssets[$asset] = true;
            }
        }
    }
}

// Scan all used views
echo "Scanning views for asset references...\n";
foreach ($usedViews as $view) {
    $viewPath = $viewsPath . '/' . $view;
    if (file_exists($viewPath)) {
        echo "  Scanning: $view\n";
        scanFileForAssets($viewPath, $usedAssets);
    } else {
        echo "  Warning: View not found: $view\n";
    }
}

// Also scan config/app.php for asset_url references
$configPath = $basePath . 'config/app.php';
if (file_exists($configPath)) {
    echo "  Scanning: config/app.php\n";
    scanFileForAssets($configPath, $usedAssets);
}

echo "\nFound " . count($usedAssets) . " unique asset references\n\n";

// Normalize asset paths (remove leading/trailing slashes, handle relative paths)
$normalizedAssets = [];
foreach ($usedAssets as $asset => $_) {
    $asset = trim($asset, '/');
    if (strpos($asset, 'assets/') === 0) {
        $asset = substr($asset, 7); // Remove 'assets/' prefix
    }
    if ($asset) {
        $normalizedAssets[$asset] = true;
    }
}

// Get all files in assets directory
function getAllFiles($dir, $baseDir = '') {
    $files = [];
    if (!is_dir($dir)) {
        return $files;
    }
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $fullPath = $dir . '/' . $item;
        $relativePath = $baseDir ? $baseDir . '/' . $item : $item;
        
        if (is_dir($fullPath)) {
            $files = array_merge($files, getAllFiles($fullPath, $relativePath));
        } else {
            $files[] = $relativePath;
        }
    }
    
    return $files;
}

echo "Scanning assets directory...\n";
$allAssets = getAllFiles($assetsPath);

echo "Found " . count($allAssets) . " files in assets directory\n\n";

// Check which assets are used
$usedFiles = [];
$unusedFiles = [];

foreach ($allAssets as $asset) {
    $assetPath = str_replace('\\', '/', $asset);
    
    // Check if this asset is referenced
    $isUsed = false;
    foreach ($normalizedAssets as $ref => $_) {
        // Exact match
        if ($assetPath === $ref) {
            $isUsed = true;
            break;
        }
        // Directory match (if reference is a directory, all files in it are used)
        if (strpos($assetPath, $ref . '/') === 0) {
            $isUsed = true;
            break;
        }
        // Reverse check (if asset is a directory that contains the reference)
        if (strpos($ref, $assetPath . '/') === 0) {
            $isUsed = true;
            break;
        }
    }
    
    // Also check common essential files that should always be kept
    $essentialPatterns = [
        'css/style.css',
        'vendor/bootstrap-select',
        'vendor/datatables',
        'vendor/swiper',
        'vendor/bootstrap-daterangepicker',
        'vendor/clockpicker',
        'vendor/jquery-asColorPicker',
        'vendor/bootstrap-material-datetimepicker',
        'vendor/pickadate',
        'vendor/select2',
        'vendor/wow-master',
        'vendor/bootstrap-select-country',
        'vendor/datepicker',
        'icons/fontawesome',
        'icons/bootstrap-icons',
        'icons/icomoon',
        'icons/line-awesome',
        'icons/flaticon',
        'icons/flaticon-1',
        'icons/avasta',
        'icons/simple-line-icons',
        'icons/themify-icons',
        'icons/material-design-iconic-font',
    ];
    
    foreach ($essentialPatterns as $pattern) {
        if (strpos($assetPath, $pattern) !== false) {
            $isUsed = true;
            break;
        }
    }
    
    if ($isUsed) {
        $usedFiles[] = $asset;
    } else {
        $unusedFiles[] = $asset;
    }
}

echo "Used files: " . count($usedFiles) . "\n";
echo "Unused files: " . count($unusedFiles) . "\n\n";

if (empty($unusedFiles)) {
    echo "No unused files to remove!\n";
    exit(0);
}

// Show summary
echo "Unused files to be removed:\n";
foreach (array_slice($unusedFiles, 0, 20) as $file) {
    echo "  - $file\n";
}
if (count($unusedFiles) > 20) {
    echo "  ... and " . (count($unusedFiles) - 20) . " more files\n";
}

echo "\n";
echo "Do you want to remove these " . count($unusedFiles) . " unused files? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'yes') {
    echo "Aborted.\n";
    exit(0);
}

// Remove unused files
$removedCount = 0;
$errorCount = 0;

foreach ($unusedFiles as $file) {
    $fullPath = $assetsPath . '/' . $file;
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            $removedCount++;
        } else {
            echo "Error removing: $file\n";
            $errorCount++;
        }
    }
}

// Remove empty directories
function removeEmptyDirs($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $items = scandir($dir);
    $isEmpty = true;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $itemPath = $dir . '/' . $item;
        if (is_dir($itemPath)) {
            removeEmptyDirs($itemPath);
            if (is_dir($itemPath) && count(scandir($itemPath)) === 2) {
                rmdir($itemPath);
            } else {
                $isEmpty = false;
            }
        } else {
            $isEmpty = false;
        }
    }
    
    if ($isEmpty && count($items) === 2) {
        rmdir($dir);
    }
}

removeEmptyDirs($assetsPath);

echo "\n";
echo "Removed $removedCount files\n";
if ($errorCount > 0) {
    echo "Errors: $errorCount files could not be removed\n";
}
echo "Done!\n";

