class UserModel {
  final String id;
  final String firstName;
  final String lastName;
  final String email;
  final String phone;
  final String role; // 'ADMIN_SYNDIC', 'RESIDENT', 'SUPER_ADMIN'
  final String locale;
  final String themePreference;
  final Map<String, dynamic> notificationPreferences;
  final String status;

  UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.phone,
    required this.role,
    required this.locale,
    this.themePreference = 'light',
    this.notificationPreferences = const {
      'payments': true,
      'residents': true,
      'polls': true,
      'announcements': true,
    },
    required this.status,
  });

  String get fullName => '$firstName $lastName';
  bool get isSyndic => role == 'ADMIN_SYNDIC' || role == 'SUPER_ADMIN';
  bool get isResident => role == 'RESIDENT';

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? '',
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'] ?? '',
      role: json['role'] ?? 'RESIDENT',
      locale: json['locale'] ?? 'ar',
      themePreference: json['theme_preference'] ?? 'light',
      notificationPreferences: json['notification_preferences'] is Map
          ? Map<String, dynamic>.from(json['notification_preferences'])
          : const {
              'payments': true,
              'residents': true,
              'polls': true,
              'announcements': true,
            },
      status: json['status'] ?? 'ACTIVE',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'role': role,
      'locale': locale,
      'theme_preference': themePreference,
      'notification_preferences': notificationPreferences,
      'status': status,
    };
  }
}
