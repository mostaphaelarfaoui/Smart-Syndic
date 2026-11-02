import 'dart:async';
import 'package:flutter/foundation.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/message_model.dart';

class MessageProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  List<MessageModel> _inbox = [];
  List<MessageModel> _sent = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _errorMessage;
  Timer? _pollingTimer;

  List<MessageModel> get inbox => _inbox;
  List<MessageModel> get sent => _sent;
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

        final inboxData = res['inbox'];
        if (inboxData is Map && inboxData['data'] is List) {
          _inbox = (inboxData['data'] as List)
              .map((e) => MessageModel.fromJson(e as Map<String, dynamic>))
              .toList();
          _inbox.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
        }

        final sentData = res['sent'];
        if (sentData is Map && sentData['data'] is List) {
          _sent = (sentData['data'] as List)
              .map((e) => MessageModel.fromJson(e as Map<String, dynamic>))
              .toList();
          _sent.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
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
        
        // Update local inbox read status & re-sort
        final idx = _inbox.indexWhere((m) => m.id == id);
        if (idx != -1) {
          _inbox[idx] = updatedMsg;
          _inbox.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
        }
        final sentIdx = _sent.indexWhere((m) => m.id == id);
        if (sentIdx != -1) {
          _sent[sentIdx] = updatedMsg;
          _sent.sort((a, b) => b.latestActivityAt.compareTo(a.latestActivityAt));
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
      _inbox = _inbox.map((m) => m.copyWith(isRead: true)).toList();
      notifyListeners();
    } catch (_) {}
  }
}
