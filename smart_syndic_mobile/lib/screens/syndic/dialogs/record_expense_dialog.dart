import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';

class RecordExpenseDialog extends StatefulWidget {
  const RecordExpenseDialog({super.key});

  @override
  State<RecordExpenseDialog> createState() => _RecordExpenseDialogState();
}

class _RecordExpenseDialogState extends State<RecordExpenseDialog> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _titleController = TextEditingController();
  final TextEditingController _amountController = TextEditingController();
  String _selectedCategory = 'CLEANING';
  DateTime _expenseDate = DateTime.now();
  bool _isSubmitting = false;

  final List<Map<String, String>> _categories = [
    {'value': 'CLEANING', 'label': '🧹 النظافة'},
    {'value': 'ELEVATOR', 'label': '🛗 صيانة المصعد'},
    {'value': 'WATER_ELECTRICITY', 'label': '💡 الماء والكهرباء'},
    {'value': 'SECURITY', 'label': '🔒 الحراسة والأمن'},
    {'value': 'GARDENING', 'label': '🌿 البستنة'},
    {'value': 'MAINTENANCE', 'label': '🔧 صيانة عامة'},
    {'value': 'OTHER', 'label': '📋 مصاريف أخرى'},
  ];

  @override
  void dispose() {
    _titleController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    final amount = double.tryParse(_amountController.text) ?? 0.0;

    final success = await syndic.recordExpense(
      title: _titleController.text.trim(),
      category: _selectedCategory,
      amount: amount,
      expenseDate: DateFormat('yyyy-MM-dd').format(_expenseDate),
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم تسجيل المصروف في دفتر المحاسبة بنجاح ✓'),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء تسجيل المصروف'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
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
                            color: AppColors.danger.withAlpha(25),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.outbox, color: AppColors.danger, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'تسجيل مصروف جديد',
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

                // Title
                TextFormField(
                  controller: _titleController,
                  decoration: const InputDecoration(
                    labelText: 'بيان المصروف (العنوان)',
                    hintText: 'مثال: فاتورة كهرباء المرافق المشتركة',
                    prefixIcon: Icon(Icons.edit_note),
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'المرجو إدخال بيان المصروف';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Category dropdown
                DropdownButtonFormField<String>(
                  initialValue: _selectedCategory,
                  decoration: const InputDecoration(
                    labelText: 'صنف المصروف',
                    prefixIcon: Icon(Icons.category_outlined),
                  ),
                  items: _categories.map((c) {
                    return DropdownMenuItem<String>(
                      value: c['value'],
                      child: Text(c['label']!),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedCategory = val);
                  },
                ),
                const SizedBox(height: 16),

                // Amount
                TextFormField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'المبلغ (درهم)',
                    prefixIcon: Icon(Icons.attach_money),
                    suffixText: 'درهم',
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'المرجو إدخال المبلغ';
                    if (double.tryParse(v) == null) return 'مبلغ غير صالح';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Date Picker Tile
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _expenseDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2030),
                    );
                    if (picked != null) {
                      setState(() => _expenseDate = picked);
                    }
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      border: Border.all(color: AppColors.border),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today_outlined, color: AppColors.textSecondary, size: 20),
                        const SizedBox(width: 12),
                        Text(
                          'تاريخ الصرف: ${DateFormat('yyyy-MM-dd').format(_expenseDate)}',
                          style: GoogleFonts.cairo(
                            fontSize: 14,
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
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.danger,
                  ),
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          'حفظ المصروف',
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
