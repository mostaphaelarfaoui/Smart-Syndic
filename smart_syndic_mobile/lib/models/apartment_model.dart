class ApartmentModel {
  final String id;
  final String unitNumber;
  final int floor;
  final double monthlyFee;
  final String? buildingName;
  final List<ApartmentResident> residents;

  ApartmentModel({
    required this.id,
    required this.unitNumber,
    required this.floor,
    required this.monthlyFee,
    this.buildingName,
    required this.residents,
  });

  factory ApartmentModel.fromJson(Map<String, dynamic> json) {
    var resList = <ApartmentResident>[];
    if (json['residents'] != null && json['residents'] is List) {
      resList = (json['residents'] as List)
          .map((r) => ApartmentResident.fromJson(r))
          .toList();
    }

    return ApartmentModel(
      id: json['id'] ?? '',
      unitNumber: json['unit_number'] ?? '',
      floor: json['floor'] ?? 0,
      monthlyFee: (json['monthly_fee'] as num?)?.toDouble() ?? 0.0,
      buildingName: json['building']?['name'],
      residents: resList,
    );
  }
}

class ApartmentResident {
  final String id;
  final String name;
  final String phone;
  final String email;
  final String status;
  final String occupancyType; // 'OWNER' or 'TENANT'

  ApartmentResident({
    required this.id,
    required this.name,
    required this.phone,
    required this.email,
    required this.status,
    required this.occupancyType,
  });

  factory ApartmentResident.fromJson(Map<String, dynamic> json) {
    return ApartmentResident(
      id: json['id'] ?? '',
      name: '${json['first_name'] ?? ''} ${json['last_name'] ?? ''}'.trim(),
      phone: json['phone'] ?? '',
      email: json['email'] ?? '',
      status: json['status'] ?? 'ACTIVE',
      occupancyType: json['pivot']?['occupancy_type'] ?? 'OWNER',
    );
  }
}
