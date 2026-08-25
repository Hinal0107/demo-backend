<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Customer\CustomerRestaurantController;
use App\Http\Controllers\Customer\CustomerMenuController;
use App\Http\Controllers\Customer\CustomerSubscriptionController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\CustomerDailyMealController;
use App\Http\Controllers\Customer\CustomerAddonController;
use App\Http\Controllers\Customer\CustomerTaxController;
use App\Http\Controllers\Restaurant\RestaurantProfileController;
use App\Http\Controllers\Restaurant\RestaurantCategoryController;
use App\Http\Controllers\Restaurant\RestaurantMenuItemController;
use App\Http\Controllers\Restaurant\RestaurantSubscriptionPlanController;
use App\Http\Controllers\Restaurant\RestaurantOrderController;
use App\Http\Controllers\Payments\WorldpayController;
use App\Http\Controllers\FCM\FcmTokenController;
use App\Http\Controllers\Notification\NotificationController;

Route::prefix('v1')->group(function () {
    
    
    // 1. Guest Authentication Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // 2. Public Worldpay Webhooks & Simulation
    Route::post('/payments/worldpay/webhook', [WorldpayController::class, 'webhook']);
    Route::post('/payments/worldpay/simulate', [WorldpayController::class, 'simulate']);

    // 3. Protected Routes (validated via Firebase Token or Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth management
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // CUSTOMER Scoped Operations
        Route::middleware('role:CUSTOMER')->group(function () {
            
            // Restaurant Listings
            Route::get('/restaurants', [CustomerRestaurantController::class, 'index']);
            Route::get('/restaurants/{restaurantId}', [CustomerRestaurantController::class, 'show']);

            // Menu Discovery
            Route::get('/restaurants/{restaurantId}/categories', [CustomerMenuController::class, 'categories']);
            Route::get('/restaurants/{restaurantId}/menu', [CustomerMenuController::class, 'menu']);
            Route::get('/menu-items/{id}', [CustomerMenuController::class, 'showItem']);
            Route::get('/restaurants/{restaurantId}/today-meal', [CustomerDailyMealController::class, 'todayMeal']);
            Route::get('/restaurants/{restaurantId}/tomorrow-meal', [CustomerDailyMealController::class, 'tomorrowMeal']);
            Route::get('/restaurants/{restaurantId}/weekly-meal', [CustomerDailyMealController::class, 'weeklyMeal']);
            Route::get('/restaurants/{restaurantId}/daily-meals', [CustomerDailyMealController::class, 'index']);
            Route::get('/restaurants/{restaurantId}/addons', [CustomerAddonController::class, 'index']);
            Route::get('/restaurants/{restaurantId}/taxes', [CustomerTaxController::class, 'index']);

            // Customer Addresses
            Route::apiResource('/addresses', AddressController::class);

            // Plan Discoveries & Subscriptions
            Route::get('/restaurants/{restaurantId}/subscription-plans', [CustomerSubscriptionController::class, 'plans']);
            Route::get('/subscription-plans/{id}', [CustomerSubscriptionController::class, 'showPlan']);
            Route::post('/subscriptions', [CustomerSubscriptionController::class, 'subscribe']);
            Route::get('/subscriptions', [CustomerSubscriptionController::class, 'index']);
            Route::get('/subscriptions/{id}', [CustomerSubscriptionController::class, 'show']);
            Route::post('/subscriptions/{id}/pause', [CustomerSubscriptionController::class, 'pause']);
            Route::post('/subscriptions/{id}/resume', [CustomerSubscriptionController::class, 'resume']);
            Route::post('/subscriptions/{id}/cancel', [CustomerSubscriptionController::class, 'cancel']);

            // Cart Actions
            Route::get('/cart', [CartController::class, 'index']);
            Route::post('/cart/items', [CartController::class, 'store']);
            Route::put('/cart/items/{id}', [CartController::class, 'update']);
            Route::delete('/cart/items/{id}', [CartController::class, 'destroy']);
            Route::delete('/cart', [CartController::class, 'clear']);

            // Order Actions
            Route::post('/orders', [OrderController::class, 'store']);
            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
            Route::get('/orders/{id}/tracking', [OrderController::class, 'tracking']);
        });

        // RESTAURANT Scoped Operations & Isolation
        Route::middleware(['role:RESTAURANT', 'restaurant.isolation'])->group(function () {
            
            // Profile Info
            Route::get('/restaurant/profile', [RestaurantProfileController::class, 'show']);
            Route::put('/restaurant/profile', [RestaurantProfileController::class, 'update']);

            // Categories Management
            Route::get('/restaurant/categories', [RestaurantCategoryController::class, 'index']);
            Route::post('/restaurant/categories', [RestaurantCategoryController::class, 'store']);
            Route::put('/restaurant/categories/{id}', [RestaurantCategoryController::class, 'update']);
            Route::delete('/restaurant/categories/{id}', [RestaurantCategoryController::class, 'destroy']);

            // Menu Items Management
            Route::get('/restaurant/menu-items', [RestaurantMenuItemController::class, 'index']);
            Route::post('/restaurant/menu-items', [RestaurantMenuItemController::class, 'store']);
            Route::put('/restaurant/menu-items/{id}', [RestaurantMenuItemController::class, 'update']);
            Route::delete('/restaurant/menu-items/{id}', [RestaurantMenuItemController::class, 'destroy']);

            // Subscription Plans Management
            Route::get('/restaurant/subscription-plans', [RestaurantSubscriptionPlanController::class, 'index']);
            Route::post('/restaurant/subscription-plans', [RestaurantSubscriptionPlanController::class, 'store']);
            Route::put('/restaurant/subscription-plans/{id}', [RestaurantSubscriptionPlanController::class, 'update']);
            Route::delete('/restaurant/subscription-plans/{id}', [RestaurantSubscriptionPlanController::class, 'destroy']);

            // Order Scopes Management
            Route::get('/restaurant/orders', [RestaurantOrderController::class, 'index']);
            Route::get('/restaurant/orders/{id}', [RestaurantOrderController::class, 'show']);
            Route::post('/restaurant/orders/{id}/confirm', [RestaurantOrderController::class, 'confirm']);
            Route::post('/restaurant/orders/{id}/preparing', [RestaurantOrderController::class, 'preparing']);
            Route::post('/restaurant/orders/{id}/ready', [RestaurantOrderController::class, 'ready']);
            Route::post('/restaurant/orders/{id}/out-for-delivery', [RestaurantOrderController::class, 'outForDelivery']);
            Route::post('/restaurant/orders/{id}/delivered', [RestaurantOrderController::class, 'delivered']);
            Route::post('/restaurant/orders/{id}/cancel', [RestaurantOrderController::class, 'cancel']);
        });

        // Common Notifications & Tokens
        Route::post('/notifications/token', [FcmTokenController::class, 'store']);
        Route::post('/devices/register', [FcmTokenController::class, 'register']);
        Route::post('/devices/unregister', [FcmTokenController::class, 'unregister']);
        Route::post('/device/fcm-token', [FcmTokenController::class, 'store']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read', [NotificationController::class, 'read']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

        // Secure refund endpoints (Available for both admin and restaurant owners)
        Route::post('/orders/{id}/refund', [WorldpayController::class, 'refund']);
    });
});
