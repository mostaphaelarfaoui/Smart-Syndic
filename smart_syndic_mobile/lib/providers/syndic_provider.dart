import 'package:flutter/material.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/kpis_model.dart';
import '../models/payment_model.dart';
import '../models/expense_model.dart';
import '../models/apartment_model.dart';
import '../models/suggestion_model.dart';
import '../models/poll_model.dart';

class SyndicProvider extends ChangeNotifier {
  final ApiService _api = ApiService();

  KpisModel? _kpis;
  List<PaymentModel> _payments = [];
  List<ExpenseModel> _expenses = [];
  List<ApartmentModel> _apartments = [];
  List<Map<String, dynamic>> _buildings = [];
  List<Map<String, dynamic>> _pendingResidents = [];
  List<SuggestionModel> _suggestions = [];
  List<PollModel> _polls = [];
  String? _invitationCode;

  bool _isLoading = false;
  String? _errorMessage;

  KpisModel? get kpis => _kpis;
  List<PaymentModel> get payments => _payments;
  List<ExpenseModel> get expenses => _expenses;
  List<ApartmentModel> get apartments => _apartments;
  List<Map<String, dynamic>> get buildings => _buildings;
  List<Map<String, dynamic>> get pendingResidents => _pendingResidents;
  List<SuggestionModel> get suggestions => _suggestions;
  List<PollModel> get polls => _polls;
  String? get invitationCode => _invitationCode;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  void clear() {
    _kpis = null;
    _payments = [];
    _expenses = [];
    _apartments = [];
    _buildings = [];
    _pendingResidents = [];
    _suggestions = [];
    _polls = [];
    _invitationCode = null;
    _isLoading = false;
    _errorMessage = null;
    notifyListeners();
  }

  Future<void> fetchDashboardKpis() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _api.get(ApiConstants.syndicKpis);
      _kpis = KpisModel.fromJson(response);
      _invitationCode = _kpis?.invitationCode;
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchPayments() async {
    try {
      final response = await _api.get(ApiConstants.syndicPayments);
      if (response is List) {
        _payments = response.map((p) => PaymentModel.fromJson(p)).toList();
      } else if (response['data'] != null) {
        _payments = (response['data'] as List).map((p) => PaymentModel.fromJson(p)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> recordPayment({
    required String apartmentId,
    required double amountPaid,
    required String paymentMethod,
    String? chequeReference,
    required String paymentDate,
    required List<String> coveredMonths,
    bool isPartial = false,
    double remainingBalance = 0,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      await _api.post(ApiConstants.syndicPayments, {
        'apartment_id': apartmentId,
        'amount_paid': amountPaid,
        'payment_method': paymentMethod,
        if (chequeReference != null) 'cheque_reference': chequeReference,
        'payment_date': paymentDate,
        'covered_months': coveredMonths,
        'is_partial': isPartial,
        'remaining_balance': remainingBalance,
      });

      await fetchPayments();
      await fetchDashboardKpis();
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

  Future<void> fetchExpenses() async {
    try {
      final response = await _api.get(ApiConstants.syndicExpenses);
      if (response is List) {
        _expenses = response.map((e) => ExpenseModel.fromJson(e)).toList();
      } else if (response['data'] != null) {
        _expenses = (response['data'] as List).map((e) => ExpenseModel.fromJson(e)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> recordExpense({
    String? buildingId,
    required String category,
    required String title,
    required double amount,
    required String expenseDate,
    String? invoiceFilePath,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final fields = <String, String>{
        if (buildingId != null) 'building_id': buildingId,
        'category': category,
        'title': title,
        'amount': amount.toString(),
        'expense_date': expenseDate,
      };

      if (invoiceFilePath != null && invoiceFilePath.isNotEmpty) {
        await _api.postMultipart(
          ApiConstants.syndicExpenses,
          fields,
          fileField: 'invoice_proof',
          filePath: invoiceFilePath,
        );
      } else {
        await _api.post(ApiConstants.syndicExpenses, {
          if (buildingId != null) 'building_id': buildingId,
          'category': category,
          'title': title,
          'amount': amount,
          'expense_date': expenseDate,
        });
      }

      await fetchExpenses();
      await fetchDashboardKpis();
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

  Future<void> fetchApartments() async {
    try {
      final response = await _api.get(ApiConstants.syndicApartments);
      List<ApartmentModel> parsedApartments = [];
      List<Map<String, dynamic>> parsedBuildings = [];
      
      final buildings = response['buildings'] as List?;
      if (buildings != null) {
        for (var b in buildings) {
          final bName = b['name'];
          final bId = b['id'];
          parsedBuildings.add({'id': bId, 'name': bName});
          
          final apts = b['apartments'] as List?;
          if (apts != null) {
            for (var a in apts) {
              parsedApartments.add(ApartmentModel.fromJson(a, bName: bName, bId: bId));
            }
          }
        }
      }
      
      _apartments = parsedApartments;
      _buildings = parsedBuildings;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> addApartment({
    required String buildingId,
    required String unitNumber,
    required int floor,
    required double monthlyFee,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      await _api.post(ApiConstants.syndicApartments, {
        'building_id': buildingId,
        'unit_number': unitNumber,
        'floor': floor,
        'monthly_fee': monthlyFee,
      });

      await fetchApartments();
      await fetchDashboardKpis();
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

  Future<void> fetchPendingResidents() async {
    try {
      final response = await _api.get(ApiConstants.syndicPendingResidents);
      if (response is List) {
        _pendingResidents = List<Map<String, dynamic>>.from(response);
      } else if (response['data'] != null) {
        _pendingResidents = List<Map<String, dynamic>>.from(response['data']);
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> approveResident(String residentId) async {
    try {
      await _api.post('${ApiConstants.syndicPendingResidents}/$residentId/approve', {});
      await fetchPendingResidents();
      await fetchDashboardKpis();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> rejectResident(String residentId) async {
    try {
      await _api.post('${ApiConstants.syndicPendingResidents}/$residentId/reject', {});
      await fetchPendingResidents();
      await fetchDashboardKpis();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<void> fetchSuggestions() async {
    try {
      final response = await _api.get(ApiConstants.syndicSuggestions);
      if (response is List) {
        _suggestions = response.map((s) => SuggestionModel.fromJson(s)).toList();
      } else if (response['data'] != null) {
        _suggestions = (response['data'] as List).map((s) => SuggestionModel.fromJson(s)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<bool> convertSuggestionToPoll(String suggestionId, List<String> options, String expiresAt, {String? customTitle, String? customDescription}) async {
    try {
      await _api.post('${ApiConstants.syndicSuggestions}/$suggestionId/convert-to-poll', {
        'options': options,
        'expires_at': expiresAt,
        if (customTitle != null) 'title': customTitle,
        if (customDescription != null) 'description': customDescription,
      });
      await fetchSuggestions();
      await fetchPolls();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> acceptSuggestion(String suggestionId, {String? notes}) async {
    try {
      await _api.post('${ApiConstants.syndicSuggestions}/$suggestionId/accept', {
        if (notes != null) 'notes': notes,
      });
      await fetchSuggestions();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> rejectSuggestion(String suggestionId, {String? notes}) async {
    try {
      await _api.post('${ApiConstants.syndicSuggestions}/$suggestionId/reject', {
        if (notes != null) 'notes': notes,
      });
      await fetchSuggestions();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<bool> createPoll({
    required String title,
    required String description,
    required List<String> options,
    required String expiresAt,
  }) async {
    try {
      await _api.post(ApiConstants.syndicPolls, {
        'title': title,
        'description': description,
        'options': options,
        'expires_at': expiresAt,
      });
      await fetchPolls();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<void> fetchPolls() async {
    try {
      final response = await _api.get(ApiConstants.syndicPolls);
      if (response is List) {
        _polls = response.map((p) => PollModel.fromJson(p)).toList();
      } else if (response['data'] != null) {
        _polls = (response['data'] as List).map((p) => PollModel.fromJson(p)).toList();
      }
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }



  Future<void> regenerateInvitationCode() async {
    try {
      final response = await _api.post(ApiConstants.syndicInviteCodeRegen, {});
      _invitationCode = response['invitation_code'];
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>?> generateFinancialReport({
    required String from,
    required String to,
    String format = 'pdf',
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _api.get(
        '${ApiConstants.syndicReportsExport}?from=$from&to=$to&format=$format',
      );
      _isLoading = false;
      notifyListeners();

      if (response != null && response['download'] != null) {
        final data = Map<String, dynamic>.from(response['download']);
        if (data['url'] != null) {
          data['url'] = _api.resolveMediaUrl(data['url']);
        }
        return data;
      }
      return null;
    } catch (e) {
      _isLoading = false;
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return null;
    }
  }
}
