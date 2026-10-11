import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';

class AddApartmentDialog extends StatefulWidget {
  const AddApartmentDialog({super.key});

  @override
  State<AddApartmentDialog> createState() => _AddApartmentDialogState();
}

class _AddApartmentDialogState extends State<AddApartmentDialog> {
  final _formKey = GlobalKey<FormState>();
  final _unitNumberController = TextEditingController();
  final _floorController = TextEditingController();
  final _monthlyFeeController = TextEditingController();
  String? _selectedBuildingId;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _unitNumberController.dispose();
    _floorController.dispose();
    _monthlyFeeController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedBuildingId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.danger,
          content: Text('الرجاء اختيار العمارة'),
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final syndic = Provider.of<SyndicProvider>(context, listen: false);

    final success = await syndic.addApartment(
      buildingId: _selectedBuildingId!,
      unitNumber: _unitNumberController.text.trim(),
      floor: int.tryParse(_floorController.text.trim()) ?? 0,
      monthlyFee: double.tryParse(_monthlyFeeController.text.trim()) ?? 0.0,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم إضافة الشقة بنجاح ✓'),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء إضافة الشقة'),
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
                          child: const Icon(Icons.add_home_work, color: AppColors.primary, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'إضافة شقة جديدة',
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
                const SizedBox(height: 24),

                if (buildings.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withAlpha(30),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: AppColors.warning),
                    ),
                    child: Text(
                      'تنبيه: لا يوجد أي عمارة مسجلة بعد في قاعدة البيانات. يرجى إضافة عمارة من لوحة التحكم الرئيسية أولاً.',
                      style: GoogleFonts.cairo(color: Colors.orange.shade800, fontSize: 13),
                    ),
                  )
                else
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: 'العمارة',
                      labelStyle: GoogleFonts.cairo(),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.business),
                    ),
                    initialValue: _selectedBuildingId,
                    items: buildings.map((b) {
                      return DropdownMenuItem<String>(value: b['id'], child: Text(b['name'], style: GoogleFonts.cairo(), overflow: TextOverflow.ellipsis));
                    }).toList(),
                    onChanged: (val) => setState(() => _selectedBuildingId = val),
                    validator: (val) => val == null ? 'مطلوب' : null,
                  ),

                const SizedBox(height: 16),
                TextFormField(
                  controller: _unitNumberController,
                  decoration: InputDecoration(
                    labelText: 'رقم الشقة',
                    labelStyle: GoogleFonts.cairo(),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    prefixIcon: const Icon(Icons.meeting_room),
                  ),
                  style: GoogleFonts.cairo(),
                  validator: (val) => val == null || val.isEmpty ? 'مطلوب' : null,
                ),

                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _floorController,
                        decoration: InputDecoration(
                          labelText: 'الطابق',
                          labelStyle: GoogleFonts.cairo(),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          prefixIcon: const Icon(Icons.layers),
                        ),
                        keyboardType: TextInputType.number,
                        style: GoogleFonts.cairo(),
                        validator: (val) => val == null || val.isEmpty ? 'مطلوب' : null,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: TextFormField(
                        controller: _monthlyFeeController,
                        decoration: InputDecoration(
                          labelText: 'الواجب الشهري (درهم)',
                          labelStyle: GoogleFonts.cairo(),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          prefixIcon: const Icon(Icons.attach_money),
                        ),
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        style: GoogleFonts.cairo(),
                        validator: (val) => val == null || val.isEmpty ? 'مطلوب' : null,
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _isSubmitting || buildings.isEmpty ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text('حفظ الشقة', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
