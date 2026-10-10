class ApiConstants {
  // Default baseUrl. For local testing:
  // - Android Emulator: 'http://10.0.2.2:8000/api/v1'
  // - Physical Phone: 'http://<YOUR_PC_IP>:8000/api/v1'
  // - Windows / Web: 'http://127.0.0.1:8000/api/v1'
  static const String defaultBaseUrl = 'http://192.168.11.168:8000/api/v1'; // Fast local Wi-Fi connection

  // Auth endpoints
  static const String login = '/auth/login';
  static const String registerResident = '/auth/register-resident';
  static const String registerSyndic = '/auth/register-syndic';
  static const String verifyInviteCode = '/auth/verify-invite-code';
  static const String updateProfile = '/auth/profile';

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

  // Resident endpoints
  static const String residentProfile = '/resident/profile';
  static const String residentBalance = '/resident/my-balance';
  static const String residentPayments = '/resident/payments';
  static const String residentTransparency = '/resident/transparency-ledger';
  static const String residentSuggestions = '/resident/suggestions';
  static const String residentPolls = '/resident/polls';
}
