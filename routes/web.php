<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRestaurantController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Payments\WorldpayController;
use App\Http\Controllers\Restaurant\RestaurantWebController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Authentication Guest Routes
Route::get('/admin/login', [AdminDashboardController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminDashboardController::class, 'login'])->name('admin.login.submit');
Route::get('/admin/logout', [AdminDashboardController::class, 'logout'])->name('admin.logout');

// Protected Admin Dashboard Routes
Route::middleware([\App\Http\Middleware\AuthenticateAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Overview
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Restaurants CRUD & Approval
    Route::resource('restaurants', AdminRestaurantController::class);
    Route::post('restaurants/{id}/status', [AdminRestaurantController::class, 'updateStatus'])->name('restaurants.status');
    
    // Customers Management
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{id}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::post('customers/{id}/status', [AdminCustomerController::class, 'updateStatus'])->name('customers.status');
    
    // Orders logs & refunding trigger
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{id}/refund', [WorldpayController::class, 'refund'])->name('orders.refund');
    
    // Payments
    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{id}', [AdminPaymentController::class, 'show'])->name('payments.show');
    
    // Subscriptions
    Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/{id}', [AdminSubscriptionController::class, 'show'])->name('subscriptions.show');
    
    // Push notifications broadcast
    Route::get('notifications', [AdminDashboardController::class, 'showNotifications'])->name('notifications.show');
    Route::post('notifications/send', [AdminDashboardController::class, 'sendNotification'])->name('notifications.send');
    Route::get('notifications/monitor', [AdminDashboardController::class, 'monitorNotifications'])->name('notifications.monitor');
});

// Restaurant Authentication Guest Routes
Route::get('/restaurant/login', [RestaurantWebController::class, 'showLogin'])->name('restaurant.login');
Route::post('/restaurant/login', [RestaurantWebController::class, 'login'])->name('restaurant.login.submit');
Route::get('/restaurant/logout', [RestaurantWebController::class, 'logout'])->name('restaurant.logout');

// Protected Restaurant Dashboard Routes
Route::middleware(['auth.restaurant'])->prefix('restaurant')->name('restaurant.')->group(function () {
    // Overview Dashboard
    Route::get('/dashboard', [RestaurantWebController::class, 'index'])->name('dashboard');
    
    // Update Restaurant Profile details
    Route::get('/profile', [RestaurantWebController::class, 'showProfile'])->name('profile');
    Route::post('/profile', [RestaurantWebController::class, 'updateProfile'])->name('profile.update');
    
    // Menu Categories Management
    Route::get('/categories', [RestaurantWebController::class, 'categoriesIndex'])->name('categories.index');
    Route::post('/categories', [RestaurantWebController::class, 'categoriesStore'])->name('categories.store');
    Route::post('/categories/{id}', [RestaurantWebController::class, 'categoriesUpdate'])->name('categories.update');
    Route::post('/categories/{id}/delete', [RestaurantWebController::class, 'categoriesDestroy'])->name('categories.destroy');
    
    // Menu Items Management
    Route::get('/menu-items', [RestaurantWebController::class, 'menuItemsIndex'])->name('menu-items.index');
    Route::post('/menu-items', [RestaurantWebController::class, 'menuItemsStore'])->name('menu-items.store');
    Route::post('/menu-items/{id}', [RestaurantWebController::class, 'menuItemsUpdate'])->name('menu-items.update');
    Route::post('/menu-items/{id}/delete', [RestaurantWebController::class, 'menuItemsDestroy'])->name('menu-items.destroy');
    
    // Orders management (real-time order listing & status triggers)
    Route::get('/orders', [RestaurantWebController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/{id}', [RestaurantWebController::class, 'ordersShow'])->name('orders.show');
    Route::post('/orders/{id}/status', [RestaurantWebController::class, 'ordersUpdateStatus'])->name('orders.status');
    
    // Active Subscriptions
    Route::get('/subscriptions', [RestaurantWebController::class, 'subscriptionsIndex'])->name('subscriptions.index');
});
