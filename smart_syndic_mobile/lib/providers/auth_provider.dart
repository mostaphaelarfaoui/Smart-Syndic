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

  UserModel? get user => _user;
  TenantModel? get tenant => _tenant;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
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
