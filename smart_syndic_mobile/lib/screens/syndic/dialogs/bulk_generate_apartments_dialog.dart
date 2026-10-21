import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../providers/auth_provider.dart';

class BulkGenerateApartmentsDialog extends StatefulWidget {
  const BulkGenerateApartmentsDialog({super.key});

  @override
  State<BulkGenerateApartmentsDialog> createState() => _BulkGenerateApartmentsDialogState();
}

class _BulkGenerateApartmentsDialogState extends State<BulkGenerateApartmentsDialog> {
  final _formKey = GlobalKey<FormState>();

  bool _isNewBuilding = true;
  String? _selectedBuildingId;

  // Controllers start empty with placeholders only
  final _blockController = TextEditingController();
  final _buildingNameController = TextEditingController();
  final _totalUnitsController = TextEditingController();
  final _monthlyFeeController = TextEditingController();
  final _prefixController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void dispose() {
    _blockController.dispose();
    _buildingNameController.dispose();
    _totalUnitsController.dispose();
    _monthlyFeeController.dispose();
    _prefixController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final syndic = context.read<SyndicProvider>();
    final auth = context.read<AuthProvider>();
    final defaultFee = auth.tenant?.defaultMonthlyFee ?? 200.0;

    if (!_isNewBuilding && _selectedBuildingId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('الرجاء اختيار العمارة')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final totalUnits = int.tryParse(_totalUnitsController.text.trim()) ?? 0;
    final feeText = _monthlyFeeController.text.trim();
    final fee = feeText.isNotEmpty ? (double.tryParse(feeText) ?? defaultFee) : defaultFee;
    final prefix = _prefixController.text.trim();

    final success = await syndic.bulkGenerateApartments(
      buildingId: _isNewBuilding ? null : _selectedBuildingId,
      buildingName: _isNewBuilding ? _buildingNameController.text.trim() : null,
      block: _isNewBuilding ? _blockController.text.trim() : null,
      totalUnits: totalUnits,
      monthlyFee: fee,
      unitPrefix: prefix.isNotEmpty ? prefix : '',
      numberingScheme: 'sequential',
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم توليد وإضافة الشقق بنجاح ✓'),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء توليد الشقق'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final buildings = syndic.buildings;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(22),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withAlpha(20),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.flash_on, color: AppColors.primary, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'توليد الشقق دفعة واحدة',
                              style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                            ),
                            Text(
                              'إضافة عمارة وشققها في ثانية واحدة',
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: AppColors.textSecondary),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const Divider(height: 24),

                // Choose New vs Existing Building
                if (buildings.isNotEmpty) ...[
                  Row(
                    children: [
                      Expanded(
                        child: ChoiceChip(
                          label: Text('عمارة جديدة', style: GoogleFonts.cairo(fontSize: 12)),
                          selected: _isNewBuilding,
                          onSelected: (val) => setState(() => _isNewBuilding = true),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ChoiceChip(
                          label: Text('عمارة حالية', style: GoogleFonts.cairo(fontSize: 12)),
                          selected: !_isNewBuilding,
                          onSelected: (val) => setState(() => _isNewBuilding = false),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                ],

                if (_isNewBuilding || buildings.isEmpty) ...[
                  Row(
                    children: [
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _blockController,
                          decoration: InputDecoration(
                            labelText: 'البلوك (اختياري)',
                            hintText: 'مثلاً: A أو 1',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                            prefixIcon: const Icon(Icons.domain, size: 20),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 3,
                        child: TextFormField(
                          controller: _buildingNameController,
                          decoration: InputDecoration(
                            labelText: 'اسم العمارة',
                            hintText: 'مثلاً: عمارة الياسمين',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                            prefixIcon: const Icon(Icons.business_outlined, size: 20),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                          validator: (v) => ((_isNewBuilding || buildings.isEmpty) && (v == null || v.trim().isEmpty)) ? 'اسم العمارة مطلوب' : null,
                        ),
                      ),
                    ],
                  ),
                ] else ...[
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    initialValue: _selectedBuildingId,
                    decoration: InputDecoration(
                      labelText: 'اختر العمارة',
                      prefixIcon: const Icon(Icons.apartment, size: 20),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                    items: buildings.map<DropdownMenuItem<String>>((b) {
                      final blockPrefix = b['block'] != null ? '(${b['block']}) ' : '';
                      return DropdownMenuItem(
                        value: b['id']?.toString(),
                        child: Text('$blockPrefix${b['name']}', style: GoogleFonts.cairo(fontSize: 13), overflow: TextOverflow.ellipsis),
                      );
                    }).toList(),
                    onChanged: (val) => setState(() => _selectedBuildingId = val),
                    validator: (v) => (!_isNewBuilding && v == null) ? 'الرجاء اختيار العمارة' : null,
                  ),
                ],
                const SizedBox(height: 16),

                // Total Units only (No floors, no floors calculation!)
                Row(
                  children: [
                    Expanded(
                      flex: 3,
                      child: TextFormField(
                        controller: _totalUnitsController,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          labelText: 'عدد الشقق المراد إضافتها',
                          hintText: 'مثلاً: 12',
                          hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                          prefixIcon: const Icon(Icons.format_list_numbered, size: 20),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                        style: GoogleFonts.cairo(fontSize: 13),
                        validator: (v) {
                          final n = int.tryParse(v?.trim() ?? '');
                          if (n == null || n <= 0) return 'أدخل عدداً صحيحاً';
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: TextFormField(
                        controller: _prefixController,
                        decoration: InputDecoration(
                          labelText: 'بادئة الترقيم (اختياري)',
                          hintText: 'مثلاً: شقة أو A-',
                          hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 11),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                        style: GoogleFonts.cairo(fontSize: 13),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Monthly Fee (placeholder only)
                TextFormField(
                  controller: _monthlyFeeController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    labelText: 'الواجب الشهري (درهم)',
                    hintText: 'مثلاً: 200',
                    hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                    prefixIcon: const Icon(Icons.payments_outlined, size: 20),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  style: GoogleFonts.cairo(fontSize: 13),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'يرجى إدخال الواجب الشهري';
                    final n = double.tryParse(v.trim());
                    if (n == null || n < 0) return 'المبلغ غير صحيح';
                    return null;
                  },
                ),
                const SizedBox(height: 24),

                // Submit Button
                ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 2,
                  ),
                  child: _isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text('توليد وإضافة الشقق', style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
