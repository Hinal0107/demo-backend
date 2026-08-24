import 'dart:convert';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'notification_router.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();

  String? _backendUrl;
  String? _authToken;

  // Initialize service with backend configurations
  Future<void> init({required String backendUrl, String? authToken}) async {
    _backendUrl = backendUrl;
    _authToken = authToken;

    // 1. Request notification permissions
    NotificationSettings settings = await _fcm.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      print('User granted notification permission.');
    }

    // 2. Initialize Local Notifications for Foreground Alerts
    const AndroidInitializationSettings androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const DarwinInitializationSettings iosInit = DarwinInitializationSettings();
    const InitializationSettings initSettings = InitializationSettings(android: androidInit, iOS: iosInit);

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        if (response.payload != null) {
          final Map<String, dynamic> payload = json.decode(response.payload!);
          _handleNotificationTap(payload);
        }
      },
    );

    // 3. Listen to Foreground Messages
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      print("Received foreground message: ${message.notification?.title}");
      _showLocalNotification(message);
    });

    // 4. Listen to Notification Taps (App in background)
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      print("Notification tapped while app was in background: ${message.data}");
      _handleNotificationTap(message.data);
    });

    // 5. Handle Terminated App State Launch
    RemoteMessage? initialMessage = await _fcm.getInitialMessage();
    if (initialMessage != null) {
      print("App launched from terminated state via notification: ${initialMessage.data}");
      _handleNotificationTap(initialMessage.data);
    }

    // 6. Token Refresh handler
    _fcm.onTokenRefresh.listen((String newToken) {
      print("FCM Token Refreshed: $newToken");
      registerDeviceToken(newToken);
    });

    // Register current token
    String? currentToken = await _fcm.getToken();
    if (currentToken != null) {
      await registerDeviceToken(currentToken);
    }
  }

  // Set auth token when user logs in
  void updateAuthToken(String? token) {
    _authToken = token;
    // Re-register token with updated authorization
    _fcm.getToken().then((token) {
      if (token != null) registerDeviceToken(token);
    });
  }

  // Register device token with Laravel Backend
  Future<bool> registerDeviceToken(String fcmToken) async {
    if (_backendUrl == null || _authToken == null) {
      print("Cannot register token: backendUrl or authToken not configured.");
      return false;
    }

    try {
      final response = await http.post(
        Uri.parse('$_backendUrl/api/v1/devices/register'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $_authToken',
        },
        body: json.encode({
          'fcm_token': fcmToken,
          'device_type': getDeviceType(),
          'device_id': await _getOrGenerateDeviceId(),
        }),
      );

      if (response.statusCode == 200) {
        print("FCM Token successfully registered with backend.");
        return true;
      } else {
        print("Failed to register token: ${response.body}");
        return false;
      }
    } catch (e) {
      print("Error registering token with backend: $e");
      return false;
    }
  }

  // Unregister device token (On Logout)
  Future<bool> unregisterDeviceToken() async {
    if (_backendUrl == null || _authToken == null) return false;

    try {
      String? token = await _fcm.getToken();
      if (token == null) return false;

      final response = await http.post(
        Uri.parse('$_backendUrl/api/v1/devices/unregister'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $_authToken',
        },
        body: json.encode({
          'fcm_token': token,
        }),
      );

      if (response.statusCode == 200) {
        print("FCM Token successfully unregistered.");
        return true;
      }
    } catch (e) {
      print("Error unregistering device: $e");
    }
    return false;
  }

  // Show a standard local HUD notification popup in foreground
  Future<void> _showLocalNotification(RemoteMessage message) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'tiffin_notifications_channel',
      'Tiffin Notifications',
      channelDescription: 'Real-time order and subscription alerts',
      importance: Importance.max,
      priority: Priority.high,
      playSound: true,
    );

    const NotificationDetails platformDetails = NotificationDetails(android: androidDetails, iOS: DarwinNotificationDetails());

    await _localNotifications.show(
      message.hashCode,
      message.notification?.title ?? 'Notification',
      message.notification?.body ?? '',
      platformDetails,
      payload: json.encode(message.data),
    );
  }

  // Delegate tap action to routing controller
  void _handleNotificationTap(Map<String, dynamic> data) {
    NotificationRouter.handle(data);
  }

  String getDeviceType() {
    return 'android'; // Replace with Platform.isIOS ? 'ios' : 'android'
  }

  Future<String> _getOrGenerateDeviceId() async {
    final prefs = await SharedPreferences.getInstance();
    String? deviceId = prefs.getString('unique_device_id');
    if (deviceId == null) {
      deviceId = 'dev-${DateTime.now().millisecondsSinceEpoch}-${hashCode}';
      await prefs.setString('unique_device_id', deviceId);
    }
    return deviceId;
  }
}
