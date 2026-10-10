import 'package:flutter/material.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/payment_model.dart';
import '../models/suggestion_model.dart';
import '../models/poll_model.dart';

class ResidentProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  Map<String, dynamic>? _balanceInfo;
  List<PaymentModel> _myPayments = [];
  Map<String, dynamic>? _transparencyData;
  List<SuggestionModel> _mySuggestions = [];
  List<PollModel> _polls = [];

  bool _isLoading = false;
  String? _errorMessage;

  Map<String, dynamic>? get balanceInfo => _balanceInfo;
  List<PaymentModel> get myPayments => _myPayments;
  Map<String, dynamic>? get transparencyData => _transparencyData;
  List<SuggestionModel> get mySuggestions => _mySuggestions;
  List<PollModel> get polls => _polls;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchMyBalance() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _api.get(ApiConstants.residentBalance);
      _balanceInfo = response;
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchMyPayments() async {
    try {
      final response = await _api.get(ApiConstants.residentPayments);
      if (response is List) {
        _myPayments = response.map((p) => PaymentModel.fromJson(p)).toList();
      } else if (response['data'] != null) {
        _myPayments = (response['data'] as List).map((p) => PaymentModel.fromJson(p)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<void> fetchTransparencyLedger() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _api.get(ApiConstants.residentTransparency);
      _transparencyData = response;
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchMySuggestions() async {
    try {
      final response = await _api.get(ApiConstants.residentSuggestions);
      if (response is List) {
        _mySuggestions = response.map((s) => SuggestionModel.fromJson(s)).toList();
      } else if (response['data'] != null) {
        _mySuggestions = (response['data'] as List).map((s) => SuggestionModel.fromJson(s)).toList();
      }
      notifyListeners();
    } catch (_) {}
  }

  Future<bool> submitSuggestion(String title, String description) async {
    _isLoading = true;
    notifyListeners();

    try {
      final String? apartmentId = _balanceInfo?['apartment']?['id'];
      if (apartmentId == null) throw Exception("لا يمكن تحديد شقة الساكن.");

      await _api.post(ApiConstants.residentSuggestions, {
        'apartment_id': apartmentId,
        'title': title,
        'description': description,
      });
      await fetchMySuggestions();
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

  Future<void> fetchPolls() async {
    try {
      final response = await _api.get(ApiConstants.residentPolls);
      if (response is List) {
        _polls = response.map((p) => PollModel.fromJson(p)).toList();
      } else if (response['polls'] != null) {
        _polls = (response['polls'] as List).map((p) => PollModel.fromJson(p)).toList();
      } else if (response['data'] != null) {
        _polls = (response['data'] as List).map((p) => PollModel.fromJson(p)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> castVote(String pollId, String optionId) async {
    try {
      final String? apartmentId = _balanceInfo?['apartment']?['id'];
      if (apartmentId == null) throw Exception("لا يمكن تحديد شقة الساكن.");

      await _api.post('${ApiConstants.residentPolls}/$pollId/vote', {
        'poll_option_id': optionId,
        'apartment_id': apartmentId,
      });
      await fetchPolls();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }
}
