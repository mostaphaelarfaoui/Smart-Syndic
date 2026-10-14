class NotificationModel {
  final String id;
  final String title;
  final String message;
  final String type;
  final String icon;
  final String? url;
  final String? targetScreen;
  final String? targetId;
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
    required this.isRead,
    this.readAt,
    required this.timeAgo,
    this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'إشعار جديد',
      message: json['message']?.toString() ?? '',
      type: json['type']?.toString() ?? 'info',
      icon: json['icon']?.toString() ?? '🔔',
      url: json['url']?.toString(),
      targetScreen: json['target_screen']?.toString() ?? json['extra']?['target_screen']?.toString(),
      targetId: json['target_id']?.toString() ?? json['extra']?['target_id']?.toString(),
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
      'is_read': isRead,
      'read_at': readAt,
      'time_ago': timeAgo,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
