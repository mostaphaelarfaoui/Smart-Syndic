class TenantModel {
  final String id;
  final String name;
  final String propertyType;
  final String planType;
  final bool isTransparencyEnabled;

  TenantModel({
    required this.id,
    required this.name,
    required this.propertyType,
    required this.planType,
    required this.isTransparencyEnabled,
  });

  factory TenantModel.fromJson(Map<String, dynamic> json) {
    return TenantModel(
      id: json['id'] ?? '',
      name: json['name'] ?? '',
      propertyType: json['property_type'] ?? '',
      planType: json['plan_type'] ?? '',
      isTransparencyEnabled: json['is_transparency_enabled'] == true || json['is_transparency_enabled'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'property_type': propertyType,
      'plan_type': planType,
      'is_transparency_enabled': isTransparencyEnabled,
    };
  }
}
