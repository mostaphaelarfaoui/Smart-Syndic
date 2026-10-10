class ExpenseModel {
  final String id;
  final String title;
  final String category;
  final double amount;
  final String expenseDate;
  final String? buildingName;
  final String? invoiceProofUrl;

  ExpenseModel({
    required this.id,
    required this.title,
    required this.category,
    required this.amount,
    required this.expenseDate,
    this.buildingName,
    this.invoiceProofUrl,
  });

  String get categoryArabic {
    switch (category) {
      case 'ELEVATOR':
        return '🛗 المصعد';
      case 'CLEANING':
        return '🧹 النظافة';
      case 'SECURITY':
        return '🔒 الحراسة والأمن';
      case 'WATER_ELECTRICITY':
        return '💡 الماء والكهرباء';
      case 'GARDENING':
        return '🌿 البستنة';
      case 'MAINTENANCE':
        return '🔧 الصيانة العامة';
      default:
        return '📋 أخرى';
    }
  }

  factory ExpenseModel.fromJson(Map<String, dynamic> json) {
    return ExpenseModel(
      id: json['id'] ?? '',
      title: json['title'] ?? '',
      category: json['category'] ?? 'OTHER',
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      expenseDate: json['expense_date'] ?? '',
      buildingName: json['building']?['name'],
      invoiceProofUrl: json['invoice_proof_url'],
    );
  }
}
