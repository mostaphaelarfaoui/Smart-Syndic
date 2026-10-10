import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  final ValueNotifier<bool> isNetworkActive = ValueNotifier(false);
  int _activeRequests = 0;

  void _startRequest() {
    _activeRequests++;
    if (_activeRequests == 1) isNetworkActive.value = true;
  }

  void _endRequest() {
    _activeRequests--;
    if (_activeRequests <= 0) {
      _activeRequests = 0;
      isNetworkActive.value = false;
    }
  }

  String? _token;
  String _baseUrl = ApiConstants.defaultBaseUrl;

  String get baseUrl => _baseUrl;
  String? get token => _token;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    _baseUrl = prefs.getString('custom_base_url') ?? ApiConstants.defaultBaseUrl;
  }

  Future<void> setBaseUrl(String newUrl) async {
    _baseUrl = newUrl;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('custom_base_url', newUrl);
  }

  String resolveMediaUrl(String? url) {
    if (url == null || url.isEmpty) return '';
    try {
      final serverUri = Uri.parse(_baseUrl);
      final targetUri = Uri.parse(url);
      if (targetUri.host == 'localhost' || targetUri.host == '127.0.0.1') {
        return targetUri.replace(
          host: serverUri.host,
          port: serverUri.port,
        ).toString();
      }
    } catch (_) {}
    return url;
  }

  Future<void> setToken(String? token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    if (token != null) {
      await prefs.setString('auth_token', token);
    } else {
      await prefs.remove('auth_token');
    }
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Accept-Language': 'ar',
        'Bypass-Tunnel-Reminder': 'true',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<dynamic> get(String endpoint) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    try {
      final response = await http.get(url, headers: _headers);
      return _handleResponse(response);
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم: $e');
    } finally {
      _endRequest();
    }
  }

  Future<dynamic> post(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    try {
      final response = await http.post(
        url,
        headers: _headers,
        body: jsonEncode(data),
      );
      return _handleResponse(response);
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم: $e');
    } finally {
      _endRequest();
    }
  }

  Future<dynamic> put(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    try {
      final response = await http.put(
        url,
        headers: _headers,
        body: jsonEncode(data),
      );
      return _handleResponse(response);
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم: $e');
    } finally {
      _endRequest();
    }
  }

  dynamic _handleResponse(http.Response response) {
    dynamic body;
    try {
      body = jsonDecode(response.body);
    } catch (_) {
      body = response.body;
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    } else if (response.statusCode == 401) {
      throw Exception('انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً.');
    } else if (response.statusCode == 422) {
      if (body is Map && body['message'] != null) {
        throw Exception(body['message']);
      }
      throw Exception('بيانات غير صحيحة، يرجى مراجعة المدخلات.');
    } else {
      if (body is Map && body['message'] != null) {
        throw Exception(body['message']);
      }
      throw Exception('حدث خطأ في الخادم (${response.statusCode})');
    }
  }
}
