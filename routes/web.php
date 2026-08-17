<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRestaurantController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Payments\WorldpayController;

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
});
