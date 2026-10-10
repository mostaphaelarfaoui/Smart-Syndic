class KpisModel {
  final int totalApartments;
  final double monthRevenue;
  final double monthExpenses;
  final double netBalance;
  final double collectionRate;
  final double totalArrears;
  final int pendingResidents;
  final int pendingSuggestions;
  final String? invitationCode;
  final List<ChartMonthData> chartData;

  KpisModel({
    required this.totalApartments,
    required this.monthRevenue,
    required this.monthExpenses,
    required this.netBalance,
    required this.collectionRate,
    required this.totalArrears,
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
      monthRevenue: (json['month_revenue'] as num?)?.toDouble() ?? 0.0,
      monthExpenses: (json['month_expenses'] as num?)?.toDouble() ?? 0.0,
      netBalance: (json['net_balance'] as num?)?.toDouble() ?? 0.0,
      collectionRate: (json['collection_rate'] as num?)?.toDouble() ?? 0.0,
      totalArrears: (json['total_arrears'] as num?)?.toDouble() ?? 0.0,
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
