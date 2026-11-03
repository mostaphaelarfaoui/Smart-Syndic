import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/app_alert.dart';

class CreateAnnouncementDialog extends StatefulWidget {
  const CreateAnnouncementDialog({super.key});

  @override
  State<CreateAnnouncementDialog> createState() => _CreateAnnouncementDialogState();
}

class _CreateAnnouncementDialogState extends State<CreateAnnouncementDialog> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _contentController = TextEditingController();

  String _priority = 'NORMAL';
  bool _isPinned = false;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _titleController.dispose();
    _contentController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);
    final provider = context.read<SyndicProvider>();

    final success = await provider.createAnnouncement(
      title: _titleController.text.trim(),
      content: _contentController.text.trim(),
      priority: _priority,
      isPinned: _isPinned,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        await AppAlert.success(context, 'تم نشر الإعلان وإشعار السكان');
        if (mounted) Navigator.pop(context, true);
      } else {
        AppAlert.error(
          context,
          provider.errorMessage ?? 'تعذر نشر الإعلان',
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Container(
        padding: const EdgeInsets.all(20),
        constraints: const BoxConstraints(maxWidth: 520, maxHeight: 680),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: const Color(0xff4f46e5).withAlpha(25),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Center(
                      child: Icon(Icons.campaign_outlined, color: Color(0xff4f46e5), size: 24),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'نشر إعلان جديد',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        Text(
                          'يصل إشعار فوري لجميع السكان',
                          style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, size: 20),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const Divider(height: 20),

              // Form content
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Title
                      Text(
                        'عنوان الإعلان',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12.5),
                      ),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _titleController,
                        decoration: InputDecoration(
                          prefixIcon: const Icon(Icons.title_rounded, size: 20),
                          hintText: 'مثال: انقطاع مؤقت للماء الصالح للشرب يوم الأربعاء',
                          hintStyle: GoogleFonts.cairo(fontSize: 12),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        style: GoogleFonts.cairo(fontSize: 13),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'يرجى إدخال عنوان الإعلان' : null,
                      ),
                      const SizedBox(height: 14),

                      // Priority Selection
                      Text(
                        'درجة الأهمية',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12.5),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Expanded(
                            child: _buildPriorityOption('NORMAL', 'عادي', const Color(0xff64748b)),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: _buildPriorityOption('INFO', 'تنبيه هام', const Color(0xff4f46e5)),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: _buildPriorityOption('URGENT', 'عاجل', const Color(0xffdc2626)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Pinned Checkbox
                      Container(
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: CheckboxListTile(
                          value: _isPinned,
                          onChanged: (v) => setState(() => _isPinned = v ?? false),
                          title: Text(
                            '📌 تثبيت في أعلى قائمة الإعلانات',
                            style: GoogleFonts.cairo(fontSize: 12.5, fontWeight: FontWeight.bold),
                          ),
                          subtitle: Text(
                            'يجعل هذا الإعلان يظهر دائماً في القمة لجميع السكان',
                            style: GoogleFonts.cairo(fontSize: 10.5, color: AppColors.textSecondary),
                          ),
                          controlAffinity: ListTileControlAffinity.leading,
                          dense: true,
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Content
                      Text(
                        'نص أو بيان الإعلان',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12.5),
                      ),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _contentController,
                        maxLines: 5,
                        decoration: InputDecoration(
                          hintText: 'اكتب هنا تفاصيل ومضمون الإعلان الموجه لكافة السكان...',
                          hintStyle: GoogleFonts.cairo(fontSize: 12),
                          contentPadding: const EdgeInsets.all(14),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        style: GoogleFonts.cairo(fontSize: 13, height: 1.5),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'يرجى كتابة نص الإعلان' : null,
                      ),
                      const SizedBox(height: 12),

                      // Delivery notice
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xff4f46e5).withAlpha(12),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xff4f46e5).withAlpha(30)),
                        ),
                        child: Row(
                          children: [
                            const Text('🔔', style: TextStyle(fontSize: 18)),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'تصل هذه الرسالة لجميع السكان في خدمة الإشعارات فوراً.',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: const Color(0xff4338ca),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 14),

              // Action buttons
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onPressed: () => Navigator.pop(context),
                      child: Text('إلغاء', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xff4f46e5),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onPressed: _isSubmitting ? null : _submit,
                      icon: _isSubmitting
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Icon(Icons.campaign_rounded, size: 20),
                      label: Text(
                        _isSubmitting ? 'جاري النشر والإشعار...' : 'نشر وإشعار السكان',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPriorityOption(String val, String label, Color color) {
    final isSelected = _priority == val;
    return InkWell(
      onTap: () => setState(() => _priority = val),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        decoration: BoxDecoration(
          color: isSelected ? color.withAlpha(20) : AppColors.background,
          border: Border.all(
            color: isSelected ? color : AppColors.border,
            width: isSelected ? 1.5 : 1,
          ),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: GoogleFonts.cairo(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
            color: isSelected ? color : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}
