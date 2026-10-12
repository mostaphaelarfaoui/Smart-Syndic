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
      monthlyExpectedTotal: (json['monthly_expected_total'] as num?)?.toDouble() ?? 0.0,
      monthRevenue: (json['month_revenue'] as num?)?.toDouble() ?? 0.0,
      monthExpenses: (json['month_expenses'] as num?)?.toDouble() ?? 0.0,
      netBalance: (json['net_balance'] as num?)?.toDouble() ?? 0.0,
      collectionRate: (json['collection_rate'] as num?)?.toDouble() ?? 0.0,
      totalArrears: (json['total_arrears'] as num?)?.toDouble() ?? 0.0,
      treasuryBalance: (json['treasury_balance'] as num?)?.toDouble() ?? 0.0,
      cashBalance: (json['cash_balance'] as num?)?.toDouble() ?? 0.0,
      bankBalance: (json['bank_balance'] as num?)?.toDouble() ?? 0.0,
      initialTreasuryBalance: (json['initial_treasury_balance'] as num?)?.toDouble() ?? 0.0,
      initialBankBalance: (json['initial_bank_balance'] as num?)?.toDouble() ?? 0.0,
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
      inflow: (json['inflow'] as num?)?.toDouble() ?? 0.0,
      outflow: (json['outflow'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
