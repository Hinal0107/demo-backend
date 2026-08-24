import 'package:flutter/material.dart';

class NotificationRouter {
  // Global navigator key to trigger redirects without context
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  static void handle(Map<String, dynamic> payload) {
    final String? type = payload['type'];
    final String? orderId = payload['order_id'];
    final String? subscriptionId = payload['subscription_id'];

    if (type == null) return;

    print("NotificationRouter routing type: $type");

    switch (type) {
      case 'new_order':
        // Navigate to Restaurant Order Details Screen
        navigatorKey.currentState?.pushNamed(
          '/restaurant/order-details',
          arguments: {'order_id': orderId},
        );
        break;

      case 'order_confirmed':
      case 'order_preparing':
      case 'order_ready':
      case 'order_out_for_delivery':
      case 'order_delivered':
      case 'order_cancelled':
        // Navigate to Customer Order Details / Track Screen
        navigatorKey.currentState?.pushNamed(
          '/customer/order-details',
          arguments: {'order_id': orderId},
        );
        break;

      case 'payment_success':
      case 'payment_failed':
      case 'payment_pending':
        // Navigate to Payment / Order Details screen
        navigatorKey.currentState?.pushNamed(
          '/customer/payment-details',
          arguments: {'order_id': orderId},
        );
        break;

      case 'subscription_purchased':
      case 'subscription_activated':
      case 'subscription_expiring':
      case 'subscription_cancelled':
        // Navigate to Subscriptions Management view
        navigatorKey.currentState?.pushNamed(
          '/customer/subscription-details',
          arguments: {'subscription_id': subscriptionId},
        );
        break;

      case 'system_update':
      default:
        // Default fall-back to Notification Inbox screen
        navigatorKey.currentState?.pushNamed('/notifications-inbox');
        break;
    }
  }
}
