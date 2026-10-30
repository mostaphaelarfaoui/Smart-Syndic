import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/user_model.dart';
import '../models/tenant_model.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  UserModel? _user;
  TenantModel? _tenant;
  bool _isLoading = false;
  String? _errorMessage;
  String? _loginErrorType;
  String? _fieldErrorEmail;
  String? _fieldErrorPassword;
  Map<String, dynamic>? _pendingContactInfo;
  Map<String, dynamic>? _pendingResidentData;
  Map<String, dynamic>? _suspendedAccountData;

  UserModel? get user => _user;
  TenantModel? get tenant => _tenant;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get loginErrorType => _loginErrorType;
  String? get fieldErrorEmail => _fieldErrorEmail;
  String? get fieldErrorPassword => _fieldErrorPassword;
  Map<String, dynamic>? get pendingContactInfo => _pendingContactInfo;
  Map<String, dynamic>? get pendingResidentData => _pendingResidentData;
  Map<String, dynamic>? get suspendedAccountData => _suspendedAccountData;
  bool get isAuthenticated => _user != null && _api.token != null;

  void clearLoginErrors() {
    _loginErrorType = null;
    _fieldErrorEmail = null;
    _fieldErrorPassword = null;
    _errorMessage = null;
    notifyListeners();
  }

  Future<void> checkAuth() async {
    _isLoading = true;
    notifyListeners();

    await _api.init();
    final prefs = await SharedPreferences.getInstance();
    final userJsonStr = prefs.getString('current_user');
    final tenantJsonStr = prefs.getString('current_tenant');

    if (userJsonStr != null && _api.token != null) {
      try {
        _user = UserModel.fromJson(jsonDecode(userJsonStr));
        if (tenantJsonStr != null) {
          _tenant = TenantModel.fromJson(jsonDecode(tenantJsonStr));
        }
      } catch (_) {
        await logout();
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    _loginErrorType = null;
    _fieldErrorEmail = null;
    _fieldErrorPassword = null;
    _pendingContactInfo = null;
    _pendingResidentData = null;
    _suspendedAccountData = null;
    notifyListeners();

    try {
      final response = await _api.post(ApiConstants.login, {
        'email': email,
        'password': password,
      });

      final token = response['token'];
      _user = UserModel.fromJson(response['user']);
      if (response['tenant'] != null) {
        _tenant = TenantModel.fromJson(response['tenant']);
      }

      await _api.setToken(token);

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('current_user', jsonEncode(_user!.toJson()));
      if (_tenant != null) {
        await prefs.setString('current_tenant', jsonEncode(_tenant!.toJson()));
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      if (e.data is Map) {
        final errType = e.data['error_type']?.toString();
        _loginErrorType = errType;

        if (errType == 'EMAIL_NOT_FOUND') {
          _fieldErrorEmail = e.data['message']?.toString() ?? 'البريد الإلكتروني غير مسجل في النظام.';
        } else if (errType == 'INVALID_PASSWORD') {
          _fieldErrorPassword = e.data['message']?.toString() ?? 'كلمة المرور غير صحيحة.';
        } else if (errType == 'ACCOUNT_LOCKED') {
          _errorMessage = e.data['message']?.toString() ?? 'تم قفل هذا الحساب مؤقتاً لأسباب أمنية.';
        } else if (errType == 'ACCOUNT_BLOCKED') {
          _errorMessage = e.data['message']?.toString() ?? 'تم حظر هذا الحساب نهائياً لتجاوز الحد الأقصى للمحاولات الخاطئة.';
        } else if (errType == 'RESIDENT_PENDING_APPROVAL') {
          _pendingResidentData = Map<String, dynamic>.from(e.data);
        } else if (errType == 'SYNDIC_PENDING_APPROVAL') {
          if (e.data['contact_info'] != null) {
            _pendingContactInfo = Map<String, dynamic>.from(e.data['contact_info']);
          }
        } else if (errType == 'RESIDENT_SUSPENDED' || errType == 'SYNDIC_SUSPENDED' || errType == 'ACCOUNT_SUSPENDED') {
          _suspendedAccountData = Map<String, dynamic>.from(e.data);
        } else if (e.statusCode == 403) {
          if (e.data['role'] == 'RESIDENT' && e.data['status'] == 'PENDING') {
            _pendingResidentData = Map<String, dynamic>.from(e.data);
          } else if (e.data['contact_info'] != null) {
            _pendingContactInfo = Map<String, dynamic>.from(e.data['contact_info']);
          } else {
            _suspendedAccountData = Map<String, dynamic>.from(e.data);
          }
        }
      }
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      final msg = e.toString().replaceAll('Exception: ', '');
      _errorMessage = msg;
      if (msg.contains('تعذر الاتصال') || msg.contains('SocketException') || msg.contains('TimeoutException')) {
        _loginErrorType = 'NETWORK_ERROR';
      } else {
        _loginErrorType = 'UNKNOWN_ERROR';
      }
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<Map<String, dynamic>?> verifyInviteCode(String code) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _api.post(ApiConstants.verifyInviteCode, {
        'invite_code': code,
      });
      _isLoading = false;
      notifyListeners();
      return response;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  Future<bool> registerResident({
    required String inviteCode,
    required String apartmentId,
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
    required String occupancyType,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _api.post(ApiConstants.registerResident, {
        'invite_code': inviteCode,
        'apartment_id': apartmentId,
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
        'password': password,
        'occupancy_type': occupancyType,
      });

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> registerSyndic({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
    required String organizationName,
    String? city,
    required String propertyType,
    int? totalUnits,
    int? totalFloors,
    int? unitsPerFloor,
    double? defaultMonthlyFee,
    String? bankRib,
    double? initialTreasuryBalance,
    double? initialBankBalance,
    String? numberingScheme,
    List<Map<String, dynamic>>? buildings,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final data = <String, dynamic>{
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
        'password': password,
        'organization_name': organizationName,
        if (city != null && city.isNotEmpty) 'city': city,
        'property_type': propertyType,
        if (totalUnits != null) 'total_units': totalUnits,
        if (totalFloors != null) 'total_floors': totalFloors,
        if (unitsPerFloor != null) 'units_per_floor': unitsPerFloor,
        if (defaultMonthlyFee != null) 'default_monthly_fee': defaultMonthlyFee,
        if (bankRib != null && bankRib.isNotEmpty) 'bank_rib': bankRib,
        if (initialTreasuryBalance != null) 'initial_treasury_balance': initialTreasuryBalance,
        if (initialBankBalance != null) 'initial_bank_balance': initialBankBalance,
        if (numberingScheme != null) 'numbering_scheme': numberingScheme,
        if (buildings != null && buildings.isNotEmpty) 'buildings': buildings,
      };

      final response = await _api.post(ApiConstants.registerSyndic, data);
      if (response is Map && response['contact_info'] != null) {
        _pendingContactInfo = Map<String, dynamic>.from(response['contact_info']);
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateProfile(Map<String, dynamic> data) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _api.put(ApiConstants.updateProfile, data);
      _user = UserModel.fromJson(response['user']);
      
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('current_user', jsonEncode(_user!.toJson()));
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> logoutOtherDevices() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _api.post(ApiConstants.logoutOtherDevices, {});
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    _user = null;
    _tenant = null;
    await _api.setToken(null);
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('current_user');
    await prefs.remove('current_tenant');
    notifyListeners();
  }
}
