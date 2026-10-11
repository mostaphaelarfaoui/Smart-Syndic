import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
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
  final ImagePicker _picker = ImagePicker();
  
  String _selectedCategory = 'CLEANING';
  DateTime _expenseDate = DateTime.now();
  File? _invoiceImage;
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

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picked = await _picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 85,
      );
      if (picked != null) {
        setState(() => _invoiceImage = File(picked.path));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text('تعذر اختيار الصورة: $e'),
          ),
        );
      }
    }
  }

  void _showImageSourceDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'إرفاق صورة الفاتورة أو وصل الشراء',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 15),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(25),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.camera_alt, color: AppColors.primary),
                ),
                title: Text('التقاط صورة بالكاميرا', style: GoogleFonts.cairo(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.success.withAlpha(25),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.photo_library, color: AppColors.success),
                ),
                title: Text('اختيار من معرض الصور', style: GoogleFonts.cairo(fontWeight: FontWeight.w600)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.gallery);
                },
              ),
            ],
          ),
        ),
      ),
    );
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
      invoiceFilePath: _invoiceImage?.path,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم تسجيل المصروف وإرفاق الفاتورة بنجاح ✓'),
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
                          child: const Icon(Icons.receipt_long, color: AppColors.danger, size: 24),
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
                      icon: const Icon(Icons.close, color: AppColors.textSecondary),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Category Dropdown
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: _selectedCategory,
                  decoration: const InputDecoration(
                    labelText: 'صنف المصروف',
                    prefixIcon: Icon(Icons.category_outlined, color: AppColors.textSecondary),
                  ),
                  items: _categories.map((c) {
                    return DropdownMenuItem(
                      value: c['value'],
                      child: Text(c['label']!, style: GoogleFonts.cairo(), overflow: TextOverflow.ellipsis),
                    );
                  }).toList(),
                  onChanged: (val) => setState(() => _selectedCategory = val!),
                ),
                const SizedBox(height: 16),

                // Title
                TextFormField(
                  controller: _titleController,
                  decoration: const InputDecoration(
                    labelText: 'بيان المصروف (الوصف)',
                    hintText: 'مثال: إصلاح عطل باب المصعد الكهربائي',
                    prefixIcon: Icon(Icons.description_outlined, color: AppColors.textSecondary),
                  ),
                  validator: (val) => val == null || val.trim().isEmpty ? 'يرجى إدخال بيان المصروف' : null,
                ),
                const SizedBox(height: 16),

                // Amount
                TextFormField(
                  controller: _amountController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(
                    labelText: 'المبلغ الإجمالي (درهم)',
                    prefixIcon: Icon(Icons.payments_outlined, color: AppColors.textSecondary),
                  ),
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) return 'يرجى إدخال المبلغ';
                    final num = double.tryParse(val);
                    if (num == null || num <= 0) return 'المبلغ غير صالح';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Date Picker
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
                const SizedBox(height: 18),

                // Invoice Proof Attachment
                Text(
                  'صورة الفاتورة أو وصل الصرف (اختياري):',
                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textPrimary),
                ),
                const SizedBox(height: 8),

                if (_invoiceImage != null)
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.background,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.success),
                    ),
                    child: Row(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.file(
                            _invoiceImage!,
                            width: 50,
                            height: 50,
                            fit: BoxFit.cover,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'تم إرفاق الفاتورة ✓',
                                style: GoogleFonts.cairo(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                  color: AppColors.success,
                                ),
                              ),
                              Text(
                                _invoiceImage!.path.split(Platform.pathSeparator).last,
                                style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete_outline, color: AppColors.danger),
                          onPressed: () => setState(() => _invoiceImage = null),
                          tooltip: 'حذف الصورة',
                        ),
                      ],
                    ),
                  )
                else
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      side: const BorderSide(color: AppColors.border),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _showImageSourceDialog,
                    icon: const Icon(Icons.add_a_photo_outlined, color: AppColors.primary, size: 20),
                    label: Text(
                      'التقاط بالكاميرا أو اختيار من المعرض',
                      style: GoogleFonts.cairo(fontSize: 13, color: AppColors.primary, fontWeight: FontWeight.bold),
                    ),
                  ),

                const SizedBox(height: 24),

                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.danger,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          'حفظ المصروف في السجل',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14),
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
