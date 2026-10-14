import 'dart:async';
import 'package:flutter/material.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/notification_model.dart';

class NotificationProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  List<NotificationModel> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _errorMessage;
  Timer? _pollingTimer;

  List<NotificationModel> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  void startSilentPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 40), (_) {
      fetchNotifications(silent: true);
    });
  }

  void stopPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }

  Future<void> fetchNotifications({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    }

    try {
      final response = await _api.get(ApiConstants.notifications);
      if (response is Map) {
        _unreadCount = (response['unread_count'] as num?)?.toInt() ?? 0;
        final rawList = response['notifications'];
        if (rawList is List) {
          _notifications = rawList
              .map((item) => NotificationModel.fromJson(Map<String, dynamic>.from(item)))
              .toList();
        }
      }
    } catch (e) {
      if (!silent) {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      }
    } finally {
      if (!silent) {
        _isLoading = false;
      }
      notifyListeners();
    }
  }

  Future<void> markAsRead(String id) async {
    final index = _notifications.indexWhere((n) => n.id == id);
    if (index != -1 && !_notifications[index].isRead) {
      _notifications[index].isRead = true;
      if (_unreadCount > 0) _unreadCount--;
      notifyListeners();
    }

    try {
      final res = await _api.post(ApiConstants.notificationRead(id), {});
      if (res is Map && res['unread_count'] != null) {
        _unreadCount = (res['unread_count'] as num).toInt();
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> markAllAsRead() async {
    _unreadCount = 0;
    for (var n in _notifications) {
      n.isRead = true;
    }
    notifyListeners();

    try {
      final res = await _api.post(ApiConstants.notificationMarkAllRead, {});
      if (res is Map && res['unread_count'] != null) {
        _unreadCount = (res['unread_count'] as num).toInt();
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> deleteNotification(String id) async {
    final index = _notifications.indexWhere((n) => n.id == id);
    if (index != -1) {
      final wasUnread = !_notifications[index].isRead;
      _notifications.removeAt(index);
      if (wasUnread && _unreadCount > 0) _unreadCount--;
      notifyListeners();
    }

    try {
      final res = await _api.delete(ApiConstants.notificationDelete(id));
      if (res is Map && res['unread_count'] != null) {
        _unreadCount = (res['unread_count'] as num).toInt();
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> clearAll() async {
    _notifications.clear();
    _unreadCount = 0;
    notifyListeners();

    try {
      await _api.delete(ApiConstants.notificationClearAll);
    } catch (_) {}
  }

  void clear() {
    stopPolling();
    _notifications = [];
    _unreadCount = 0;
    _isLoading = false;
    _errorMessage = null;
    notifyListeners();
  }

  @override
  void dispose() {
    stopPolling();
    super.dispose();
  }
}
