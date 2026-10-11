import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../providers/syndic_provider.dart';

class RecordPaymentDialog extends StatefulWidget {
  const RecordPaymentDialog({super.key});

  @override
  State<RecordPaymentDialog> createState() => _RecordPaymentDialogState();
}

class _RecordPaymentDialogState extends State<RecordPaymentDialog> {
  final _formKey = GlobalKey<FormState>();
  String? _selectedApartmentId;
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _chequeRefController = TextEditingController();
  String _paymentMethod = 'CASH';
  DateTime _paymentDate = DateTime.now();
  final List<String> _selectedMonths = [];
  bool _isSubmitting = false;

  final List<String> _availableMonths = [
    '2026-01', '2026-02', '2026-03', '2026-04',
    '2026-05', '2026-06', '2026-07', '2026-08',
    '2026-09', '2026-10', '2026-11', '2026-12',
  ];

  @override
  void initState() {
    super.initState();
    // Default current month
    final currentMonthStr = DateFormat('yyyy-MM').format(DateTime.now());
    _selectedMonths.add(currentMonthStr);

    final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
    if (syndicProvider.apartments.isNotEmpty) {
      _selectedApartmentId = syndicProvider.apartments.first.id;
      _amountController.text = syndicProvider.apartments.first.monthlyFee.toStringAsFixed(0);
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    _chequeRefController.dispose();
    super.dispose();
  }

  void _onApartmentChanged(String? newId) {
    setState(() {
      _selectedApartmentId = newId;
      if (newId != null) {
        final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
        final apt = syndicProvider.apartments.firstWhere((a) => a.id == newId, orElse: () => syndicProvider.apartments.first);
        _amountController.text = (apt.monthlyFee * (_selectedMonths.isEmpty ? 1 : _selectedMonths.length)).toStringAsFixed(0);
      }
    });
  }

  void _toggleMonth(String month) {
    setState(() {
      if (_selectedMonths.contains(month)) {
        if (_selectedMonths.length > 1) {
          _selectedMonths.remove(month);
        }
      } else {
        _selectedMonths.add(month);
      }

      if (_selectedApartmentId != null) {
        final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
        final apt = syndicProvider.apartments.firstWhere((a) => a.id == _selectedApartmentId, orElse: () => syndicProvider.apartments.first);
        _amountController.text = (apt.monthlyFee * _selectedMonths.length).toStringAsFixed(0);
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedApartmentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('المرجو اختيار الشقة')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    final amount = double.tryParse(_amountController.text) ?? 0.0;

    final result = await syndic.recordPayment(
      apartmentId: _selectedApartmentId!,
      amountPaid: amount,
      paymentMethod: _paymentMethod,
      chequeReference: _paymentMethod == 'CHEQUE' ? _chequeRefController.text : null,
      paymentDate: DateFormat('yyyy-MM-dd').format(_paymentDate),
      coveredMonths: _selectedMonths,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (result != null) {
        final receiptUrl = result['receipt_url'] as String?;
        final receiptNumber = result['receipt_number'] as String? ?? 'REC-';
        final paidAmount = (result['amount_paid'] as num?)?.toDouble() ?? amount;

        // Find apartment data for unit number, resident info and remaining arrears
        final apt = syndic.apartments.where((a) => a.id == _selectedApartmentId).firstOrNull;
        final unitNumber = apt?.unitNumber ?? '';
        final residentName = apt?.resident != null ? (apt!.resident!['name'] ?? 'ساكن الإقامة') : 'شاغرة';
        final residentPhone = apt?.resident != null ? apt!.resident!['phone'] as String? : null;
        final remainingArrears = apt?.arrears ?? 0.0;
        final coveredCopy = List<String>.from(_selectedMonths);

        Navigator.pop(context, true);

        // Auto launch receipt in browser/external viewer for the syndic
        if (receiptUrl != null && receiptUrl.isNotEmpty) {
          try {
            final resolved = ApiService().resolveMediaUrl(receiptUrl);
            final uri = Uri.parse(resolved);
            launchUrl(uri, mode: LaunchMode.externalApplication);
          } catch (_) {}
        }

        // Show dedicated receipt success modal dialog with direct download & WhatsApp actions
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (dialogCtx) => PaymentReceiptSuccessDialog(
            receiptNumber: receiptNumber,
            amountPaid: paidAmount,
            unitNumber: unitNumber,
            residentName: residentName,
            residentPhone: residentPhone,
            receiptUrl: receiptUrl,
            coveredMonths: coveredCopy,
            remainingArrears: remainingArrears,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء تسجيل الدفعة'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withAlpha(25),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.receipt_long, color: AppColors.primary, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'تسجيل استخلاص واجبات',
                          style: GoogleFonts.cairo(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: AppColors.textLight),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Apartment dropdown
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: _selectedApartmentId,
                  decoration: const InputDecoration(
                    labelText: 'رقم الشقة / الساكن',
                    prefixIcon: Icon(Icons.home_outlined),
                  ),
                  items: syndic.apartments.map((apt) {
                    final resident = apt.resident != null ? apt.resident!['name'] : 'شاغرة';
                    return DropdownMenuItem<String>(
                      value: apt.id,
                      child: Text(
                        'شقة ${apt.unitNumber} - $resident (${apt.monthlyFee.toStringAsFixed(0)} درهم)',
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  }).toList(),
                  onChanged: _onApartmentChanged,
                ),
                const SizedBox(height: 16),

                // Amount
                TextFormField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'المبلغ المؤدى (درهم)',
                    prefixIcon: Icon(Icons.payments_outlined),
                    suffixText: 'درهم',
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'المرجو إدخال المبلغ';
                    if (double.tryParse(v) == null) return 'مبلغ غير صالح';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Payment Method
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: _paymentMethod,
                  decoration: const InputDecoration(
                    labelText: 'طريقة الأداء',
                    prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'CASH', child: Text('نقداً (Cash)', overflow: TextOverflow.ellipsis)),
                    DropdownMenuItem(value: 'BANK_TRANSFER', child: Text('تحويل بنكي (Virement)', overflow: TextOverflow.ellipsis)),
                    DropdownMenuItem(value: 'CHEQUE', child: Text('شيك بنكي (Chèque)', overflow: TextOverflow.ellipsis)),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _paymentMethod = val);
                  },
                ),
                if (_paymentMethod == 'CHEQUE') ...[
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _chequeRefController,
                    decoration: const InputDecoration(
                      labelText: 'رقم الشيك أو المرجع البنكي',
                      prefixIcon: Icon(Icons.numbers),
                    ),
                    validator: (v) {
                      if (_paymentMethod == 'CHEQUE' && (v == null || v.isEmpty)) {
                        return 'المرجو إدخال رقم الشيك';
                      }
                      return null;
                    },
                  ),
                ],
                const SizedBox(height: 16),

                // Covered months selection
                Text(
                  'الشهور المستخلصة:',
                  style: GoogleFonts.cairo(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _availableMonths.map((m) {
                    final isSelected = _selectedMonths.contains(m);
                    return FilterChip(
                      selected: isSelected,
                      label: Text(m),
                      labelStyle: GoogleFonts.cairo(
                        fontSize: 12,
                        color: isSelected ? Colors.white : AppColors.textPrimary,
                        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      ),
                      selectedColor: AppColors.primary,
                      checkmarkColor: Colors.white,
                      onSelected: (_) => _toggleMonth(m),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 16),

                // Payment Date
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _paymentDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2030),
                    );
                    if (picked != null) {
                      setState(() => _paymentDate = picked);
                    }
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      border: Border.all(color: AppColors.border),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today_outlined, color: AppColors.textSecondary, size: 20),
                        const SizedBox(width: 12),
                        Text(
                          'تاريخ الأداء: ${DateFormat('yyyy-MM-dd').format(_paymentDate)}',
                          style: GoogleFonts.cairo(
                            fontSize: 13,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const Spacer(),
                        const Icon(Icons.arrow_drop_down, color: AppColors.textLight),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          'تأكيد الدفع وإصدار الوصل',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class PaymentReceiptSuccessDialog extends StatelessWidget {
  final String receiptNumber;
  final double amountPaid;
  final String unitNumber;
  final String residentName;
  final String? residentPhone;
  final String? receiptUrl;
  final List<String> coveredMonths;
  final double remainingArrears;

  const PaymentReceiptSuccessDialog({
    super.key,
    required this.receiptNumber,
    required this.amountPaid,
    required this.unitNumber,
    required this.residentName,
    this.residentPhone,
    this.receiptUrl,
    required this.coveredMonths,
    required this.remainingArrears,
  });

  Future<void> _viewReceipt(BuildContext context) async {
    if (receiptUrl == null || receiptUrl!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    final resolved = ApiService().resolveMediaUrl(receiptUrl);
    final uri = Uri.parse(resolved);
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('تعذر فتح الوصل: $e')),
        );
      }
    }
  }

  Future<void> _shareWhatsApp(BuildContext context) async {
    final cleanPhone = (residentPhone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    final monthsText = coveredMonths.isNotEmpty ? coveredMonths.join(', ') : 'واجب شهري';
    final resolvedUrl = ApiService().resolveMediaUrl(receiptUrl);
    final linkText = resolvedUrl.isNotEmpty ? '\nرابط تحميل الوصل:\n$resolvedUrl' : '';

    final message = Uri.encodeComponent(
      'السلام عليكم ورحمة الله وبركاته،\n'
      'نؤكد لكم استلام واجب السانديك للشقة $unitNumber بمبلغ ${amountPaid.toStringAsFixed(0)} درهم.\n'
      'رقم الوصل: $receiptNumber\n'
      'الشهور المغطاة: $monthsText\n'
      'المستحقات المتبقية على الشقة: ${remainingArrears.toStringAsFixed(0)} درهم\n'
      '$linkText\n\n'
      'تم إيداع الوصل أيضاً في حسابكم الخاص بالتطبيق (ركن الإيصالات).\n'
      'شكراً لالتزامكم وحسن تعاونكم 🙏\n'
      'إدارة السانديك',
    );

    Uri uri;
    if (cleanPhone.isNotEmpty) {
      final phoneFormatted = cleanPhone.startsWith('0') ? '212${cleanPhone.substring(1)}' : cleanPhone;
      uri = Uri.parse('https://wa.me/$phoneFormatted?text=$message');
    } else {
      uri = Uri.parse('https://wa.me/?text=$message');
    }

    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  void _copyReceiptNumber(BuildContext context) {
    Clipboard.setData(ClipboardData(text: receiptNumber));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('تم نسخ رقم الوصل بنجاح ✓'),
        backgroundColor: AppColors.success,
        duration: Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Success Badge
              Center(
                child: Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: AppColors.success.withAlpha(25),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.check_circle_rounded,
                    color: AppColors.success,
                    size: 44,
                  ),
                ),
              ),
              const SizedBox(height: 16),

              Text(
                'تم تأكيد الدفع وإصدار الوصل بنجاح',
                textAlign: TextAlign.center,
                style: GoogleFonts.cairo(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'تم خصم المبلغ من متأخرات الشقة وإيداع الوصل في حساب الساكن الخاص',
                textAlign: TextAlign.center,
                style: GoogleFonts.cairo(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 20),

              // Details Container
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.background,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    _buildRow(
                      label: 'رقم الوصل الرسمي',
                      value: receiptNumber,
                      isBold: true,
                      trailing: IconButton(
                        icon: const Icon(Icons.copy, size: 16, color: AppColors.primary),
                        tooltip: 'نسخ رقم الوصل',
                        onPressed: () => _copyReceiptNumber(context),
                      ),
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'الشقة والساكن',
                      value: 'شقة $unitNumber - $residentName',
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'المبلغ المستخلص',
                      value: '${amountPaid.toStringAsFixed(0)} درهم',
                      valueColor: AppColors.success,
                      isBold: true,
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'الشهور المغطاة',
                      value: coveredMonths.isNotEmpty ? coveredMonths.join(', ') : 'واجب شهري',
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'المستحقات المتبقية على الشقة',
                      value: remainingArrears <= 0 ? '0 درهم (خالصة ✓)' : '${remainingArrears.toStringAsFixed(0)} درهم',
                      valueColor: remainingArrears <= 0 ? AppColors.success : AppColors.danger,
                      isBold: true,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Privacy Reassurance Banner
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.primary.withAlpha(30)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.lock_outline, size: 18, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'تم وضع الوصل في حساب صاحب الشقة في ركن إيصالات الدفع، ولن يظهر لباقي الشقق لضمان الخصوصية التامة.',
                        style: GoogleFonts.cairo(
                          fontSize: 11,
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Primary Action: View/Download Receipt
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () => _viewReceipt(context),
                icon: const Icon(Icons.download_rounded, size: 20),
                label: Text(
                  'معاينة وتحميل الوصل فوراً',
                  style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 10),

              // Secondary Action: Share via WhatsApp
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF25D366),
                  side: const BorderSide(color: Color(0xFF25D366)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () => _shareWhatsApp(context),
                icon: const Icon(Icons.chat, size: 18, color: Color(0xFF25D366)),
                label: Text(
                  'مشاركة الوصل مع الساكن عبر واتساب',
                  style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 8),

              // Dismiss
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text(
                  'تم / إغلاق',
                  style: GoogleFonts.cairo(
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRow({
    required String label,
    required String value,
    Color? valueColor,
    bool isBold = false,
    Widget? trailing,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
        ),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              value,
              style: GoogleFonts.cairo(
                fontSize: 13,
                color: valueColor ?? AppColors.textPrimary,
                fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              ),
            ),
            ?trailing,
          ],
        ),
      ],
    );
  }
}
