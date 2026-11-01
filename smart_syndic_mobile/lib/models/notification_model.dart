class NotificationModel {
  final String id;
  final String title;
  final String message;
  final String type;
  final String icon;
  final String? url;
  final String? targetScreen;
  final String? targetId;
  final String? actorName;
  final String? actorRole;
  final String? actorLabel;
  final String? recipientName;
  final String? recipientLabel;
  final String? unitNumber;
  final String? unitLabel;
  final String? buildingName;
  final Map<String, dynamic>? extra;
  bool isRead;
  final String? readAt;
  final String timeAgo;
  final DateTime? createdAt;

  NotificationModel({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.icon,
    this.url,
    this.targetScreen,
    this.targetId,
    this.actorName,
    this.actorRole,
    this.actorLabel,
    this.recipientName,
    this.recipientLabel,
    this.unitNumber,
    this.unitLabel,
    this.buildingName,
    this.extra,
    required this.isRead,
    this.readAt,
    required this.timeAgo,
    this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    final extra = json['extra'] is Map ? json['extra'] as Map<String, dynamic> : null;
    final actorName = json['actor_name']?.toString() ?? extra?['actor_name']?.toString();
    final actorRole = json['actor_role']?.toString() ?? extra?['actor_role']?.toString();
    final actorLabel = json['actor_label']?.toString() ?? extra?['actor_label']?.toString();
    final recipientName = json['recipient_name']?.toString() ?? extra?['recipient_name']?.toString();
    final recipientLabel = json['recipient_label']?.toString() ?? extra?['recipient_label']?.toString();
    final unitNumber = json['unit_number']?.toString() ?? extra?['unit_number']?.toString();
    final unitLabel = json['unit_label']?.toString() ?? extra?['unit_label']?.toString();
    final buildingName = json['building_name']?.toString() ?? extra?['building_name']?.toString();

    return NotificationModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'إشعار جديد',
      message: json['message']?.toString() ?? '',
      type: json['type']?.toString() ?? 'info',
      icon: json['icon']?.toString() ?? '🔔',
      url: json['url']?.toString(),
      targetScreen: json['target_screen']?.toString() ?? extra?['target_screen']?.toString(),
      targetId: json['target_id']?.toString() ?? extra?['target_id']?.toString(),
      actorName: actorName,
      actorRole: actorRole,
      actorLabel: actorLabel,
      recipientName: recipientName,
      recipientLabel: recipientLabel,
      unitNumber: unitNumber,
      unitLabel: unitLabel,
      buildingName: buildingName,
      extra: extra,
      isRead: json['is_read'] == true,
      readAt: json['read_at']?.toString(),
      timeAgo: json['time_ago']?.toString() ?? '',
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'message': message,
      'type': type,
      'icon': icon,
      'url': url,
      'target_screen': targetScreen,
      'target_id': targetId,
      'actor_name': actorName,
      'actor_role': actorRole,
      'actor_label': actorLabel,
      'recipient_name': recipientName,
      'recipient_label': recipientLabel,
      'unit_number': unitNumber,
      'unit_label': unitLabel,
      'building_name': buildingName,
      'extra': extra,
      'is_read': isRead,
      'read_at': readAt,
      'time_ago': timeAgo,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
