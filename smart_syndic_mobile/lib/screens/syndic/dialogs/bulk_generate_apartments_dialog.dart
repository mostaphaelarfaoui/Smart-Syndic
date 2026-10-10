import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';

class BulkGenerateApartmentsDialog extends StatefulWidget {
  const BulkGenerateApartmentsDialog({super.key});

  @override
  State<BulkGenerateApartmentsDialog> createState() => _BulkGenerateApartmentsDialogState();
}

class _BulkGenerateApartmentsDialogState extends State<BulkGenerateApartmentsDialog> {
  final _formKey = GlobalKey<FormState>();

  bool _isNewBuilding = true;
  String? _selectedBuildingId;

  final _blockController = TextEditingController();
  final _buildingNameController = TextEditingController();
  final _floorsController = TextEditingController(text: '4');
  final _unitsPerFloorController = TextEditingController(text: '3');
  final _totalUnitsController = TextEditingController();
  final _monthlyFeeController = TextEditingController(text: '200');
  final _prefixController = TextEditingController(text: 'شقة ');

  bool _useFloorsCalculation = true;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _blockController.dispose();
    _buildingNameController.dispose();
    _floorsController.dispose();
    _unitsPerFloorController.dispose();
    _totalUnitsController.dispose();
    _monthlyFeeController.dispose();
    _prefixController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final syndic = context.read<SyndicProvider>();

    if (!_isNewBuilding && _selectedBuildingId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('الرجاء اختيار العمارة')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    int? totalFloors;
    int? unitsPerFloor;
    int? totalUnits;

    if (_useFloorsCalculation) {
      totalFloors = int.tryParse(_floorsController.text.trim());
      unitsPerFloor = int.tryParse(_unitsPerFloorController.text.trim());
    } else {
      totalUnits = int.tryParse(_totalUnitsController.text.trim());
    }

    final fee = double.tryParse(_monthlyFeeController.text.trim()) ?? 200.0;
    final prefix = _prefixController.text;

    final success = await syndic.bulkGenerateApartments(
      buildingId: _isNewBuilding ? null : _selectedBuildingId,
      buildingName: _isNewBuilding ? _buildingNameController.text.trim() : null,
      block: _isNewBuilding ? _blockController.text.trim() : null,
      totalFloors: totalFloors,
      unitsPerFloor: unitsPerFloor,
      totalUnits: totalUnits,
      monthlyFee: fee,
      unitPrefix: prefix,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم توليد وإضافة الشقق دفعة واحدة بنجاح ✓'),
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

    int estimatedUnits = 0;
    if (_useFloorsCalculation) {
      final f = int.tryParse(_floorsController.text) ?? 0;
      final u = int.tryParse(_unitsPerFloorController.text) ?? 0;
      estimatedUnits = f * u;
    } else {
      estimatedUnits = int.tryParse(_totalUnitsController.text) ?? 0;
    }

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 500),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
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
                Row(
                  children: [
                    Expanded(
                      child: ChoiceChip(
                        label: Text('عمارة جديدة في المجمع', style: GoogleFonts.cairo(fontSize: 12)),
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

                if (_isNewBuilding) ...[
                  Row(
                    children: [
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _blockController,
                          decoration: const InputDecoration(
                            labelText: 'البلوك (اختياري)',
                            hintText: 'A أو 1',
                            prefixIcon: Icon(Icons.domain),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 3,
                        child: TextFormField(
                          controller: _buildingNameController,
                          decoration: const InputDecoration(
                            labelText: 'اسم العمارة',
                            hintText: 'مثلاً: عمارة الياسمين',
                            prefixIcon: Icon(Icons.business_outlined),
                          ),
                          validator: (v) => (_isNewBuilding && (v == null || v.trim().isEmpty)) ? 'اسم العمارة مطلوب' : null,
                        ),
                      ),
                    ],
                  ),
                ] else ...[
                  DropdownButtonFormField<String>(
                    value: _selectedBuildingId,
                    decoration: const InputDecoration(
                      labelText: 'اختر العمارة',
                      prefixIcon: Icon(Icons.apartment),
                    ),
                    items: buildings.map<DropdownMenuItem<String>>((b) {
                      final blockPrefix = b['block'] != null ? '(${b['block']}) ' : '';
                      return DropdownMenuItem(
                        value: b['id'],
                        child: Text('$blockPrefix${b['name']}', style: GoogleFonts.cairo()),
                      );
                    }).toList(),
                    onChanged: (val) => setState(() => _selectedBuildingId = val),
                    validator: (v) => (!_isNewBuilding && v == null) ? 'الرجاء اختيار العمارة' : null,
                  ),
                ],
                const SizedBox(height: 20),

                // Generation Mode Switcher
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () => setState(() => _useFloorsCalculation = true),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            decoration: BoxDecoration(
                              color: _useFloorsCalculation ? Colors.white : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: _useFloorsCalculation ? [const BoxShadow(color: Colors.black12, blurRadius: 4)] : [],
                            ),
                            child: Text(
                              'بالطوابق والشقق',
                              textAlign: TextAlign.center,
                              style: GoogleFonts.cairo(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: _useFloorsCalculation ? AppColors.primary : AppColors.textSecondary,
                              ),
                            ),
                          ),
                        ),
                      ),
                      Expanded(
                        child: InkWell(
                          onTap: () => setState(() => _useFloorsCalculation = false),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            decoration: BoxDecoration(
                              color: !_useFloorsCalculation ? Colors.white : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: !_useFloorsCalculation ? [const BoxShadow(color: Colors.black12, blurRadius: 4)] : [],
                            ),
                            child: Text(
                              'إجمالي عدد الشقق',
                              textAlign: TextAlign.center,
                              style: GoogleFonts.cairo(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: !_useFloorsCalculation ? AppColors.primary : AppColors.textSecondary,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                if (_useFloorsCalculation) ...[
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _floorsController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'عدد الطوابق', prefixIcon: Icon(Icons.stairs)),
                          validator: (v) => (_useFloorsCalculation && (int.tryParse(v ?? '') ?? 0) <= 0) ? 'مطلوب' : null,
                          onChanged: (_) => setState(() {}),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextFormField(
                          controller: _unitsPerFloorController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'شقق كل طابق', prefixIcon: Icon(Icons.meeting_room)),
                          validator: (v) => (_useFloorsCalculation && (int.tryParse(v ?? '') ?? 0) <= 0) ? 'مطلوب' : null,
                          onChanged: (_) => setState(() {}),
                        ),
                      ),
                    ],
                  ),
                ] else ...[
                  TextFormField(
                    controller: _totalUnitsController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'إجمالي عدد الشقق المراد إضافتها',
                      prefixIcon: Icon(Icons.format_list_numbered),
                    ),
                    validator: (v) => (!_useFloorsCalculation && (int.tryParse(v ?? '') ?? 0) <= 0) ? 'مطلوب' : null,
                    onChanged: (_) => setState(() {}),
                  ),
                ],
                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      flex: 3,
                      child: TextFormField(
                        controller: _monthlyFeeController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'الواجب الشهري (د.م)',
                          prefixIcon: Icon(Icons.payments_outlined),
                        ),
                        validator: (v) => (double.tryParse(v ?? '') ?? 0) < 0 ? 'غير صحيح' : null,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: TextFormField(
                        controller: _prefixController,
                        decoration: const InputDecoration(
                          labelText: 'بادئة الترقيم',
                          hintText: 'شقة ',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Calculation Result Preview
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(15),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline, size: 20, color: AppColors.primary),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'سيتم توليد $estimatedUnits شقة فوراً مع تحديد الطابق والواجب الشهري لكل شقة ✓',
                          style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Buttons
                Row(
                  children: [
                    Expanded(
                      child: TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _isSubmitting ? null : _submit,
                        icon: _isSubmitting
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Icon(Icons.bolt, size: 18),
                        label: Text(
                          'توليد الشقق الآن',
                          style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
