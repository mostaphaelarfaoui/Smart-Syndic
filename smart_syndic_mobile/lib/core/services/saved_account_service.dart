import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class SavedAccount {
  final String email;
  final String password;
  final String name;
  final String role;
  final String? residenceName;
  final DateTime lastUsed;

  SavedAccount({
    required this.email,
    required this.password,
    required this.name,
    required this.role,
    this.residenceName,
    required this.lastUsed,
  });

  Map<String, dynamic> toJson() => {
        'email': email,
        'password': password,
        'name': name,
        'role': role,
        'residenceName': residenceName,
        'lastUsed': lastUsed.toIso8601String(),
      };

  factory SavedAccount.fromJson(Map<String, dynamic> json) => SavedAccount(
        email: json['email'] as String? ?? '',
        password: json['password'] as String? ?? '',
        name: json['name'] as String? ?? '',
        role: json['role'] as String? ?? 'RESIDENT',
        residenceName: json['residenceName'] as String?,
        lastUsed: json['lastUsed'] != null
            ? DateTime.tryParse(json['lastUsed']) ?? DateTime.now()
            : DateTime.now(),
      );

  String get roleDisplay {
    switch (role) {
      case 'ADMIN_SYNDIC':
        return 'سانديك الإقامة';
      case 'RESIDENT':
        return 'ساكن';
      case 'SUPER_ADMIN':
        return 'سوبر أدمن';
      default:
        return role;
    }
  }
}

class SavedAccountService {
  static const String _keyAccounts = 'saved_login_accounts_list';
  static const String _keyRememberMe = 'remember_login_credentials_enabled';

  /// Check if the "Remember login info" checkbox is enabled (defaults to true)
  static Future<bool> isRememberEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_keyRememberMe) ?? true;
  }

  /// Update the "Remember login info" preference
  static Future<void> setRememberEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_keyRememberMe, enabled);
  }

  /// Retrieve all saved accounts, sorted by last used date descending
  static Future<List<SavedAccount>> getSavedAccounts() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_keyAccounts);
      if (raw == null || raw.isEmpty) return [];

      final list = jsonDecode(raw) as List<dynamic>;
      final accounts = list
          .map((item) => SavedAccount.fromJson(item as Map<String, dynamic>))
          .where((acc) => acc.email.isNotEmpty)
          .toList();

      accounts.sort((a, b) => b.lastUsed.compareTo(a.lastUsed));
      return accounts;
    } catch (_) {
      return [];
    }
  }

  /// Save or update an account in history
  static Future<void> saveAccount({
    required String email,
    required String password,
    required String name,
    required String role,
    String? residenceName,
  }) async {
    try {
      final accounts = await getSavedAccounts();
      final cleanEmail = email.trim().toLowerCase();

      // Remove existing entry if any
      accounts.removeWhere((acc) => acc.email.trim().toLowerCase() == cleanEmail);

      // Add as newest at the top
      accounts.insert(
        0,
        SavedAccount(
          email: cleanEmail,
          password: password,
          name: name.trim().isNotEmpty ? name.trim() : cleanEmail,
          role: role,
          residenceName: residenceName,
          lastUsed: DateTime.now(),
        ),
      );

      final prefs = await SharedPreferences.getInstance();
      final encoded = jsonEncode(accounts.map((a) => a.toJson()).toList());
      await prefs.setString(_keyAccounts, encoded);
    } catch (_) {}
  }

  /// Remove a specific account from history
  static Future<void> removeAccount(String email) async {
    try {
      final accounts = await getSavedAccounts();
      final cleanEmail = email.trim().toLowerCase();
      accounts.removeWhere((acc) => acc.email.trim().toLowerCase() == cleanEmail);

      final prefs = await SharedPreferences.getInstance();
      final encoded = jsonEncode(accounts.map((a) => a.toJson()).toList());
      await prefs.setString(_keyAccounts, encoded);
    } catch (_) {}
  }

  /// Clear all saved accounts
  static Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyAccounts);
  }
}
