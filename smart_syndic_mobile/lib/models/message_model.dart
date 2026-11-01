import '../core/utils/number_utils.dart';

class MessageModel {
  final String id;
  final String subject;
  final String body;
  final String category;
  final String priority;
  final String senderId;
  final String senderName;
  final String senderDisplayName;
  final String senderRole;
  final String senderRoleLabel;
  final String? senderUnitLabel;
  final String targetType;
  final String recipientLabel;
  final String? recipientName;
  final String? recipientUnitLabel;
  final String? residentPhone;
  final String? buildingName;
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
    String? senderDisplayName,
    required this.senderRole,
    String? senderRoleLabel,
    this.senderUnitLabel,
    required this.targetType,
    String? recipientLabel,
    this.recipientName,
    this.recipientUnitLabel,
    this.residentPhone,
    this.buildingName,
    this.targetMetadata,
    required this.isRead,
    required this.createdAt,
    this.replies = const [],
  })  : senderDisplayName = senderDisplayName ?? senderName,
        senderRoleLabel = senderRoleLabel ??
            (senderRole == 'SUPER_ADMIN'
                ? 'الإدارة العامة'
                : (senderRole == 'RESIDENT' ? 'ساكن مقيم' : 'إدارة السانديك')),
        recipientLabel = recipientLabel ?? 'المستلم';

  MessageModel copyWith({
    bool? isRead,
    List<MessageModel>? replies,
  }) {
    return MessageModel(
      id: id,
      subject: subject,
      body: body,
      category: category,
      priority: priority,
      senderId: senderId,
      senderName: senderName,
      senderDisplayName: senderDisplayName,
      senderRole: senderRole,
      senderRoleLabel: senderRoleLabel,
      senderUnitLabel: senderUnitLabel,
      targetType: targetType,
      recipientLabel: recipientLabel,
      recipientName: recipientName,
      recipientUnitLabel: recipientUnitLabel,
      residentPhone: residentPhone,
      buildingName: buildingName,
      targetMetadata: targetMetadata,
      isRead: isRead ?? this.isRead,
      createdAt: createdAt,
      replies: replies ?? this.replies,
    );
  }

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
    
    // Sender Name & Display Name
    final rawSenderDisplayName = json['sender_display_name']?.toString();
    final senderName = json['sender_name']?.toString() ??
        senderObj?['full_name']?.toString() ??
        (json['sender_role'] == 'SUPER_ADMIN' ? 'الإدارة العامة للمنصة' : 'مستخدم');
    final senderDisplayName = rawSenderDisplayName ?? senderName;

    // Sender Role
    final senderRole = json['sender_role']?.toString() ?? 'RESIDENT';
    final senderRoleLabel = json['sender_role_label']?.toString() ??
        (senderRole == 'SUPER_ADMIN'
            ? 'الإدارة العامة'
            : (senderRole == 'RESIDENT' ? 'ساكن مقيم' : 'إدارة السانديك'));

    // Target Metadata
    final targetMetadata = json['target_metadata'] is Map ? json['target_metadata'] as Map<String, dynamic> : null;

    // Sender Unit
    String? senderUnitLabel = json['sender_unit_label']?.toString();
    if (senderUnitLabel == null || senderUnitLabel.isEmpty) {
      final unitNum = targetMetadata?['unit_number']?.toString();
      if (unitNum != null && unitNum.isNotEmpty && unitNum != 'غير محدد') {
        senderUnitLabel = formatUnitLabel(unitNum);
      }
    }

    // Target Type & Recipient Label
    final targetType = json['target_type']?.toString() ?? 'ALL_RESIDENTS';
    String recipientLabel = json['recipient_label']?.toString() ?? '';
    if (recipientLabel.isEmpty) {
      if (targetType == 'ALL_RESIDENTS') {
        recipientLabel = 'كافة سكان الإقامة';
      } else if (targetType == 'SYNDIC' || targetType == 'SYNDICS_ONLY') {
        recipientLabel = 'إدارة السانديك';
      } else if (targetType == 'INDIVIDUAL') {
        final rName = targetMetadata?['recipient_name']?.toString();
        final rUnit = targetMetadata?['unit_number']?.toString();
        if (rName != null && rUnit != null && rUnit.isNotEmpty) {
          recipientLabel = '$rName (${formatUnitLabel(rUnit)})';
        } else {
          recipientLabel = rName ?? 'ساكن محدد';
        }
      } else if (targetType == 'GROUP') {
        final targetedUnits = targetMetadata?['targeted_units'];
        if (targetedUnits is List && targetedUnits.isNotEmpty) {
          final formatted = targetedUnits.map((u) => formatUnitLabel(u.toString())).join(', ');
          recipientLabel = 'مجموعة شقق: $formatted';
        } else {
          recipientLabel = 'مجموعة شقق محددة';
        }
      } else {
        recipientLabel = 'المستلم';
      }
    }

    final recipientName = json['recipient_name']?.toString() ?? targetMetadata?['recipient_name']?.toString();
    final recipientUnitLabel = json['recipient_unit_label']?.toString() ??
        (targetMetadata?['unit_number'] != null ? formatUnitLabel(targetMetadata?['unit_number'].toString()) : null);

    final residentPhone = targetMetadata?['resident_phone']?.toString() ?? senderObj?['phone']?.toString();
    final buildingName = targetMetadata?['building_name']?.toString();

    final repliesList = (json['replies'] as List?)
            ?.map((r) => MessageModel.fromJson(r as Map<String, dynamic>))
            .toList() ??
        [];

    // is_read can come from recipient pivot or top-level field
    bool isRead = json['is_read'] == true || json['is_read'] == 1;
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
      senderDisplayName: senderDisplayName,
      senderRole: senderRole,
      senderRoleLabel: senderRoleLabel,
      senderUnitLabel: senderUnitLabel,
      targetType: targetType,
      recipientLabel: recipientLabel,
      recipientName: recipientName,
      recipientUnitLabel: recipientUnitLabel,
      residentPhone: residentPhone,
      buildingName: buildingName,
      targetMetadata: targetMetadata,
      isRead: isRead,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
      replies: repliesList,
    );
  }
}
