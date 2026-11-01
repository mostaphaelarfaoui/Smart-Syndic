class MessageModel {
  final String id;
  final String subject;
  final String body;
  final String category;
  final String priority;
  final String senderId;
  final String senderName;
  final String senderRole;
  final String targetType;
  final Map<String, dynamic>? targetMetadata;
  final bool isRead;
  final DateTime createdAt;
  final List<MessageModel> replies;

  MessageModel({
    required this.id,
    required this.subject,
    required this.body,
    required this.category,
    required this.priority,
    required this.senderId,
    required this.senderName,
    required this.senderRole,
    required this.targetType,
    this.targetMetadata,
    required this.isRead,
    required this.createdAt,
    this.replies = const [],
  });

  bool get isFromSuperAdmin => senderRole == 'SUPER_ADMIN';
  bool get isFromSyndic => senderRole == 'ADMIN_SYNDIC' || senderRole == 'SYNDIC';
  bool get isFromResident => senderRole == 'RESIDENT';

  String get categoryLabel {
    switch (category) {
      case 'ANNOUNCEMENT':
        return 'إعلان رسمي';
      case 'INQUIRY':
        return 'استفسار';
      case 'COMPLAINT':
        return 'شكاية';
      case 'MAINTENANCE':
        return 'صيانة وأشغال';
      case 'FINANCIAL':
        return 'شؤون مالية';
      default:
        return 'عامة';
    }
  }

  factory MessageModel.fromJson(Map<String, dynamic> json) {
    final senderObj = json['sender'] is Map ? json['sender'] as Map<String, dynamic> : null;
    final senderName = json['sender_name'] ??
        senderObj?['full_name'] ??
        (json['sender_role'] == 'SUPER_ADMIN' ? 'الإدارة العامة للمنصة' : 'مستخدم');

    final repliesList = (json['replies'] as List?)
            ?.map((r) => MessageModel.fromJson(r as Map<String, dynamic>))
            .toList() ??
        [];

    // is_read can come from recipient pivot or top-level field
    bool isRead = json['is_read'] ?? false;
    if (json['recipients'] is List && (json['recipients'] as List).isNotEmpty) {
      final r = (json['recipients'] as List).first;
      if (r is Map && r.containsKey('is_read')) {
        isRead = r['is_read'] == true || r['is_read'] == 1;
      }
    }
    if (json['pivot'] is Map && (json['pivot'] as Map).containsKey('is_read')) {
      isRead = json['pivot']['is_read'] == true || json['pivot']['is_read'] == 1;
    }

    return MessageModel(
      id: json['id']?.toString() ?? '',
      subject: json['subject']?.toString() ?? '',
      body: json['body']?.toString() ?? '',
      category: json['category']?.toString() ?? 'GENERAL',
      priority: json['priority']?.toString() ?? 'NORMAL',
      senderId: json['sender_id']?.toString() ?? '',
      senderName: senderName,
      senderRole: json['sender_role']?.toString() ?? 'RESIDENT',
      targetType: json['target_type']?.toString() ?? 'ALL_RESIDENTS',
      targetMetadata: json['target_metadata'] is Map ? json['target_metadata'] as Map<String, dynamic> : null,
      isRead: isRead,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at']) ?? DateTime.now() : DateTime.now(),
      replies: repliesList,
    );
  }
}
