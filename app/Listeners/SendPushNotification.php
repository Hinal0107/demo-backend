<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        Log::info('SendPushNotification Listener: Processing event ' . get_class($event));

        try {
            if ($event instanceof \App\Events\OrderCreatedEvent) {
                $this->notificationService->notifyRestaurantNewOrder($event->order);
            } elseif ($event instanceof \App\Events\OrderPaymentSuccessfulEvent) {
                $this->notificationService->notifyCustomerPaymentSuccess($event->order);
            } elseif ($event instanceof \App\Events\OrderPaymentFailedEvent) {
                $this->notificationService->notifyCustomerPaymentFailed($event->order);
            } elseif ($event instanceof \App\Events\OrderPaymentPendingEvent) {
                $this->notificationService->notifyCustomerPaymentPending($event->order);
            } elseif ($event instanceof \App\Events\OrderConfirmedEvent) {
                $this->notificationService->notifyCustomerOrderConfirmed($event->order);
            } elseif ($event instanceof \App\Events\OrderRejectedEvent) {
                $this->notificationService->notifyCustomerOrderRejected($event->order, $event->reason);
            } elseif ($event instanceof \App\Events\OrderPreparingEvent) {
                $this->notificationService->notifyCustomerOrderPreparing($event->order);
            } elseif ($event instanceof \App\Events\OrderReadyEvent) {
                $this->notificationService->notifyCustomerOrderReady($event->order);
            } elseif ($event instanceof \App\Events\OrderOutForDeliveryEvent) {
                $this->notificationService->notifyCustomerOrderOutForDelivery($event->order);
            } elseif ($event instanceof \App\Events\OrderDeliveredEvent) {
                $this->notificationService->notifyCustomerOrderDelivered($event->order);
            } elseif ($event instanceof \App\Events\OrderCancelledEvent) {
                $this->notificationService->notifyOrderCancelled($event->order, $event->reason);
            } elseif ($event instanceof \App\Events\SubscriptionPurchasedEvent) {
                $this->notificationService->notifyCustomerSubscriptionPurchased($event->subscription);
                $this->notificationService->notifyRestaurantNewSubscription($event->subscription);
            } elseif ($event instanceof \App\Events\SubscriptionActivatedEvent) {
                $this->notificationService->notifyCustomerSubscriptionActivated($event->subscription);
            } elseif ($event instanceof \App\Events\SubscriptionCancelledEvent) {
                $this->notificationService->notifyCustomerSubscriptionCancelled($event->subscription);
                $this->notificationService->notifyRestaurantSubscriptionCancelled($event->subscription);
            } elseif ($event instanceof \App\Events\RestaurantApprovedEvent) {
                $this->notificationService->notifyRestaurantApproved($event->restaurant);
            } elseif ($event instanceof \App\Events\RestaurantBlockedEvent) {
                $this->notificationService->notifyRestaurantBlocked($event->restaurant);
            } elseif ($event instanceof \App\Events\SubscriptionExpiringEvent) {
                $this->notificationService->notifySubscriptionExpiring($event->subscription, $event->daysLeft);
            }
        } catch (\Exception $e) {
            Log::error('SendPushNotification Listener: Processing failed', [
                'event' => get_class($event),
                'error' => $e->getMessage()
            ]);
        }
    }
}
