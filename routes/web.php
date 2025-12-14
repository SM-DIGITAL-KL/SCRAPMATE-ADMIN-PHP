<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AccountsController;

// PHP Upload Limits Check Route (for debugging)
Route::get('/check-php-limits', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'php_ini_loaded_file' => php_ini_loaded_file(),
        'php_ini_scanned_files' => php_ini_scanned_files() ?: 'None',
        'php_version' => PHP_VERSION,
        'recommended' => [
            'upload_max_filesize' => '64M',
            'post_max_size' => '64M',
            'memory_limit' => '256M',
            'max_execution_time' => '300'
        ]
    ], 200, [], JSON_PRETTY_PRINT);
});

// Admin authentication required routes
Route::middleware(['authusers'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard'); // Alias for compatibility
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::post('/categories/{id}', [CategoryController::class, 'updateCategory'])->name('updateCategory');
    Route::put('/categories/{id}', [CategoryController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Subcategories (handled by CategoryController)
    Route::post('/subcategories', [CategoryController::class, 'createSubcategory'])->name('createSubcategory');
    Route::post('/subcategories/{id}', [CategoryController::class, 'updateSubcategory'])->name('updateSubcategory');
    Route::get('/subcategories/{id}/delete', [CategoryController::class, 'deleteSubcategory'])->name('deleteSubcategory');

    // Users
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
    Route::get('/view_users', [\App\Http\Controllers\AdminController::class, 'view_users'])->name('view_users');
    Route::match(['get', 'post'], '/manage_users/{id?}', [\App\Http\Controllers\AdminController::class, 'manage_users'])->name('manage_users');
    Route::match(['get', 'post'], '/user_password_reset/{id}', [\App\Http\Controllers\AdminController::class, 'user_password_reset'])->name('user_password_reset');
    Route::get('/del_user/{id}', [\App\Http\Controllers\AdminController::class, 'del_user'])->name('del_user');
    Route::get('/set_permission/{id?}', [\App\Http\Controllers\AdminController::class, 'set_permission'])->name('set_permission');
    Route::post('/store_user_per', [\App\Http\Controllers\AdminController::class, 'store_user_per'])->name('store_user_per');

    // B2B Users
    Route::get('/b2bUsers', [\App\Http\Controllers\AdminController::class, 'b2bUsers'])->name('b2bUsers');
    Route::get('/b2bUserDocuments/{userId}', [\App\Http\Controllers\AdminController::class, 'viewB2BUserDocuments'])->name('b2bUserDocuments');
    Route::post('/b2bUsers/{userId}/approval-status', [\App\Http\Controllers\AdminController::class, 'updateB2BApprovalStatus'])->name('updateB2BApprovalStatus');

    // B2C Users
    Route::get('/b2cUsers', [\App\Http\Controllers\AdminController::class, 'b2cUsers'])->name('b2cUsers');
    Route::get('/b2cUserDocuments/{userId}', [\App\Http\Controllers\AdminController::class, 'viewB2CUserDocuments'])->name('b2cUserDocuments');
    Route::post('/b2cUsers/{userId}/approval-status', [\App\Http\Controllers\AdminController::class, 'updateB2CApprovalStatus'])->name('updateB2CApprovalStatus');

    // Delivery Users
    Route::get('/deliveryUsers', [\App\Http\Controllers\AdminController::class, 'deliveryUsers'])->name('deliveryUsers');
    Route::get('/deliveryUserDocuments/{userId}', [\App\Http\Controllers\AdminController::class, 'viewDeliveryUserDocuments'])->name('deliveryUserDocuments');
    Route::post('/deliveryUsers/{userId}/approval-status', [\App\Http\Controllers\AdminController::class, 'updateDeliveryApprovalStatus'])->name('updateDeliveryApprovalStatus');

    // Agent/Vendor Routes
    Route::get('/agents', [AgentController::class, 'agents'])->name('agents');
    Route::match(['get', 'post'], '/manage_agent/{id?}', [AgentController::class, 'manage_agent'])->name('manage_agent');
    Route::get('/view_shops', [AgentController::class, 'view_shops'])->name('view_shops');
    Route::get('/shop_status_change/{id}', [AgentController::class, 'shop_status_change'])->name('shop_status_change');
    Route::get('/shop_view_by_id/{id}', [AgentController::class, 'shop_view_by_id'])->name('shop_view_by_id');
    Route::get('/del_shop/{id}', [AgentController::class, 'del_shop'])->name('del_shop');
    Route::get('/show_shop_images/{id}', [AgentController::class, 'show_shop_images'])->name('show_shop_images');

    // Customer & Order Routes
    Route::get('/customers', [CustomerController::class, 'customers'])->name('customers');
    Route::get('/view_customers', [CustomerController::class, 'view_customers'])->name('view_customers');
    Route::get('/orders', [CustomerController::class, 'orders'])->name('orders');
    Route::get('/view_orders', [CustomerController::class, 'view_orders'])->name('view_orders');
    Route::get('/view_order_details/{id}', [CustomerController::class, 'view_order_details'])->name('view_order_details');

    // Reports & Notifications
    Route::match(['get', 'post'], '/signUpReport', [\App\Http\Controllers\AdminController::class, 'signUpReport'])->name('signUpReport');
    Route::get('/custNotification', [\App\Http\Controllers\AdminController::class, 'custNotification'])->name('custNotification');
    Route::post('/sendCustNotification', [\App\Http\Controllers\AdminController::class, 'sendCustNotification'])->name('sendCustNotification');
    Route::get('/vendorNotification', [\App\Http\Controllers\AdminController::class, 'vendorNotification'])->name('vendorNotification');
    Route::post('/sendVendorNotification', [\App\Http\Controllers\AdminController::class, 'sendVendorNotification'])->name('sendVendorNotification');
    
    // Utilities
    Route::post('/check_distance', [\App\Http\Controllers\AdminController::class, 'check_distance'])->name('check_distance');
    Route::get('/callLogSearch', [\App\Http\Controllers\AdminController::class, 'callLogSearch'])->name('callLogSearch');
    Route::get('/getcallLogSearch', [\App\Http\Controllers\AdminController::class, 'getcallLogSearch'])->name('getcallLogSearch');

    // Subscription Packages
    Route::get('/subscriptionPackages', [\App\Http\Controllers\AdminController::class, 'subscriptionPackages'])->name('subscriptionPackages');
    Route::match(['post', 'put', 'delete'], '/subscriptionPackages/{id}', [\App\Http\Controllers\AdminController::class, 'updateSubscriptionPackage'])->name('updateSubscriptionPackage');
    
    // Subscribers List
    Route::get('/subcribersList', [AccountsController::class, 'subcribersList'])->name('subcribersList.index');
    Route::get('/view_subcribersList', [AccountsController::class, 'view_subcribersList'])->name('view_subcribersList');

    // Site Management
    Route::match(['get', 'post'], '/manage_site', [SiteController::class, 'manage_site'])->name('manage_site');
    Route::match(['get', 'post'], '/updateAppVersion', [SiteController::class, 'updateAppVersion'])->name('updateAppVersion');
    
    // Dashboard API routes (cached endpoints that proxy to Node.js)
    Route::prefix('api')->group(function () {
        Route::get('/dashboard/kpis', [DashboardController::class, 'dashboardKPIs'])->name('api.dashboard.kpis');
        Route::get('/dashboard/charts', [DashboardController::class, 'dashboardCharts'])->name('api.dashboard.charts');
        Route::get('/dashboard/recent-orders', [DashboardController::class, 'dashboardRecentOrders'])->name('api.dashboard.recent-orders');
        Route::get('/dashboard/call-logs', [DashboardController::class, 'dashboardCallLogs'])->name('api.dashboard.call-logs');
    });
});

// Login routes (no auth required)
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/dologin', [LoginController::class, 'dologin'])->name('dologin');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
