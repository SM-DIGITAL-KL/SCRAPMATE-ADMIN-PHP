<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes - PROXY MODE
|--------------------------------------------------------------------------
|
| Laravel web routes are kept for view rendering. Controllers call Node.js 
| APIs for data. Node.js handles the actual business logic.
|
| Mobile app API routes are in routes/api.php (also migrated to Node.js)
| Node.js Server Base URL: Configure in .env (NODE_URL)
|
*/

// Authentication Routes (no middleware)
Route::get('/', [LoginController::class, 'login'])->name('login');
Route::get('/login', [LoginController::class, 'login'])->name('login.show');
Route::post('/login', [LoginController::class, 'dologin'])->name('dologin');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('dashboard')->middleware('authusers');
// Cached dashboard API endpoints (10 minutes cache)
Route::get('/api/dashboard/kpis', [AdminController::class, 'dashboardKPIs'])->name('dashboard.kpis')->middleware('authusers');
Route::get('/api/dashboard/charts', [AdminController::class, 'dashboardCharts'])->name('dashboard.charts')->middleware('authusers');
Route::get('/api/dashboard/recent-orders', [AdminController::class, 'dashboardRecentOrders'])->name('dashboard.recentOrders')->middleware('authusers');
Route::get('/api/dashboard/call-logs', [AdminController::class, 'dashboardCallLogs'])->name('dashboard.callLogs')->middleware('authusers');
Route::get('/users', [AdminController::class, 'users'])->name('users')->middleware('authusers');
Route::get('/b2b-users', [AdminController::class, 'b2bUsers'])->name('b2bUsers')->middleware('authusers');
Route::get('/b2b-users/{userId}/documents', [AdminController::class, 'viewB2BUserDocuments'])->name('b2bUserDocuments')->middleware('authusers');
Route::post('/b2b-users/{userId}/approval-status', [AdminController::class, 'updateB2BApprovalStatus'])->name('updateB2BApprovalStatus')->middleware('authusers');
Route::get('/b2c-users', [AdminController::class, 'b2cUsers'])->name('b2cUsers')->middleware('authusers');
Route::get('/b2c-users/{userId}/documents', [AdminController::class, 'viewB2CUserDocuments'])->name('b2cUserDocuments')->middleware('authusers');
Route::post('/b2c-users/{userId}/approval-status', [AdminController::class, 'updateB2CApprovalStatus'])->name('updateB2CApprovalStatus')->middleware('authusers');
Route::get('/delivery-users/{userId}/documents', [AdminController::class, 'viewDeliveryUserDocuments'])->name('deliveryUserDocuments')->middleware('authusers');
Route::post('/delivery-users/{userId}/approval-status', [AdminController::class, 'updateDeliveryApprovalStatus'])->name('updateDeliveryApprovalStatus')->middleware('authusers');
Route::get('/subPackages', [AdminController::class, 'subscriptionPackages'])->name('subscriptionPackages')->middleware('authusers');
Route::post('/subPackages/{id}', [AdminController::class, 'updateSubscriptionPackage'])->name('updateSubscriptionPackage')->middleware('authusers');
Route::delete('/subPackages/{id}', [AdminController::class, 'updateSubscriptionPackage'])->name('deleteSubscriptionPackage')->middleware('authusers');
Route::get('/view_users', [AdminController::class, 'view_users'])->name('view_users')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/manage_users/{id?}', [AdminController::class, 'manage_users'])->middleware('authusers');
Route::get('/del_user/{id}', [AdminController::class, 'del_user'])->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/user_password_reset/{id}', [AdminController::class, 'user_password_reset'])->name('user_password_reset')->middleware('authusers');
Route::get('/set_permission/{id?}', [AdminController::class, 'set_permission'])->name('set_permission')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/store_user_per', [AdminController::class, 'store_user_per'])->middleware('authusers');
Route::match(['get', 'post'], '/check_distance', [AdminController::class, 'check_distance'])->middleware('authusers');
Route::match(['get', 'post'], '/signUpReport', [AdminController::class, 'signUpReport'])->name('signUpReport')->middleware('authusers');
Route::match(['get', 'post'], '/custNotification', [AdminController::class, 'custNotification'])->name('custNotification')->middleware('authusers');
Route::match(['get', 'post'], '/vendorNotification', [AdminController::class, 'vendorNotification'])->name('vendorNotification')->middleware('authusers');
Route::post('/sendCustNotification', [AdminController::class, 'sendCustNotification'])->name('sendCustNotification')->middleware('authusers');
Route::post('/sendVendorNotification', [AdminController::class, 'sendVendorNotification'])->name('sendVendorNotification')->middleware('authusers');
Route::get('/callLogSearch', [AdminController::class, 'callLogSearch'])->name('callLogSearch')->middleware('authusers');
Route::get('/getcallLogSearch', [AdminController::class, 'getcallLogSearch'])->name('getcallLogSearch')->middleware('authusers');

// Agent Routes
Route::get('/agents', [AgentController::class, 'agents'])->name('agents')->middleware('authusers');
Route::get('/view_shops', [AgentController::class, 'view_shops'])->name('view_shops')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/manage_agent/{id?}', [AgentController::class, 'manage_agent'])->middleware('authusers');
Route::get('/show_shop_images/{id?}', [AgentController::class, 'show_shop_images'])->middleware('authusers');
Route::get('/shop_status_change/{id}', [AgentController::class, 'shop_status_change'])->name('shop_status_change')->middleware('authusers');
Route::get('/del_shop/{id}', [AgentController::class, 'del_shop'])->middleware('authusers');
Route::get('/shop_view_by_id/{id}', [AgentController::class, 'shop_view_by_id'])->name('shop_view_by_id')->middleware('authusers');
Route::match(['get', 'post'], '/createCategory/{id}', [AgentController::class, 'createCategory'])->middleware('authusers');
Route::match(['get', 'post'], '/createItem/{shop_id}/{cat_id}', [AgentController::class, 'createItem'])->middleware('authusers');
Route::get('/view_del_boy/{id}', [AgentController::class, 'view_del_boy'])->middleware('authusers');
Route::get('/agents_leads', [AgentController::class, 'agents_leads'])->name('agents_leads')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/manage_leads', [AgentController::class, 'manage_leads'])->middleware('authusers');
Route::match(['get', 'post'], '/agent_report', [AgentController::class, 'agent_report'])->name('agent_report')->middleware('authusers');
Route::match(['get', 'post'], '/commission_track', [AgentController::class, 'commission_track'])->name('commission_track')->middleware('authusers');

// Accounts Routes
// Route::get('/subPackages', [AccountsController::class, 'subPackages'])->name('subPackages.index')->middleware('authusers'); // Commented out - using AdminController::subscriptionPackages instead
Route::match(['get', 'post'], '/createSubPackage', [AccountsController::class, 'createSubPackage'])->name('subPackages.create')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/editSubPackage/{id}', [AccountsController::class, 'editSubPackage'])->name('subPackages.edit')->middleware('authusers');
Route::get('/delSubPackage/{id}', [AccountsController::class, 'delSubPackage'])->name('subPackages.delete')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/updateSubPackageStatus', [AccountsController::class, 'updateSubPackageStatus'])->name('subPackages.updateStatus')->middleware('authusers');
Route::get('/view_subcribersList', [AccountsController::class, 'view_subcribersList'])->name('subcribersList.index')->middleware('authusers');

// Customer Routes
Route::get('/customers', [CustomerController::class, 'customers'])->name('customers')->middleware('authusers');
Route::get('/orders', [CustomerController::class, 'orders'])->name('orders')->middleware('authusers');
Route::get('/view_customers', [CustomerController::class, 'view_customers'])->name('view_customers')->middleware('authusers');
Route::get('/view_orders', [CustomerController::class, 'view_orders'])->name('view_orders')->middleware('authusers');
Route::get('/view_order_details/{id}', [CustomerController::class, 'view_order_details'])->middleware('authusers');
Route::get('/del_customer/{id}', [CustomerController::class, 'del_customer'])->middleware('authusers');
Route::match(['get', 'post'], '/show_recent_orders/{id?}', [CustomerController::class, 'show_recent_orders'])->middleware('authusers');

// Site Routes
Route::match(['get', 'post', 'put', 'patch'], '/manage_site', [SiteController::class, 'manage_site'])->name('manage_site')->middleware('authusers');
Route::match(['get', 'post', 'put', 'patch'], '/updateAppVersion', [SiteController::class, 'updateAppVersion'])->name('updateAppVersion')->middleware('authusers');
