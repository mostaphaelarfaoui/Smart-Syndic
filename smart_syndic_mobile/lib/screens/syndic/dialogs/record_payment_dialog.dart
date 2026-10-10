import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../../../core/constants/app_colors.dart';
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

    final success = await syndic.recordPayment(
      apartmentId: _selectedApartmentId!,
      amountPaid: amount,
      paymentMethod: _paymentMethod,
      chequeReference: _paymentMethod == 'CHEQUE' ? _chequeRefController.text : null,
      paymentDate: DateFormat('yyyy-MM-dd').format(_paymentDate),
      coveredMonths: _selectedMonths,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم تسجيل الدفعة وإصدار الوصل بنجاح ✓'),
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
                  initialValue: _selectedApartmentId,
                  decoration: const InputDecoration(
                    labelText: 'رقم الشقة / الساكن',
                    prefixIcon: Icon(Icons.home_outlined),
                  ),
                  items: syndic.apartments.map((apt) {
                    final resident = apt.residents.isNotEmpty ? apt.residents.first.name : 'شاغرة';
                    return DropdownMenuItem<String>(
                      value: apt.id,
                      child: Text('شقة ${apt.unitNumber} - $resident (${apt.monthlyFee.toStringAsFixed(0)} درهم)'),
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
                  initialValue: _paymentMethod,
                  decoration: const InputDecoration(
                    labelText: 'طريقة الأداء',
                    prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'CASH', child: Text('نقداً (Cash)')),
                    DropdownMenuItem(value: 'BANK_TRANSFER', child: Text('تحويل بنكي (Virement)')),
                    DropdownMenuItem(value: 'CHEQUE', child: Text('شيك بنكي (Chèque)')),
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
