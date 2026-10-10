class ApartmentModel {
  final String id;
  final String unitNumber;
  final int floor;
  final double monthlyFee;
  final String? buildingName;
  final String? buildingId;
  final Map<String, dynamic>? resident;

  ApartmentModel({
    required this.id,
    required this.unitNumber,
    required this.floor,
    required this.monthlyFee,
    this.buildingName,
    this.buildingId,
    this.resident,
  });

  factory ApartmentModel.fromJson(Map<String, dynamic> json, {String? bName, String? bId}) {
    return ApartmentModel(
      id: json['id'] ?? '',
      unitNumber: json['unit_number'] ?? '',
      floor: json['floor'] ?? 0,
      monthlyFee: (json['monthly_fee'] as num?)?.toDouble() ?? 0.0,
      buildingName: bName ?? json['building']?['name'],
      buildingId: bId,
      resident: json['resident'],
    );
  }
}
