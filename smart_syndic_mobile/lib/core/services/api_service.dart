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
    http.Response response;
    try {
      response = await http.get(url, headers: _headers).timeout(const Duration(seconds: 15));
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم ($_baseUrl): يرجى التأكد من تشغيل السيرفر وعنوان الـ IP.');
    } finally {
      _endRequest();
    }
    return _handleResponse(response);
  }

  Future<dynamic> post(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    http.Response response;
    try {
      response = await http.post(
        url,
        headers: _headers,
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 15));
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم ($_baseUrl): يرجى التأكد من تشغيل السيرفر وعنوان الـ IP.');
    } finally {
      _endRequest();
    }
    return _handleResponse(response);
  }

  Future<dynamic> put(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    http.Response response;
    try {
      response = await http.put(
        url,
        headers: _headers,
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 15));
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم ($_baseUrl): يرجى التأكد من تشغيل السيرفر وعنوان الـ IP.');
    } finally {
      _endRequest();
    }
    return _handleResponse(response);
  }

  Future<dynamic> delete(String endpoint) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    http.Response response;
    try {
      response = await http.delete(
        url,
        headers: _headers,
      ).timeout(const Duration(seconds: 15));
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم ($_baseUrl): يرجى التأكد من تشغيل السيرفر وعنوان الـ IP.');
    } finally {
      _endRequest();
    }
    return _handleResponse(response);
  }

  Future<dynamic> postMultipart(
    String endpoint,
    Map<String, String> fields, {
    String? fileField,
    String? filePath,
  }) async {
    final url = Uri.parse('$_baseUrl$endpoint');
    _startRequest();
    http.Response response;
    try {
      final request = http.MultipartRequest('POST', url);
      request.headers.addAll({
        'Accept': 'application/json',
        'Accept-Language': 'ar',
        'Bypass-Tunnel-Reminder': 'true',
        if (_token != null) 'Authorization': 'Bearer $_token',
      });

      request.fields.addAll(fields);

      if (fileField != null && filePath != null && filePath.isNotEmpty) {
        request.files.add(await http.MultipartFile.fromPath(fileField, filePath));
      }

      final streamedResponse = await request.send().timeout(const Duration(seconds: 25));
      response = await http.Response.fromStream(streamedResponse);
    } catch (e) {
      throw Exception('تعذر الاتصال بالخادم ($_baseUrl): يرجى التأكد من تشغيل السيرفر وعنوان الـ IP.');
    } finally {
      _endRequest();
    }
    return _handleResponse(response);
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
      throw ApiException('انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً.', statusCode: 401, data: body);
    } else if (response.statusCode == 422) {
      if (body is Map) {
        if (body['errors'] is Map) {
          final errMap = body['errors'] as Map;
          final List<String> errorList = [];
          errMap.forEach((_, val) {
            if (val is List) {
              errorList.addAll(val.map((e) => e.toString()));
            } else if (val != null) {
              errorList.add(val.toString());
            }
          });
          if (errorList.isNotEmpty) {
            throw ApiException(errorList.join('\n'), statusCode: 422, data: body);
          }
        }
        if (body['message'] != null) {
          throw ApiException(body['message'].toString(), statusCode: 422, data: body);
        }
      }
      throw ApiException('بيانات غير صحيحة، يرجى مراجعة المدخلات.', statusCode: 422, data: body);
    } else {
      if (body is Map && body['message'] != null) {
        throw ApiException(body['message'].toString(), statusCode: response.statusCode, data: body);
      }
      throw ApiException('حدث خطأ في الخادم (${response.statusCode})', statusCode: response.statusCode, data: body);
    }
  }
}

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final dynamic data;

  ApiException(this.message, {this.statusCode, this.data});

  @override
  String toString() => message;
}
