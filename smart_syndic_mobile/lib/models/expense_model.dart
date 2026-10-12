class ExpenseModel {
  final String id;
  final String title;
  final String category;
  final double amount;
  final String paymentMethod;
  final String expenseDate;
  final String? buildingName;
  final String? invoiceProofUrl;

  ExpenseModel({
    required this.id,
    required this.title,
    required this.category,
    required this.amount,
    this.paymentMethod = 'CASH',
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
    double parsedAmount = 0.0;
    final rawAmount = json['amount'];
    if (rawAmount != null) {
      if (rawAmount is num) {
        parsedAmount = rawAmount.toDouble();
      } else {
        parsedAmount = double.tryParse(rawAmount.toString()) ?? 0.0;
      }
    }

    String dateStr = json['expense_date']?.toString() ?? '';
    if (dateStr.contains('T')) {
      dateStr = dateStr.split('T').first;
    }

    return ExpenseModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      category: json['category']?.toString() ?? 'OTHER',
      amount: parsedAmount,
      paymentMethod: json['payment_method']?.toString() ?? 'CASH',
      expenseDate: dateStr,
      buildingName: json['building']?['name']?.toString(),
      invoiceProofUrl: json['invoice_proof_url']?.toString(),
    );
  }
}
