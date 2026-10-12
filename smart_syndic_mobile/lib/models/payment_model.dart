class PaymentModel {
  final String id;
  final String receiptNumber;
  final double amountPaid;
  final String paymentMethod;
  final String? chequeReference;
  final String paymentDate;
  final bool isPartial;
  final double remainingBalance;
  final List<String> coveredMonths;
  final String? receiptUrl;
  final String? apartmentUnit;
  final String? residentName;
  final String? residentPhone;
  final String status;
  final String? confirmedAt;
  final String? sentToResidentAt;

  PaymentModel({
    required this.id,
    required this.receiptNumber,
    required this.amountPaid,
    required this.paymentMethod,
    this.chequeReference,
    required this.paymentDate,
    required this.isPartial,
    required this.remainingBalance,
    required this.coveredMonths,
    this.receiptUrl,
    this.apartmentUnit,
    this.residentName,
    this.residentPhone,
    this.status = 'DRAFT',
    this.confirmedAt,
    this.sentToResidentAt,
  });

  bool get isDraft => status == 'DRAFT';
  bool get isConfirmed => status == 'CONFIRMED';
  bool get isSentToResident => status == 'SENT_TO_RESIDENT';

  String get statusArabic {
    switch (status) {
      case 'DRAFT':
        return 'مسودة';
      case 'CONFIRMED':
        return 'مؤكد';
      case 'SENT_TO_RESIDENT':
        return 'مُرسل للساكن';
      default:
        return status;
    }
  }

  String get paymentMethodArabic {
    switch (paymentMethod) {
      case 'CASH':
        return 'نقداً';
      case 'BANK_TRANSFER':
        return 'تحويل بنكي';
      case 'CHEQUE':
        return 'شيك';
      default:
        return paymentMethod;
    }
  }

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    List<String> months = [];
    if (json['covered_months'] != null) {
      if (json['covered_months'] is List) {
        months = List<String>.from(json['covered_months']);
      }
    }

    String? unit;
    if (json['apartment'] != null && json['apartment'] is Map) {
      unit = json['apartment']['unit_number'];
    }

    String? resName;
    String? resPhone;
    if (json['apartment'] != null && json['apartment']['residents'] != null && (json['apartment']['residents'] as List).isNotEmpty) {
      final res = json['apartment']['residents'][0];
      resName = '${res['first_name'] ?? ''} ${res['last_name'] ?? ''}'.trim();
      resPhone = res['phone'];
    }

    return PaymentModel(
      id: json['id'] ?? '',
      receiptNumber: json['receipt_number'] ?? '',
      amountPaid: (json['amount_paid'] as num?)?.toDouble() ?? 0.0,
      paymentMethod: json['payment_method'] ?? 'CASH',
      chequeReference: json['cheque_reference'],
      paymentDate: json['payment_date'] ?? '',
      isPartial: json['is_partial'] == true || json['is_partial'] == 1,
      remainingBalance: (json['remaining_balance'] as num?)?.toDouble() ?? 0.0,
      coveredMonths: months,
      receiptUrl: json['receipt_url'] ?? json['receipt_download_url'],
      apartmentUnit: unit,
      residentName: resName,
      residentPhone: resPhone,
      status: json['status'] ?? 'DRAFT',
      confirmedAt: json['confirmed_at'],
      sentToResidentAt: json['sent_to_resident_at'],
    );
  }
}
