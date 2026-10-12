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
  Map<String, dynamic>? _pendingContactInfo;
  Map<String, dynamic>? _pendingResidentData;

  UserModel? get user => _user;
  TenantModel? get tenant => _tenant;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  Map<String, dynamic>? get pendingContactInfo => _pendingContactInfo;
  Map<String, dynamic>? get pendingResidentData => _pendingResidentData;
  bool get isAuthenticated => _user != null && _api.token != null;

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
    _pendingContactInfo = null;
    _pendingResidentData = null;
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
      if (e.statusCode == 403 && e.data is Map) {
        if (e.data['role'] == 'RESIDENT' && e.data['status'] == 'PENDING') {
          _pendingResidentData = Map<String, dynamic>.from(e.data);
        } else if (e.data['contact_info'] != null) {
          _pendingContactInfo = Map<String, dynamic>.from(e.data['contact_info']);
        }
      }
      _errorMessage = e.message;
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
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
    required String propertyType,
    int? totalUnits,
    int? totalFloors,
    int? unitsPerFloor,
    double? defaultMonthlyFee,
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
        'property_type': propertyType,
        if (totalUnits != null) 'total_units': totalUnits,
        if (totalFloors != null) 'total_floors': totalFloors,
        if (unitsPerFloor != null) 'units_per_floor': unitsPerFloor,
        if (defaultMonthlyFee != null) 'default_monthly_fee': defaultMonthlyFee,
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
