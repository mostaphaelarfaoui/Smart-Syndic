import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/number_utils.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/syndic_provider.dart';
import 'edit_apartment_dialog.dart';
import 'record_payment_dialog.dart';
import '../../../widgets/confirm_action_dialog.dart';
import '../../common/receipt_viewer_screen.dart';

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

  void _openPdfReceipt(BuildContext context, String? url, String receiptNumber, double amount) {
    if (url == null || url.isEmpty) return;
    ReceiptViewerScreen.open(
      context,
      receiptUrl: url,
      receiptNumber: receiptNumber,
      residentName: apartment.resident?['name'] as String?,
      residentPhone: apartment.resident?['phone'] as String?,
      unitNumber: apartment.unitNumber,
      amountPaid: amount,
    );
  }

  void _downloadDirectReceipt(BuildContext context, String? url, String receiptNumber) {
    if (url == null || url.isEmpty) return;
    ReceiptViewerScreen.downloadDirectly(
      context,
      receiptUrl: url,
      receiptNumber: receiptNumber,
    );
  }

  Future<void> _toggleExemption(BuildContext context) async {
    final isBillable = apartment.isBillable;
    final title = isBillable ? 'تأكيد إعفاء الشقة من واجبات السانديك' : 'إلغاء إعفاء الشقة وإعادة الفوترة';
    final message = isBillable
        ? 'هل أنت متأكد من رغبتك في إعفاء الشقة ${apartment.unitNumber} من واجبات السانديك الشهرية؟\n\n• لن تُحسب عليها أي متأخرات.\n• سيتم استثناؤها من المداخيل الشهرية المتوقعة للإقامة.'
        : 'هل تريد إلغاء إعفاء الشقة ${apartment.unitNumber} وإعادة إدراجها في حسابات واجبات السانديك الشهرية والمتأخرات؟';

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            Icon(
              isBillable ? Icons.money_off_rounded : Icons.restore_rounded,
              color: isBillable ? AppColors.warning : AppColors.success,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(title, style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
            ),
          ],
        ),
        content: Text(message, style: GoogleFonts.cairo(fontSize: 13, height: 1.5)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: isBillable ? AppColors.warning : AppColors.success,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              isBillable ? 'تأكيد الإعفاء' : 'إعادة الفوترة',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: Colors.white),
            ),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final syndic = context.read<SyndicProvider>();
      final ok = await syndic.toggleApartmentBillable(apartment.id);
      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              ok
                  ? (isBillable ? 'تم إعفاء الشقة من واجبات السانديك بنجاح ✓' : 'تم إلغاء الإعفاء وإعادة فوترة الشقة بنجاح ✓')
                  : (syndic.errorMessage ?? 'تعذر تحديث حالة الإعفاء'),
              style: GoogleFonts.cairo(),
            ),
            backgroundColor: ok ? AppColors.success : AppColors.danger,
          ),
        );
      }
    }
  }

  Future<void> _vacateApartment(BuildContext context) async {
    final resident = apartment.resident;
    final residentName = resident?['name'] ?? 'الساكن';
    final confirm = await ConfirmActionDialog.show(
      context,
      title: 'إخلاء الشقة وحذف حساب الساكن',
      message: 'هل أنت متأكد من رغبتك في إخلاء الشقة ${apartment.unitNumber} وحذف حساب $residentName؟\n\n'
          '• سيتم حذف حساب الساكن نهائياً لتصبح الشقة شاغرة وقابلة لربط شخص آخر بها.\n'
          '• سيتم تصفير وإرجاع الحسابات والمبالغ للصيغة الأولى (0 درهم متأخرات).',
      confirmText: 'تأكيد الإخلاء والحذف',
      type: ConfirmDialogType.danger,
      requiresPassword: true,
      passwordHint: 'عملية حساسة: يرجى إدخال كلمة مرور حساب السانديك لتأكيد الحذف والإخلاء',
    );

    if (confirm == true && context.mounted) {
      final syndic = context.read<SyndicProvider>();
      final ok = await syndic.vacateApartment(apartment.id);
      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              ok
                  ? 'تم إخلاء الشقة وتصفير سجلاتها وحذف حساب الساكن بنجاح ✓'
                  : (syndic.errorMessage ?? 'تعذر إخلاء الشقة'),
              style: GoogleFonts.cairo(),
            ),
            backgroundColor: ok ? AppColors.success : AppColors.danger,
          ),
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
                          formatUnitLabel(apartment.unitNumber),
                          style: GoogleFonts.cairo(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        Text(
                          '${apartment.block != null ? "بلوك ${apartment.block} • " : ""}${apartment.buildingName ?? "الإقامة"}',
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
                    // Exemption Banner
                    if (!apartment.isBillable) ...[
                      Container(
                        padding: const EdgeInsets.all(14),
                        margin: const EdgeInsets.only(bottom: 14),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade50,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.orange.shade300, width: 1.2),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.orange.shade100,
                                shape: BoxShape.circle,
                              ),
                              child: Icon(Icons.shield_outlined, color: Colors.orange.shade900, size: 22),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'شقة معفاة من واجبات السانديك',
                                    style: GoogleFonts.cairo(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.orange.shade900,
                                    ),
                                  ),
                                  Text(
                                    'هذه الشقة معفاة حالياً من المستحقات الشهرية ولا تترتب عليها أي متأخرات مالية للإقامة.',
                                    style: GoogleFonts.cairo(
                                      fontSize: 11,
                                      color: Colors.orange.shade800,
                                    ),
                                  ),
                                ],
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
                        color: !apartment.isBillable
                            ? Colors.orange.shade50
                            : (apartment.isSettled
                                ? AppColors.success.withAlpha(20)
                                : AppColors.danger.withAlpha(20)),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: !apartment.isBillable
                              ? Colors.orange.shade400
                              : (apartment.isSettled ? AppColors.success : AppColors.danger),
                          width: 1.2,
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            !apartment.isBillable
                                ? Icons.money_off_rounded
                                : (apartment.isSettled
                                    ? Icons.verified_user
                                    : Icons.warning_amber_rounded),
                            color: !apartment.isBillable
                                ? Colors.orange.shade800
                                : (apartment.isSettled ? AppColors.success : AppColors.danger),
                            size: 32,
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  !apartment.isBillable
                                      ? 'الحالة: معفاة من أداء الواجبات ✓'
                                      : (apartment.isSettled
                                          ? 'الحالة: خالصة الذمة ✓'
                                          : 'الحالة: توجد متأخرات غير مسددة'),
                                  style: GoogleFonts.cairo(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                    color: !apartment.isBillable
                                        ? Colors.orange.shade900
                                        : (apartment.isSettled ? AppColors.success : AppColors.danger),
                                  ),
                                ),
                                Text(
                                  !apartment.isBillable
                                      ? 'الواجب الشهري المسجل: ${apartment.monthlyFee.toStringAsFixed(0)} درهم (معفى) • المتأخرات: 0 درهم'
                                      : (apartment.isSettled
                                          ? 'الواجب الشهري: ${apartment.monthlyFee.toStringAsFixed(0)} درهم'
                                          : 'مجموع المتأخرات: ${apartment.arrears.toStringAsFixed(0)} درهم (الشهري: ${apartment.monthlyFee.toStringAsFixed(0)} درهم)'),
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
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: AppColors.danger,
                                  side: BorderSide(color: AppColors.danger.withAlpha(120), width: 1.2),
                                  backgroundColor: AppColors.danger.withAlpha(10),
                                  padding: const EdgeInsets.symmetric(vertical: 8),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: () => _vacateApartment(context),
                                icon: const Icon(Icons.person_remove_outlined, size: 16),
                                label: Text(
                                  'إخلاء الشقة وحذف حساب الساكن',
                                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ),
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
                      Builder(
                        builder: (context) {
                          final Map<String, List<Map<String, dynamic>>> grouped = {};
                          for (final p in apartment.payments) {
                            String year = 'أخرى';
                            final date = p['payment_date']?.toString();
                            if (date != null && date.length >= 4) {
                              year = date.substring(0, 4);
                            } else if (p['covered_months'] is List && (p['covered_months'] as List).isNotEmpty) {
                              final m = (p['covered_months'] as List).first.toString();
                              if (m.length >= 4) year = m.substring(0, 4);
                            }
                            grouped.putIfAbsent(year, () => []).add(p);
                          }
                          final sortedYears = grouped.keys.toList()..sort((a, b) => b.compareTo(a));

                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: sortedYears.map((year) {
                              final yearPayments = grouped[year]!;
                              final yearTotal = yearPayments.fold<double>(
                                0.0,
                                (sum, p) => sum + parseDouble(p['amount_paid']),
                              );

                              return Container(
                                margin: const EdgeInsets.only(bottom: 12),
                                decoration: BoxDecoration(
                                  color: AppColors.background,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: AppColors.border),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.stretch,
                                  children: [
                                    // Year Header
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                      decoration: BoxDecoration(
                                        color: AppColors.primary.withAlpha(12),
                                        borderRadius: const BorderRadius.vertical(top: Radius.circular(13)),
                                      ),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Row(
                                            children: [
                                              const Icon(Icons.calendar_today_rounded, size: 16, color: AppColors.primary),
                                              const SizedBox(width: 8),
                                              Text(
                                                'سنة $year',
                                                style: GoogleFonts.cairo(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 13,
                                                  color: AppColors.primaryDark,
                                                ),
                                              ),
                                              const SizedBox(width: 6),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                                decoration: BoxDecoration(
                                                  color: AppColors.primary.withAlpha(20),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  '${yearPayments.length} وصل',
                                                  style: GoogleFonts.cairo(
                                                    fontSize: 10.5,
                                                    fontWeight: FontWeight.bold,
                                                    color: AppColors.primaryDark,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                          Text(
                                            '${yearTotal.toStringAsFixed(0)} درهم',
                                            style: GoogleFonts.cairo(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 13,
                                              color: AppColors.success,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const Divider(height: 1, thickness: 1, color: AppColors.border),
                                    // Payments in this year
                                    ListView.separated(
                                      shrinkWrap: true,
                                      physics: const NeverScrollableScrollPhysics(),
                                      padding: const EdgeInsets.all(10),
                                      itemCount: yearPayments.length,
                                      separatorBuilder: (context, index) => const SizedBox(height: 8),
                                      itemBuilder: (context, idx) {
                                        final p = yearPayments[idx];
                                        final amount = parseDouble(p['amount_paid']);
                                        final date = p['payment_date'] ?? '';
                                        final receiptNo = p['receipt_number'] ?? '';
                                        final receiptUrl = p['receipt_url'] as String?;
                                        final months = (p['covered_months'] as List?)?.join(', ') ?? '';
                                        final hasBottomRow = date.isNotEmpty || (receiptUrl != null && receiptUrl.isNotEmpty);

                                        return Container(
                                          padding: const EdgeInsets.all(12),
                                          decoration: BoxDecoration(
                                            color: Colors.white,
                                            borderRadius: BorderRadius.circular(10),
                                            border: Border.all(color: AppColors.border),
                                          ),
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.stretch,
                                            children: [
                                              // Row 1: Month badge & receipt number on right, amount on left
                                              Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Expanded(
                                                    child: Row(
                                                      mainAxisSize: MainAxisSize.min,
                                                      children: [
                                                        Flexible(
                                                          child: Container(
                                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                                            decoration: BoxDecoration(
                                                              color: AppColors.success.withAlpha(15),
                                                              borderRadius: BorderRadius.circular(6),
                                                            ),
                                                            child: Text(
                                                              months.isNotEmpty ? 'شهر $months' : 'أداء',
                                                              style: GoogleFonts.cairo(
                                                                fontSize: 11,
                                                                fontWeight: FontWeight.bold,
                                                                color: AppColors.success,
                                                              ),
                                                              overflow: TextOverflow.ellipsis,
                                                            ),
                                                          ),
                                                        ),
                                                        if (receiptNo.isNotEmpty) ...[
                                                          const SizedBox(width: 8),
                                                          Flexible(
                                                            child: Text(
                                                              'وصل $receiptNo',
                                                              style: GoogleFonts.cairo(
                                                                fontWeight: FontWeight.bold,
                                                                fontSize: 12,
                                                                color: AppColors.textPrimary,
                                                              ),
                                                              overflow: TextOverflow.ellipsis,
                                                            ),
                                                          ),
                                                        ],
                                                      ],
                                                    ),
                                                  ),
                                                  const SizedBox(width: 8),
                                                  Text(
                                                    '${amount.toStringAsFixed(0)} درهم',
                                                    style: GoogleFonts.cairo(
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 13.5,
                                                      color: AppColors.success,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                              if (hasBottomRow) ...[
                                                const SizedBox(height: 8),
                                                const Divider(height: 1, thickness: 0.5, color: Color(0xFFEEEEEE)),
                                                const SizedBox(height: 8),
                                                // Row 2: Date and Action buttons
                                                Row(
                                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                  children: [
                                                    if (date.isNotEmpty)
                                                      Row(
                                                        mainAxisSize: MainAxisSize.min,
                                                        children: [
                                                          const Icon(Icons.calendar_today_outlined, size: 12, color: AppColors.textLight),
                                                          const SizedBox(width: 4),
                                                          Text(
                                                            'تاريخ: $date',
                                                            style: GoogleFonts.cairo(
                                                              fontSize: 11,
                                                              color: AppColors.textSecondary,
                                                            ),
                                                          ),
                                                        ],
                                                      )
                                                    else
                                                      const SizedBox.shrink(),
                                                    if (receiptUrl != null && receiptUrl.isNotEmpty)
                                                      Row(
                                                        mainAxisSize: MainAxisSize.min,
                                                        children: [
                                                          InkWell(
                                                            onTap: () => _openPdfReceipt(context, receiptUrl, receiptNo, amount),
                                                            borderRadius: BorderRadius.circular(6),
                                                            child: Container(
                                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                              decoration: BoxDecoration(
                                                                color: AppColors.primary.withAlpha(15),
                                                                borderRadius: BorderRadius.circular(6),
                                                              ),
                                                              child: Row(
                                                                mainAxisSize: MainAxisSize.min,
                                                                children: [
                                                                  const Icon(Icons.visibility_rounded, color: AppColors.primary, size: 14),
                                                                  const SizedBox(width: 4),
                                                                  Text(
                                                                    'معاينة',
                                                                    style: GoogleFonts.cairo(
                                                                      fontSize: 11,
                                                                      color: AppColors.primary,
                                                                      fontWeight: FontWeight.bold,
                                                                    ),
                                                                  ),
                                                                ],
                                                              ),
                                                            ),
                                                          ),
                                                          const SizedBox(width: 6),
                                                          InkWell(
                                                            onTap: () => _downloadDirectReceipt(context, receiptUrl, receiptNo),
                                                            borderRadius: BorderRadius.circular(6),
                                                            child: Container(
                                                              padding: const EdgeInsets.all(5),
                                                              decoration: BoxDecoration(
                                                                color: AppColors.background,
                                                                border: Border.all(color: AppColors.border),
                                                                borderRadius: BorderRadius.circular(6),
                                                              ),
                                                              child: const Icon(Icons.download_rounded, color: AppColors.textSecondary, size: 15),
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                  ],
                                                ),
                                              ],
                                            ],
                                          ),
                                        );
                                      },
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
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
                            final confirm = await ConfirmActionDialog.show(
                              context,
                              title: 'تأكيد حذف الشقة نهائياً',
                              message: 'هل أنت متأكد من رغبتك في حذف الشقة ${apartment.unitNumber}؟\n\n'
                                  '⚠️ تنبيه: سيتم مباشرة حذف حساب الساكن المرتبط بها، بالإضافة إلى كافة إيصالاتها ومستحقاتها المالية.',
                              confirmText: 'تأكيد الحذف النهائي',
                              type: ConfirmDialogType.danger,
                              requiresPassword: true,
                              passwordHint: 'لحماية بيانات الإقامة، يرجى إدخال كلمة مرور حسابك لتأكيد الحذف',
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
                  // Dedicated Exemption Button (Admin can exempt or cancel exemption)
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: apartment.isBillable ? Colors.orange.shade900 : AppColors.success,
                      side: BorderSide(
                        color: apartment.isBillable ? Colors.orange.shade400 : AppColors.success,
                        width: 1.2,
                      ),
                      backgroundColor: apartment.isBillable
                          ? Colors.orange.shade50.withAlpha(120)
                          : AppColors.success.withAlpha(20),
                      minimumSize: const Size(double.infinity, 42),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => _toggleExemption(context),
                    icon: Icon(
                      apartment.isBillable ? Icons.money_off_rounded : Icons.restore_rounded,
                      size: 18,
                      color: apartment.isBillable ? Colors.orange.shade900 : AppColors.success,
                    ),
                    label: Text(
                      apartment.isBillable
                          ? 'إعفاء الشقة من واجبات السانديك'
                          : 'إلغاء الإعفاء وإعادة الفوترة',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                        color: apartment.isBillable ? Colors.orange.shade900 : AppColors.success,
                      ),
                    ),
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
                        builder: (_) => RecordPaymentDialog(initialApartmentId: apartment.id),
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
