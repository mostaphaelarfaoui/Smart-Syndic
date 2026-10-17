class TenantModel {
  final String id;
  final String name;
  final String propertyType;
  final String planType;
  final bool isTransparencyEnabled;
  final double defaultMonthlyFee;
  final String? bankRib;

  TenantModel({
    required this.id,
    required this.name,
    required this.propertyType,
    required this.planType,
    required this.isTransparencyEnabled,
    this.defaultMonthlyFee = 200.0,
    this.bankRib,
  });

  factory TenantModel.fromJson(Map<String, dynamic> json) {
    return TenantModel(
      id: json['id'] ?? '',
      name: json['name'] ?? '',
      propertyType: json['property_type'] ?? '',
      planType: json['plan_type'] ?? '',
      isTransparencyEnabled: json['is_transparency_enabled'] == true || json['is_transparency_enabled'] == 1,
      defaultMonthlyFee: (json['default_monthly_fee'] as num?)?.toDouble() ?? 200.0,
      bankRib: json['bank_rib']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'property_type': propertyType,
      'plan_type': planType,
      'is_transparency_enabled': isTransparencyEnabled,
      'default_monthly_fee': defaultMonthlyFee,
      'bank_rib': bankRib,
    };
  }
}
