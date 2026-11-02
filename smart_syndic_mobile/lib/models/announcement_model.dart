class AnnouncementModel {
  final String id;
  final String tenantId;
  final String? createdById;
  final String creatorName;
  final String title;
  final String content;
  final String priority;
  final bool isPinned;
  final DateTime createdAt;

  AnnouncementModel({
    required this.id,
    required this.tenantId,
    this.createdById,
    required this.creatorName,
    required this.title,
    required this.content,
    required this.priority,
    required this.isPinned,
    required this.createdAt,
  });

  factory AnnouncementModel.fromJson(Map<String, dynamic> json) {
    String creator = 'إدارة السانديك';
    if (json['creator_name'] != null) {
      creator = json['creator_name'].toString();
    } else if (json['creator'] is Map && json['creator']['full_name'] != null) {
      creator = json['creator']['full_name'].toString();
    }

    return AnnouncementModel(
      id: json['id']?.toString() ?? '',
      tenantId: json['tenant_id']?.toString() ?? '',
      createdById: json['created_by']?.toString(),
      creatorName: creator,
      title: json['title']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
      priority: json['priority']?.toString() ?? 'NORMAL',
      isPinned: json['is_pinned'] == true || json['is_pinned'] == 1,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  bool get isUrgent => priority == 'URGENT';
  bool get isInfo => priority == 'INFO';

  String get priorityLabel {
    switch (priority) {
      case 'URGENT':
        return '🚨 عاجل';
      case 'INFO':
        return 'ℹ️ تنبيه هام';
      default:
        return '📢 إعلان عام';
    }
  }
}
