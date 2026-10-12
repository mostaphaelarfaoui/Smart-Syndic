import '../core/utils/number_utils.dart';

class KpisModel {
  final int totalApartments;
  final double monthlyExpectedTotal;
  final double monthRevenue;
  final double monthExpenses;
  final double netBalance;
  final double collectionRate;
  final double totalArrears;
  final double treasuryBalance;
  final double cashBalance;
  final double bankBalance;
  final double initialTreasuryBalance;
  final double initialBankBalance;
  final bool initialBalanceLocked;
  final int pendingResidents;
  final int pendingSuggestions;
  final String? invitationCode;
  final List<ChartMonthData> chartData;

  KpisModel({
    required this.totalApartments,
    required this.monthlyExpectedTotal,
    required this.monthRevenue,
    required this.monthExpenses,
    required this.netBalance,
    required this.collectionRate,
    required this.totalArrears,
    required this.treasuryBalance,
    required this.cashBalance,
    required this.bankBalance,
    required this.initialTreasuryBalance,
    required this.initialBankBalance,
    required this.initialBalanceLocked,
    required this.pendingResidents,
    required this.pendingSuggestions,
    this.invitationCode,
    required this.chartData,
  });

  factory KpisModel.fromJson(Map<String, dynamic> json) {
    var chartList = <ChartMonthData>[];
    if (json['chart_data'] != null && json['chart_data'] is List) {
      chartList = (json['chart_data'] as List)
          .map((item) => ChartMonthData.fromJson(item))
          .toList();
    }

    return KpisModel(
      totalApartments: json['total_apartments'] ?? 0,
      monthlyExpectedTotal: parseDouble(json['monthly_expected_total']),
      monthRevenue: parseDouble(json['month_revenue']),
      monthExpenses: parseDouble(json['month_expenses']),
      netBalance: parseDouble(json['net_balance']),
      collectionRate: parseDouble(json['collection_rate']),
      totalArrears: parseDouble(json['total_arrears']),
      treasuryBalance: parseDouble(json['treasury_balance']),
      cashBalance: parseDouble(json['cash_balance']),
      bankBalance: parseDouble(json['bank_balance']),
      initialTreasuryBalance: parseDouble(json['initial_treasury_balance']),
      initialBankBalance: parseDouble(json['initial_bank_balance']),
      initialBalanceLocked: json['initial_balance_locked'] == true,
      pendingResidents: json['pending_residents'] ?? 0,
      pendingSuggestions: json['pending_suggestions'] ?? 0,
      invitationCode: json['invitation_code'],
      chartData: chartList,
    );
  }
}

class ChartMonthData {
  final String month;
  final double inflow;
  final double outflow;

  ChartMonthData({
    required this.month,
    required this.inflow,
    required this.outflow,
  });

  factory ChartMonthData.fromJson(Map<String, dynamic> json) {
    return ChartMonthData(
      month: json['month'] ?? '',
      inflow: parseDouble(json['inflow']),
      outflow: parseDouble(json['outflow']),
    );
  }
}
