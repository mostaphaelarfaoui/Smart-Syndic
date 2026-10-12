import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/syndic_provider.dart';
import 'edit_apartment_dialog.dart';
import 'record_payment_dialog.dart';

class ApartmentDetailsDialog extends StatelessWidget {
  final ApartmentModel apartment;

  const ApartmentDetailsDialog({super.key, required this.apartment});

  Future<void> _makePhoneCall(String phone) async {
    final cleanPhone = phone.replaceAll(RegExp(r'\s+'), '');
    final uri = Uri.parse('tel:$cleanPhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _openWhatsApp(String phone, String unit) async {
    var cleanPhone = phone.replaceAll(RegExp(r'\s+'), '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '212${cleanPhone.substring(1)}';
    } else if (cleanPhone.startsWith('+')) {
      cleanPhone = cleanPhone.substring(1);
    }
    final message = Uri.encodeComponent('السلام عليكم، بخصوص الشقة رقم $unit في الإقامة.');
    final uri = Uri.parse('https://wa.me/$cleanPhone?text=$message');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _openPdfReceipt(BuildContext context, String? url) async {
    if (url == null || url.isEmpty) return;
    final resolved = ApiService().resolveMediaUrl(url);
    final uri = Uri.parse(resolved);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح وصل الأداء')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final resident = apartment.resident;
    final phone = resident?['phone'] as String?;
    final email = resident?['email'] as String?;
    final hasResident = resident != null;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 520, maxHeight: 720),
        child: Column(
          children: [
            // Header Bar
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.white.withAlpha(40),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.apartment, color: Colors.white, size: 28),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'شقة ${apartment.unitNumber}',
                          style: GoogleFonts.cairo(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        Text(
                          '${apartment.block != null ? "بلوك ${apartment.block} • " : ""}${apartment.buildingName ?? "الإقامة"} • الطابق ${apartment.floor}',
                          style: GoogleFonts.cairo(
                            fontSize: 12,
                            color: Colors.white.withAlpha(220),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),

            // Scrollable Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Feature 4 — Developer-owned apartment exemption banner
                    if (!apartment.isBillable) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 12),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.orange.shade200),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.domain_disabled, color: Colors.orange, size: 22),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                'شقة معفاة من الحسابات المالية (باسم المنعش العقاري)',
                                style: GoogleFonts.cairo(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.orange.shade900,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    // Financial Status Banner
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: apartment.isSettled
                            ? AppColors.success.withAlpha(20)
                            : AppColors.danger.withAlpha(20),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: apartment.isSettled ? AppColors.success : AppColors.danger,
                          width: 1.2,
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            apartment.isSettled
                                ? Icons.verified_user
                                : Icons.warning_amber_rounded,
                            color: apartment.isSettled ? AppColors.success : AppColors.danger,
                            size: 32,
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  apartment.isSettled
                                      ? 'الحالة: خالصة الذمة ✓'
                                      : 'الحالة: توجد متأخرات غير مسددة',
                                  style: GoogleFonts.cairo(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                    color: apartment.isSettled ? AppColors.success : AppColors.danger,
                                  ),
                                ),
                                Text(
                                  apartment.isSettled
                                      ? 'الواجب الشهري: ${apartment.monthlyFee.toStringAsFixed(0)} درهم'
                                      : 'مجموع المتأخرات: ${apartment.arrears.toStringAsFixed(0)} درهم (الشهري: ${apartment.monthlyFee.toStringAsFixed(0)} درهم)',
                                  style: GoogleFonts.cairo(
                                    fontSize: 12,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Resident Information Section
                    Text(
                      'معلومات الساكن / المالك:',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 8),

                    if (hasResident) ...[
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.border),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withAlpha(8),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                CircleAvatar(
                                  radius: 24,
                                  backgroundColor: AppColors.primary.withAlpha(25),
                                  child: const Icon(Icons.person, color: AppColors.primary, size: 28),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        resident['name'] ?? 'ساكن الإقامة',
                                        style: GoogleFonts.cairo(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 15,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: AppColors.primaryDark.withAlpha(20),
                                              borderRadius: BorderRadius.circular(6),
                                            ),
                                            child: Text(
                                              (resident['occupancy_type'] == 'TENANT') ? 'مكتري' : 'مالك الشقة',
                                              style: GoogleFonts.cairo(
                                                fontSize: 11,
                                                fontWeight: FontWeight.w600,
                                                color: AppColors.primaryDark,
                                              ),
                                            ),
                                          ),
                                          if (phone != null) ...[
                                            const SizedBox(width: 8),
                                            Text(
                                              phone,
                                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            if (email != null && email.isNotEmpty) ...[
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  const Icon(Icons.email_outlined, size: 14, color: AppColors.textLight),
                                  const SizedBox(width: 6),
                                  Text(
                                    email,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                ],
                              ),
                            ],
                            const Divider(height: 24),
                            // Quick Action Contact Buttons
                            Row(
                              children: [
                                if (phone != null && phone.isNotEmpty) ...[
                                  Expanded(
                                    child: ElevatedButton.icon(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppColors.success,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 10),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => _openWhatsApp(phone, apartment.unitNumber),
                                      icon: const Icon(Icons.chat, size: 18),
                                      label: Text('واتساب', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: AppColors.primary,
                                        side: const BorderSide(color: AppColors.primary),
                                        padding: const EdgeInsets.symmetric(vertical: 10),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => _makePhoneCall(phone),
                                      icon: const Icon(Icons.call, size: 18),
                                      label: Text('اتصال', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                                    ),
                                  ),
                                ] else ...[
                                  Expanded(
                                    child: Text(
                                      'رقم هاتف الساكن غير مسجل',
                                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                                      textAlign: TextAlign.center,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),
                    ] else ...[
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.info_outline, color: AppColors.warning, size: 24),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                'هذه الشقة فارغة أو لم يقم صاحبها بالتسجيل في التطبيق بعد عبر رمز الدعوة.',
                                style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 24),

                    // Payment History Section
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'سجل الدفعات السابقة (${apartment.payments.length}):',
                          style: GoogleFonts.cairo(
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          'المحصل: ${apartment.totalPaid.toStringAsFixed(0)} درهم',
                          style: GoogleFonts.cairo(
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                            color: AppColors.success,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),

                    if (apartment.payments.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(20),
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          children: [
                            const Icon(Icons.receipt_long_outlined, size: 36, color: AppColors.textLight),
                            const SizedBox(height: 6),
                            Text(
                              'لا توجد أداءات مسجلة لهذه الشقة بعد',
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      )
                    else
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: apartment.payments.length,
                        separatorBuilder: (context, index) => const SizedBox(height: 8),
                        itemBuilder: (context, idx) {
                          final p = apartment.payments[idx];
                          final amount = (p['amount_paid'] as num?)?.toDouble() ?? 0.0;
                          final date = p['payment_date'] ?? '';
                          final receiptNo = p['receipt_number'] ?? '';
                          final receiptUrl = p['receipt_url'] as String?;

                          return Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: AppColors.border),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: AppColors.success.withAlpha(20),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.check, color: AppColors.success, size: 16),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${amount.toStringAsFixed(0)} درهم',
                                        style: GoogleFonts.cairo(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      Text(
                                        '$date • وصل $receiptNo',
                                        style: GoogleFonts.cairo(
                                          fontSize: 11,
                                          color: AppColors.textLight,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                if (receiptUrl != null && receiptUrl.isNotEmpty)
                                  IconButton(
                                    onPressed: () => _openPdfReceipt(context, receiptUrl),
                                    icon: const Icon(Icons.picture_as_pdf, color: AppColors.danger, size: 20),
                                    tooltip: 'معاينة الوصل',
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                  ],
                ),
              ),
            ),

            // Bottom Action Bar: Edit, Delete, Record Payment
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withAlpha(10),
                    blurRadius: 10,
                    offset: const Offset(0, -4),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.primary,
                            side: const BorderSide(color: AppColors.primary),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () {
                            Navigator.pop(context);
                            showDialog(
                              context: context,
                              builder: (_) => EditApartmentDialog(apartment: apartment),
                            );
                          },
                          icon: const Icon(Icons.edit_outlined, size: 16),
                          label: Text('تعديل الشقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.danger,
                            side: const BorderSide(color: AppColors.danger),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: () async {
                            final confirm = await showDialog<bool>(
                              context: context,
                              builder: (ctx) => AlertDialog(
                                title: Text('تأكيد حذف الشقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                                content: Text('هل أنت متأكد من رغبتك في حذف ${apartment.unitNumber}؟', style: GoogleFonts.cairo()),
                                actions: [
                                  TextButton(
                                    onPressed: () => Navigator.pop(ctx, false),
                                    child: Text('إلغاء', style: GoogleFonts.cairo()),
                                  ),
                                  ElevatedButton(
                                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
                                    onPressed: () => Navigator.pop(ctx, true),
                                    child: Text('تأكيد الحذف', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: Colors.white)),
                                  ),
                                ],
                              ),
                            );
                            if (confirm == true && context.mounted) {
                              final syndic = context.read<SyndicProvider>();
                              final success = await syndic.deleteApartment(apartment.id);
                              if (context.mounted) {
                                Navigator.pop(context);
                                if (success) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      backgroundColor: AppColors.success,
                                      content: Text('تم حذف الشقة بنجاح ✓'),
                                    ),
                                  );
                                } else {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      backgroundColor: AppColors.danger,
                                      content: Text(syndic.errorMessage ?? 'تعذر حذف الشقة'),
                                    ),
                                  );
                                }
                              }
                            }
                          },
                          icon: const Icon(Icons.delete_outline, size: 16),
                          label: Text('حذف الشقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      minimumSize: const Size(double.infinity, 44),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () {
                      Navigator.pop(context);
                      showDialog(
                        context: context,
                        builder: (_) => const RecordPaymentDialog(),
                      );
                    },
                    icon: const Icon(Icons.add_circle_outline, size: 18),
                    label: Text(
                      'استخلاص واجب لهذه الشقة',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
