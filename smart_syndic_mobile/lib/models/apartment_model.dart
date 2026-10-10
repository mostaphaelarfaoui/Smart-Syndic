class ApartmentModel {
  final String id;
  final String unitNumber;
  final int floor;
  final double monthlyFee;
  final String? buildingName;
  final String? buildingId;
  final String? block;
  final Map<String, dynamic>? resident;
  final double totalPaid;
  final double arrears;
  final bool isSettled;
  final String? lastPayment;
  final List<Map<String, dynamic>> payments;

  ApartmentModel({
    required this.id,
    required this.unitNumber,
    required this.floor,
    required this.monthlyFee,
    this.buildingName,
    this.buildingId,
    this.block,
    this.resident,
    this.totalPaid = 0.0,
    this.arrears = 0.0,
    this.isSettled = true,
    this.lastPayment,
    this.payments = const [],
  });

  factory ApartmentModel.fromJson(Map<String, dynamic> json, {String? bName, String? bId, String? blockName}) {
    final rawPayments = json['payments'] as List?;
    final parsedPayments = rawPayments != null
        ? List<Map<String, dynamic>>.from(rawPayments)
        : <Map<String, dynamic>>[];

    final settledRaw = json['is_settled'];
    final isSettledBool = settledRaw == true || settledRaw == 1 || settledRaw == '1';

    return ApartmentModel(
      id: json['id'] ?? '',
      unitNumber: json['unit_number'] ?? '',
      floor: json['floor'] ?? 0,
      monthlyFee: (json['monthly_fee'] as num?)?.toDouble() ?? 0.0,
      buildingName: bName ?? json['building_name'] ?? json['building']?['name'],
      buildingId: bId ?? json['building_id'],
      block: blockName ?? json['block'] ?? json['building']?['block'],
      resident: json['resident'],
      totalPaid: (json['total_paid'] as num?)?.toDouble() ?? 0.0,
      arrears: (json['arrears'] as num?)?.toDouble() ?? 0.0,
      isSettled: isSettledBool,
      lastPayment: json['last_payment'],
      payments: parsedPayments,
    );
  }
}
