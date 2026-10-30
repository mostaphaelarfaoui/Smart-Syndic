import 'package:flutter/foundation.dart';

class ApiConstants {
  // Default baseUrl. For local testing:
  // - Android Emulator: 'http://10.0.2.2:8000/api/v1'
  // - Physical Phone: 'http://192.168.11.203:8000/api/v1'
  // - Windows / Web: 'http://127.0.0.1:8000/api/v1'
  static String get defaultBaseUrl =>
      kIsWeb ? 'http://127.0.0.1:8000/api/v1' : 'http://192.168.11.203:8000/api/v1';

  // Auth endpoints
  static const String login = '/auth/login';
  static const String registerResident = '/auth/register-resident';
  static const String registerSyndic = '/auth/register-syndic';
  static const String verifyInviteCode = '/auth/verify-invite-code';
  static const String updateProfile = '/auth/profile';
  static const String logoutOtherDevices = '/auth/logout-other-devices';

  // Syndic endpoints
  static const String syndicKpis = '/syndic/dashboard/kpis';
  static const String syndicApartments = '/syndic/apartments';
  static const String syndicBulkApartments = '/syndic/apartments/bulk-generate';
  static const String syndicPendingResidents = '/syndic/pending-residents';
  static const String syndicPayments = '/syndic/payments';
  static const String syndicExpenses = '/syndic/expenses';
  static const String syndicSuggestions = '/syndic/suggestions';
  static const String syndicPolls = '/syndic/polls';
  static const String syndicInviteCodeRegen = '/syndic/invitation-code/regenerate';
  static const String syndicReportsExport = '/syndic/reports/export';

  // Feature 1 — Tenant settings & stamp
  static const String syndicSettings = '/syndic/settings';
  static const String syndicStampUpload = '/syndic/settings/stamp';

  // Feature 4 — Toggle apartment billable
  static String apartmentToggleBillable(String id) => '/syndic/apartments/$id/toggle-billable';
  static String apartmentVacate(String id) => '/syndic/apartments/$id/vacate';

  // Feature 12 — Payment workflow
  static String paymentConfirm(String id) => '/syndic/payments/$id/confirm';
  static String paymentSend(String id) => '/syndic/payments/$id/send';

  // Resident endpoints
  static const String residentProfile = '/resident/profile';
  static const String residentBalance = '/resident/my-balance';
  static const String residentPayments = '/resident/payments';
  static const String residentTransparency = '/resident/transparency-ledger';
  static const String residentSuggestions = '/resident/suggestions';
  static const String residentPolls = '/resident/polls';

  // Notifications endpoints
  static const String notifications = '/notifications';
  static String notificationRead(String id) => '/notifications/$id/read';
  static const String notificationMarkAllRead = '/notifications/mark-all-read';
  static String notificationDelete(String id) => '/notifications/$id';
  static const String notificationClearAll = '/notifications/clear-all';

  // Security & Password verification for sensitive actions
  static const String verifyPassword = '/auth/verify-password';
}
