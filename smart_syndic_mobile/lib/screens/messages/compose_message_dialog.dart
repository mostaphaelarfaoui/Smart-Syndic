import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../models/apartment_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/message_provider.dart';
import '../../providers/syndic_provider.dart';

class ComposeMessageDialog extends StatefulWidget {
  final String? initialResidentId;
  final String? initialApartmentUnit;

  const ComposeMessageDialog({
    super.key,
    this.initialResidentId,
    this.initialApartmentUnit,
  });

  @override
  State<ComposeMessageDialog> createState() => _ComposeMessageDialogState();
}

class _ComposeMessageDialogState extends State<ComposeMessageDialog> {
  final _formKey = GlobalKey<FormState>();
  final _bodyController = TextEditingController();

  String _category = 'GENERAL';
  String _priority = 'NORMAL';
  final String _targetType = 'INDIVIDUAL';
  String? _selectedResidentId;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialResidentId != null) {
      _selectedResidentId = widget.initialResidentId;
    }
  }

  @override
  void dispose() {
    _bodyController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();
    final isSyndic = auth.isSyndic;

    List<String>? targetIds;
    if (isSyndic) {
      if (_selectedResidentId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('يرجى اختيار الساكن لبدء المحادثة معه')),
        );
        return;
      }
      targetIds = [_selectedResidentId!];
    }

    setState(() => _isSubmitting = true);
    final msgProvider = context.read<MessageProvider>();

    final bodyText = _bodyController.text.trim();
    final cleanBody = bodyText.replaceAll(RegExp(r'\s+'), ' ');
    final fallbackSubject = cleanBody.length > 50 ? '${cleanBody.substring(0, 47)}...' : cleanBody;

    final success = await msgProvider.sendMessage(
      subject: fallbackSubject,
      body: bodyText,
      category: _category,
      priority: _priority,
      targetType: isSyndic ? _targetType : 'SYNDIC',
      targetIds: targetIds,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: AppColors.success,
            content: Text('تم إرسال الرسالة بنجاح ✓'),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(msgProvider.errorMessage ?? 'تعذر إرسال الرسالة'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final isSyndic = auth.isSyndic;
    final syndic = isSyndic ? context.watch<SyndicProvider>() : null;
    final apartments = syndic?.apartments ?? <ApartmentModel>[];

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Container(
        padding: const EdgeInsets.all(20),
        constraints: const BoxConstraints(maxWidth: 500, maxHeight: 680),
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
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withAlpha(25),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.email_outlined, color: AppColors.primary, size: 22),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSyndic ? 'إرسال رسالة للسكان' : 'مراسلة إدارة السانديك',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        Text(
                          isSyndic ? 'توجيه رسالة عامة أو مخصصة' : 'استفسار، شكاية، أو طلب صيانة',
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

              // Form fields in scrollable area
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Target Resident for Syndic (Direct 1-on-1 Chat)
                      if (isSyndic) ...[
                        Text(
                          '👤 اختر الساكن (محادثة مباشرة فردية)',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12.5),
                        ),
                        const SizedBox(height: 6),
                        DropdownButtonFormField<String>(
                          initialValue: _selectedResidentId,
                          decoration: InputDecoration(
                            prefixIcon: const Icon(Icons.person_outline, size: 20),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            hintText: '-- اختر الشقة أو الساكن لبدء المحادثة --',
                            hintStyle: GoogleFonts.cairo(fontSize: 12),
                          ),
                          items: apartments.map((apt) {
                            final resName = apt.resident != null ? (apt.resident!['name'] ?? 'ساكن الإقامة') : 'شاغرة';
                            final resVal = apt.resident != null ? (apt.resident!['id']?.toString() ?? apt.id) : apt.id;
                            return DropdownMenuItem(
                              value: resVal,
                              child: Text('${apt.formattedUnit} ($resName)', style: GoogleFonts.cairo(fontSize: 12)),
                            );
                          }).toList(),
                          onChanged: (val) => setState(() => _selectedResidentId = val),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '💡 الرسائل مخصصة للتواصل المباشر الفردي فقط. للإعلانات العامة الموجهة للجميع، يرجى استخدام قسم الإعلانات.',
                          style: GoogleFonts.cairo(fontSize: 10.5, color: AppColors.textSecondary),
                        ),
                        const SizedBox(height: 12),
                      ],

                      // Category and Priority in one row
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('التصنيف', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                                const SizedBox(height: 6),
                                DropdownButtonFormField<String>(
                                  initialValue: _category,
                                  decoration: InputDecoration(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  items: [
                                    DropdownMenuItem(value: 'GENERAL', child: Text('عامة', style: GoogleFonts.cairo(fontSize: 12))),
                                    if (isSyndic)
                                      DropdownMenuItem(value: 'ANNOUNCEMENT', child: Text('إعلان رسمي', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'INQUIRY', child: Text('استفسار', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'COMPLAINT', child: Text('شكاية', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'MAINTENANCE', child: Text('صيانة وأشغال', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'FINANCIAL', child: Text('شؤون مالية', style: GoogleFonts.cairo(fontSize: 12))),
                                  ],
                                  onChanged: (v) => setState(() => _category = v ?? 'GENERAL'),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('الأهمية', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                                const SizedBox(height: 6),
                                DropdownButtonFormField<String>(
                                  initialValue: _priority,
                                  decoration: InputDecoration(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  items: [
                                    DropdownMenuItem(value: 'NORMAL', child: Text('عادي', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'HIGH', child: Text('⚠️ هام', style: GoogleFonts.cairo(fontSize: 12))),
                                    DropdownMenuItem(value: 'URGENT', child: Text('🚨 عاجل', style: GoogleFonts.cairo(fontSize: 12))),
                                  ],
                                  onChanged: (v) => setState(() => _priority = v ?? 'NORMAL'),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Body
                      Text('نص الرسالة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _bodyController,
                        maxLines: 5,
                        decoration: InputDecoration(
                          hintText: 'اكتب نص رسالتك بالتفصيل هنا...',
                          hintStyle: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                          contentPadding: const EdgeInsets.all(12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'يرجى كتابة نص الرسالة' : null,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Actions
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
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onPressed: _isSubmitting ? null : _submit,
                      icon: _isSubmitting
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Icon(Icons.send_rounded, size: 18),
                      label: Text(
                        _isSubmitting ? 'جاري الإرسال...' : 'إرسال الرسالة',
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

}
