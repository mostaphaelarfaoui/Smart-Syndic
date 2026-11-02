import 'dart:async';
import 'package:flutter/foundation.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/message_model.dart';

class MessageProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  List<MessageModel> _conversations = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _errorMessage;
  Timer? _pollingTimer;

  List<MessageModel> get conversations => _conversations;
  List<MessageModel> get inbox => _conversations;
  List<MessageModel> get sent => _conversations;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  void startSilentPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 35), (_) {
      fetchMessages(silent: true);
    });
  }

  Future<void> fetchMessages({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    }

    try {
      final res = await _api.get(ApiConstants.messages);
      if (res is Map<String, dynamic>) {
        _unreadCount = res['unread_count'] ?? 0;

        final convData = res['conversations'] ?? res['inbox'];
        if (convData is Map && convData['data'] is List) {
          _conversations = (convData['data'] as List)
              .map((e) => MessageModel.fromJson(e as Map<String, dynamic>))
              .toList();
          _conversations.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
        }
      }
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<MessageModel?> fetchMessageDetails(String id) async {
    try {
      final res = await _api.get(ApiConstants.messageDetails(id));
      if (res is Map<String, dynamic> && res['message'] != null) {
        if (res.containsKey('unread_count')) {
          _unreadCount = res['unread_count'];
        }
        final updatedMsg = MessageModel.fromJson(res['message'] as Map<String, dynamic>);
        
        // Update local conversation read status & re-sort
        final idx = _conversations.indexWhere((m) => m.id == id);
        if (idx != -1) {
          _conversations[idx] = updatedMsg;
          _conversations.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
        } else {
          _conversations.insert(0, updatedMsg);
          _conversations.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
        }
        notifyListeners();
        return updatedMsg;
      }
    } catch (_) {}
    return null;
  }

  Future<bool> sendMessage({
    required String subject,
    required String body,
    String category = 'GENERAL',
    String priority = 'NORMAL',
    String targetType = 'ALL_RESIDENTS',
    List<String>? targetIds,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _api.post(ApiConstants.messages, {
        'subject': subject,
        'body': body,
        'category': category,
        'priority': priority,
        'target_type': targetType,
        if (targetIds != null && targetIds.isNotEmpty) 'target_ids': targetIds,
      });

      await fetchMessages(silent: true);
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> sendReply({
    required String messageId,
    required String body,
  }) async {
    try {
      await _api.post(ApiConstants.messageReply(messageId), {
        'body': body,
      });

      await fetchMessages(silent: true);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<void> markAllAsRead() async {
    try {
      await _api.post(ApiConstants.messageMarkAllRead, {});
      _unreadCount = 0;
      _conversations = _conversations.map((m) => m.copyWith(isRead: true)).toList();
      notifyListeners();
    } catch (_) {}
  }
}
