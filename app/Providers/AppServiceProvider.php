<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderCreatedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderPaymentSuccessfulEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderPaymentFailedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderPaymentPendingEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderConfirmedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderRejectedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderPreparingEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderReadyEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderOutForDeliveryEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderDeliveredEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\OrderCancelledEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\SubscriptionPurchasedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\SubscriptionActivatedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\SubscriptionExpiringEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\SubscriptionCancelledEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\RestaurantApprovedEvent::class, \App\Listeners\SendPushNotification::class);
        \Illuminate\Support\Facades\Event::listen(\App\Events\RestaurantBlockedEvent::class, \App\Listeners\SendPushNotification::class);
    }
}
