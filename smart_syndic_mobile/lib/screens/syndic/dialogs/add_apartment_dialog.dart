import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../providers/auth_provider.dart';

class AddApartmentDialog extends StatefulWidget {
  final bool initialIsBulk;
  const AddApartmentDialog({super.key, this.initialIsBulk = false});

  @override
  State<AddApartmentDialog> createState() => _AddApartmentDialogState();
}

class _AddApartmentDialogState extends State<AddApartmentDialog> {
  final _formKey = GlobalKey<FormState>();

  // Mode: single (false) vs bulk (true)
  late bool _isBulk;

  // Building selection
  bool _isNewBuilding = false;
  String? _selectedBuildingId;
  final _buildingNameController = TextEditingController();
  final _blockController = TextEditingController();

  // Single mode controllers - EMPTY controllers, placeholders only
  final _unitNumberController = TextEditingController();

  // Bulk mode controllers - EMPTY controllers, placeholders only
  final _totalUnitsController = TextEditingController();
  final _prefixController = TextEditingController();

  // Shared fee controller - EMPTY controller, placeholder only
  final _monthlyFeeController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _isBulk = widget.initialIsBulk;
  }

  @override
  void dispose() {
    _buildingNameController.dispose();
    _blockController.dispose();
    _unitNumberController.dispose();
    _totalUnitsController.dispose();
    _prefixController.dispose();
    _monthlyFeeController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final defaultFee = auth.tenant?.defaultMonthlyFee ?? 200.0;

    final feeText = _monthlyFeeController.text.trim();
    final fee = feeText.isNotEmpty ? (double.tryParse(feeText) ?? defaultFee) : defaultFee;

    setState(() => _isSubmitting = true);

    try {
      bool success = false;

      if (_isBulk) {
        // Bulk generation: only total units, fee, optional prefix
        final totalUnits = int.parse(_totalUnitsController.text.trim());
        final prefix = _prefixController.text.trim();

        success = await syndic.bulkGenerateApartments(
          buildingId: _isNewBuilding ? null : _selectedBuildingId,
          buildingName: _isNewBuilding ? _buildingNameController.text.trim() : null,
          block: _isNewBuilding ? _blockController.text.trim() : null,
          totalUnits: totalUnits,
          monthlyFee: fee,
          unitPrefix: prefix.isNotEmpty ? prefix : '',
          numberingScheme: 'sequential',
        );
      } else {
        // Single apartment: unit number, fee (no floors!)
        success = await syndic.addApartment(
          buildingId: _isNewBuilding ? null : _selectedBuildingId,
          buildingName: _isNewBuilding ? _buildingNameController.text.trim() : null,
          block: _isNewBuilding ? _blockController.text.trim() : null,
          unitNumber: _unitNumberController.text.trim(),
          floor: 0,
          monthlyFee: fee,
        );
      }

      if (mounted) {
        setState(() => _isSubmitting = false);
        if (success) {
          Navigator.pop(context, true);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: AppColors.success,
              content: Text(
                _isBulk ? 'تم توليد وإضافة الشقق بنجاح ✓' : 'تم إضافة الشقة بنجاح ✓',
                style: GoogleFonts.cairo(),
              ),
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: AppColors.danger,
              content: Text(
                syndic.errorMessage ?? 'حدث خطأ أثناء حفظ الشقق',
                style: GoogleFonts.cairo(),
              ),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text('حدث خطأ: $e', style: GoogleFonts.cairo()),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final buildings = syndic.buildings;

    // Auto-select first building if not set and not in new building mode
    if (!_isNewBuilding && _selectedBuildingId == null && buildings.isNotEmpty) {
      _selectedBuildingId = buildings.first['id']?.toString();
    }

    final hasBuildings = buildings.isNotEmpty;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
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
                    Expanded(
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withAlpha(20),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.add_home_work_rounded, color: AppColors.primary, size: 22),
                          ),
                          const SizedBox(width: 10),
                          Flexible(
                            child: Text(
                              'إضافة شقق جديدة',
                              style: GoogleFonts.cairo(
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: AppColors.textLight),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Mode Selector: Single vs Bulk
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () => setState(() => _isBulk = false),
                          borderRadius: BorderRadius.circular(9),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
                            decoration: BoxDecoration(
                              color: !_isBulk ? AppColors.primary : Colors.transparent,
                              borderRadius: BorderRadius.circular(9),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.home_outlined, size: 16, color: !_isBulk ? Colors.white : AppColors.textSecondary),
                                const SizedBox(width: 6),
                                Flexible(
                                  child: Text(
                                    'شقة واحدة',
                                    style: GoogleFonts.cairo(
                                      fontSize: 12,
                                      fontWeight: !_isBulk ? FontWeight.bold : FontWeight.normal,
                                      color: !_isBulk ? Colors.white : AppColors.textSecondary,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: InkWell(
                          onTap: () => setState(() => _isBulk = true),
                          borderRadius: BorderRadius.circular(9),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
                            decoration: BoxDecoration(
                              color: _isBulk ? AppColors.primary : Colors.transparent,
                              borderRadius: BorderRadius.circular(9),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.flash_on_rounded, size: 16, color: _isBulk ? Colors.white : AppColors.textSecondary),
                                const SizedBox(width: 6),
                                Flexible(
                                  child: Text(
                                    'مجموعة شقق',
                                    style: GoogleFonts.cairo(
                                      fontSize: 12,
                                      fontWeight: _isBulk ? FontWeight.bold : FontWeight.normal,
                                      color: _isBulk ? Colors.white : AppColors.textSecondary,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // Building Section
                if (hasBuildings) ...[
                  Row(
                    children: [
                      Expanded(
                        child: ChoiceChip(
                          label: Text('عمارة حالية', style: GoogleFonts.cairo(fontSize: 11)),
                          selected: !_isNewBuilding,
                          onSelected: (val) => setState(() => _isNewBuilding = !val),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ChoiceChip(
                          label: Text('عمارة جديدة', style: GoogleFonts.cairo(fontSize: 11)),
                          selected: _isNewBuilding,
                          onSelected: (val) => setState(() => _isNewBuilding = val),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],

                if (hasBuildings && !_isNewBuilding) ...[
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    initialValue: _selectedBuildingId,
                    decoration: InputDecoration(
                      labelText: 'العمارة',
                      labelStyle: GoogleFonts.cairo(fontSize: 13),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.business, size: 20),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                    items: buildings.map((b) {
                      final blockStr = b['block'] != null ? ' (بلوك ${b['block']})' : '';
                      return DropdownMenuItem<String>(
                        value: b['id']?.toString(),
                        child: Text('${b['name']}$blockStr', style: GoogleFonts.cairo(fontSize: 13), overflow: TextOverflow.ellipsis),
                      );
                    }).toList(),
                    onChanged: (val) => setState(() => _selectedBuildingId = val),
                    validator: (val) => (!_isNewBuilding && val == null) ? 'يرجى اختيار العمارة' : null,
                  ),
                ] else ...[
                  // New building inputs (all placeholders only)
                  Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: TextFormField(
                          controller: _buildingNameController,
                          decoration: InputDecoration(
                            labelText: 'اسم العمارة',
                            hintText: 'مثلاً: عمارة الياسمين',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                            labelStyle: GoogleFonts.cairo(fontSize: 13),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            prefixIcon: const Icon(Icons.business_outlined, size: 20),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                          validator: (v) {
                            if (_isNewBuilding || !hasBuildings) {
                              if (v == null || v.trim().isEmpty) return 'اسم العمارة مطلوب';
                            }
                            return null;
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _blockController,
                          decoration: InputDecoration(
                            labelText: 'البلوك (اختياري)',
                            hintText: 'مثلاً: A',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                            labelStyle: GoogleFonts.cairo(fontSize: 13),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 14),

                // Specific Fields based on Mode:
                if (!_isBulk) ...[
                  // Single Mode: Only Apartment Unit Number (Placeholder only!)
                  TextFormField(
                    controller: _unitNumberController,
                    decoration: InputDecoration(
                      labelText: 'رقم الشقة',
                      hintText: 'مثلاً: 1 أو A1',
                      hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                      labelStyle: GoogleFonts.cairo(fontSize: 13),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.meeting_room_outlined, size: 20),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                    style: GoogleFonts.cairo(fontSize: 13),
                    validator: (val) => (val == null || val.trim().isEmpty) ? 'يرجى إدخال رقم الشقة' : null,
                  ),
                ] else ...[
                  // Bulk Mode: Only Apartment Count & Optional Prefix (Placeholder only!)
                  Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: TextFormField(
                          controller: _totalUnitsController,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            labelText: 'عدد الشقق المراد إضافتها',
                            hintText: 'مثلاً: 10',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                            labelStyle: GoogleFonts.cairo(fontSize: 13),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            prefixIcon: const Icon(Icons.format_list_numbered, size: 20),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                          validator: (val) {
                            final n = int.tryParse(val?.trim() ?? '');
                            if (n == null || n <= 0) return 'أدخل عدداً صحيحاً';
                            return null;
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _prefixController,
                          decoration: InputDecoration(
                            labelText: 'بادئة الترقيم (اختياري)',
                            hintText: 'مثلاً: شقة أو A-',
                            hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 11),
                            labelStyle: GoogleFonts.cairo(fontSize: 13),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                          ),
                          style: GoogleFonts.cairo(fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 14),

                // Monthly Fee (Shared - Placeholder only, starts empty!)
                TextFormField(
                  controller: _monthlyFeeController,
                  decoration: InputDecoration(
                    labelText: 'الواجب الشهري (درهم)',
                    hintText: 'مثلاً: 200',
                    hintStyle: GoogleFonts.cairo(color: AppColors.textLight, fontSize: 12),
                    labelStyle: GoogleFonts.cairo(fontSize: 13),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    prefixIcon: const Icon(Icons.payments_outlined, size: 20),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  style: GoogleFonts.cairo(fontSize: 13),
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) return 'يرجى إدخال الواجب الشهري';
                    final n = double.tryParse(val.trim());
                    if (n == null || n < 0) return 'المبلغ غير صحيح';
                    return null;
                  },
                ),
                const SizedBox(height: 22),

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
                      : Text(
                          _isBulk ? 'توليد وإضافة الشقق' : 'حفظ الشقة',
                          style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
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
