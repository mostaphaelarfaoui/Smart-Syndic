import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/syndic_provider.dart';

class EditApartmentDialog extends StatefulWidget {
  final ApartmentModel apartment;

  const EditApartmentDialog({super.key, required this.apartment});

  @override
  State<EditApartmentDialog> createState() => _EditApartmentDialogState();
}

class _EditApartmentDialogState extends State<EditApartmentDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _unitNumberController;
  late final TextEditingController _floorController;
  late final TextEditingController _monthlyFeeController;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _unitNumberController = TextEditingController(text: widget.apartment.unitNumber);
    _floorController = TextEditingController(text: widget.apartment.floor.toString());
    _monthlyFeeController = TextEditingController(text: widget.apartment.monthlyFee.toStringAsFixed(0));
  }

  @override
  void dispose() {
    _unitNumberController.dispose();
    _floorController.dispose();
    _monthlyFeeController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    final syndic = context.read<SyndicProvider>();

    final success = await syndic.updateApartment(
      apartmentId: widget.apartment.id,
      unitNumber: _unitNumberController.text.trim(),
      floor: int.tryParse(_floorController.text.trim()) ?? widget.apartment.floor,
      monthlyFee: double.tryParse(_monthlyFeeController.text.trim()) ?? widget.apartment.monthlyFee,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم تحديث بيانات الشقة بنجاح ✓'),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء التحديث'),
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
        constraints: const BoxConstraints(maxWidth: 440),
        child: Padding(
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
                            color: AppColors.primary.withAlpha(20),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.edit_outlined, color: AppColors.primary, size: 22),
                        ),
                        const SizedBox(width: 10),
                        Text(
                          'تعديل بيانات الشقة',
                          style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
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

                TextFormField(
                  controller: _unitNumberController,
                  decoration: const InputDecoration(labelText: 'رقم أو اسم الشقة', prefixIcon: Icon(Icons.door_front_door_outlined)),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'رقم الشقة مطلوب' : null,
                ),
                const SizedBox(height: 14),

                TextFormField(
                  controller: _floorController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'رقم الطابق', prefixIcon: Icon(Icons.layers_outlined)),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'الطابق مطلوب' : null,
                ),
                const SizedBox(height: 14),

                TextFormField(
                  controller: _monthlyFeeController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'الواجب الشهري (درهم)', prefixIcon: Icon(Icons.payments_outlined)),
                  validator: (v) => (double.tryParse(v ?? '') ?? 0) < 0 ? 'الواجب غير صحيح' : null,
                ),
                const SizedBox(height: 24),

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
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: _isSubmitting ? null : _submit,
                        child: _isSubmitting
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : Text('حفظ التعديلات', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
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
