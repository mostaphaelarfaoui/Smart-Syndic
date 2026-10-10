import 'package:flutter/material.dart';

class SuggestionModel {
  final String id;
  final String title;
  final String description;
  final String status; // 'PENDING', 'ACCEPTED', 'REJECTED', 'CONVERTED_TO_POLL'
  final String? adminNotes;
  final String createdAt;
  final String? userName;
  final String? apartmentUnit;

  SuggestionModel({
    required this.id,
    required this.title,
    required this.description,
    required this.status,
    this.adminNotes,
    required this.createdAt,
    this.userName,
    this.apartmentUnit,
  });

  String get statusArabic {
    switch (status) {
      case 'PENDING':
        return 'قيد المراجعة';
      case 'ACCEPTED':
        return 'مقبول';
      case 'REJECTED':
        return 'مرفوض';
      case 'CONVERTED_TO_POLL':
        return 'تم تحويله لتصويت';
      default:
        return status;
    }
  }

  Color get statusColor {
    switch (status) {
      case 'ACCEPTED':
        return const Color(0xFF10B981);
      case 'REJECTED':
        return const Color(0xFFEF4444);
      case 'CONVERTED_TO_POLL':
        return const Color(0xFF3B82F6);
      default:
        return const Color(0xFFF59E0B);
    }
  }

  factory SuggestionModel.fromJson(Map<String, dynamic> json) {
    return SuggestionModel(
      id: json['id'] ?? '',
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      status: json['status'] ?? 'PENDING',
      adminNotes: json['admin_notes'],
      createdAt: json['created_at'] ?? '',
      userName: json['user'] != null ? '${json['user']['first_name'] ?? ''} ${json['user']['last_name'] ?? ''}'.trim() : null,
      apartmentUnit: json['apartment']?['unit_number'],
    );
  }
}
