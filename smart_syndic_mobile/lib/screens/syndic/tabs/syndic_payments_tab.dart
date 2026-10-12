import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../models/payment_model.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/record_payment_dialog.dart';

class SyndicPaymentsTab extends StatefulWidget {
  const SyndicPaymentsTab({super.key});

  @override
  State<SyndicPaymentsTab> createState() => _SyndicPaymentsTabState();
}

class _SyndicPaymentsTabState extends State<SyndicPaymentsTab> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _confirmPaymentDialog(PaymentModel payment) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.check_circle_outline, color: AppColors.primary),
            const SizedBox(width: 8),
            Text('تأكيد الوصل', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'هل تريد بالتأكيد تأكيد هذا الوصل؟',
              style: GoogleFonts.cairo(fontWeight: FontWeight.w600, fontSize: 14),
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.amber.shade200),
              ),
              child: Text(
                'تنبيه: بعد التأكيد، سيتم حفظ نسخة رقابية لدى الإدارة العامة (SuperAdmin) ولن تتمكن من تعديل أو حذف هذا الوصل.',
                style: GoogleFonts.cairo(fontSize: 12, color: Colors.brown.shade900),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('تأكيد الآن', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<SyndicProvider>().confirmPayment(payment.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: success ? AppColors.success : AppColors.danger,
            content: Text(
              success ? 'تم تأكيد الوصل بنجاح ووثق في الأرشيف الرقابي ✓' : 'حدث خطأ أثناء تأكيد الوصل',
              style: GoogleFonts.cairo(),
            ),
          ),
        );
      }
    }
  }

  Future<void> _sendPaymentDialog(PaymentModel payment) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.send_rounded, color: Color(0xFF0D9488)),
            const SizedBox(width: 8),
            Text('إرسال الوصل للساكن', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Text(
          'سيتم إرسال هذا الوصل إلى حساب الساكن وتحديث ذمته المالية في تطبيقه.',
          style: GoogleFonts.cairo(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0D9488),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('إرسال للساكن', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<SyndicProvider>().sendPaymentToResident(payment.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: success ? AppColors.success : AppColors.danger,
            content: Text(
              success ? 'تم إرسال الوصل للساكن بنجاح ✓' : 'حدث خطأ أثناء إرسال الوصل',
              style: GoogleFonts.cairo(),
            ),
          ),
        );
      }
    }
  }

  Future<void> _deletePaymentDialog(PaymentModel payment) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.delete_forever, color: Colors.red),
            const SizedBox(width: 8),
            Text('حذف مسودة الوصل', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.red)),
          ],
        ),
        content: Text(
          'هل تريد حذف هذه المسودة نهائياً؟',
          style: GoogleFonts.cairo(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('حذف', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<SyndicProvider>().deletePayment(payment.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: success ? AppColors.success : AppColors.danger,
            content: Text(
              success ? 'تم حذف مسودة الوصل بنجاح' : 'لا يمكن حذف هذا الوصل',
              style: GoogleFonts.cairo(),
            ),
          ),
        );
      }
    }
  }

  Future<void> _shareViaWhatsApp(String? phone, String receiptNumber, double amount, String unit, {String? receiptUrl}) async {
    final cleanPhone = (phone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    final resolvedUrl = receiptUrl != null ? ApiService().resolveMediaUrl(receiptUrl) : '';
    final linkText = resolvedUrl.isNotEmpty ? '\nرابط تحميل الوصل:\n$resolvedUrl\n' : '';

    final message = Uri.encodeComponent(
      'السلام عليكم ورحمة الله وبركاته،\n'
      'نؤكد لكم استلام واجب السانديك للشقة $unit بمبلغ ${amount.toStringAsFixed(0)} درهم.\n'
      'رقم الوصل: $receiptNumber\n'
      '$linkText'
      'شكراً لالتزامكم وحسن تعاونكم 🙏\n'
      'إدارة السانديك',
    );

    Uri uri;
    if (cleanPhone.isNotEmpty) {
      final phoneFormatted = cleanPhone.startsWith('0') ? '212${cleanPhone.substring(1)}' : cleanPhone;
      uri = Uri.parse('https://wa.me/$phoneFormatted?text=$message');
    } else {
      uri = Uri.parse('https://wa.me/?text=$message');
    }

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final allPayments = syndic.payments;

    final filteredPayments = allPayments.where((p) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      final unitMatch = (p.apartmentUnit ?? '').toLowerCase().contains(q);
      final nameMatch = (p.residentName ?? '').toLowerCase().contains(q);
      final rcptMatch = p.receiptNumber.toLowerCase().contains(q);
      return unitMatch || nameMatch || rcptMatch;
    }).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const RecordPaymentDialog(),
          );
        },
        icon: const Icon(Icons.add),
        label: Text('استخلاص واجب', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Search & Filter Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: (val) => setState(() => _searchQuery = val.trim()),
              decoration: InputDecoration(
                hintText: 'البحث بالشقة، الاسم أو رقم الوصل...',
                prefixIcon: const Icon(Icons.search, color: AppColors.textLight),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _searchQuery = '');
                        },
                      )
                    : null,
                filled: true,
                fillColor: Colors.white,
              ),
            ),
          ),

          // Total count summary
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'عدد الإيصالات: ${filteredPayments.length}',
                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                ),
                Text(
                  'المجموع: ${filteredPayments.fold<double>(0, (sum, p) => sum + p.amountPaid).toStringAsFixed(0)} درهم',
                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                ),
              ],
            ),
          ),

          // Payment List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                await syndic.fetchPayments();
                await syndic.fetchApartments();
              },
              child: filteredPayments.isEmpty
                  ? EmptyStateWidget(
                      icon: Icons.receipt_long_outlined,
                      title: 'لا توجد إيصالات مسجلة حالياً',
                      description: 'اضغط على زر استخلاص واجب بالأسفل لتسجيل دفعة جديدة',
                      buttonText: 'استخلاص واجب جديد',
                      onButtonPressed: () {
                        showDialog(
                          context: context,
                          builder: (_) => const RecordPaymentDialog(),
                        );
                      },
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                      itemCount: filteredPayments.length,
                      itemBuilder: (context, index) {
                        final payment = filteredPayments[index];

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.border),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withAlpha(5),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Top row: Unit, Resident, Status badge & Amount
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: AppColors.primary.withAlpha(20),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Text(
                                            'شقة ${payment.apartmentUnit ?? "-"}',
                                            style: GoogleFonts.cairo(
                                              fontSize: 13,
                                              fontWeight: FontWeight.bold,
                                              color: AppColors.primary,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Flexible(
                                          child: Text(
                                            payment.residentName ?? 'ساكن الإقامة',
                                            overflow: TextOverflow.ellipsis,
                                            style: GoogleFonts.cairo(
                                              fontSize: 14,
                                              fontWeight: FontWeight.w600,
                                              color: AppColors.textPrimary,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        // Status badge
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: payment.isDraft
                                                ? Colors.orange.withAlpha(25)
                                                : (payment.isConfirmed ? Colors.blue.withAlpha(25) : Colors.green.withAlpha(25)),
                                            borderRadius: BorderRadius.circular(6),
                                            border: Border.all(
                                              color: payment.isDraft
                                                  ? Colors.orange
                                                  : (payment.isConfirmed ? Colors.blue : Colors.green),
                                              width: 0.8,
                                            ),
                                          ),
                                          child: Text(
                                            payment.statusArabic,
                                            style: GoogleFonts.cairo(
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold,
                                              color: payment.isDraft
                                                  ? Colors.orange.shade800
                                                  : (payment.isConfirmed ? Colors.blue.shade800 : Colors.green.shade800),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    '${payment.amountPaid.toStringAsFixed(0)} درهم',
                                    style: GoogleFonts.cairo(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.success,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),

                              // Details: Receipt number, date, method
                              Row(
                                children: [
                                  const Icon(Icons.tag, size: 14, color: AppColors.textLight),
                                  const SizedBox(width: 4),
                                  Text(
                                    payment.receiptNumber,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                  const SizedBox(width: 16),
                                  const Icon(Icons.calendar_today, size: 14, color: AppColors.textLight),
                                  const SizedBox(width: 4),
                                  Text(
                                    payment.paymentDate,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                  const SizedBox(width: 16),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppColors.background,
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(color: AppColors.border),
                                    ),
                                    child: Text(
                                      payment.paymentMethodArabic,
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ),
                                ],
                              ),

                              // Covered months tags
                              if (payment.coveredMonths.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 4,
                                  children: payment.coveredMonths.map((m) {
                                    return Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryLight.withAlpha(25),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        m,
                                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.primaryDark),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ],

                              const Divider(height: 20),

                              // Actions row based on payment workflow state
                              if (payment.isDraft) ...[
                                Row(
                                  children: [
                                    Expanded(
                                      child: ElevatedButton.icon(
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: AppColors.primary,
                                          foregroundColor: Colors.white,
                                          padding: const EdgeInsets.symmetric(vertical: 8),
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                          elevation: 0,
                                        ),
                                        onPressed: () => _confirmPaymentDialog(payment),
                                        icon: const Icon(Icons.check_circle, size: 16),
                                        label: Text('تأكيد الوصل', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: Colors.red,
                                        side: const BorderSide(color: Colors.red),
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onPressed: () => _deletePaymentDialog(payment),
                                      icon: const Icon(Icons.delete_outline, size: 16, color: Colors.red),
                                      label: Text('حذف المسودة', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ),
                                  ],
                                ),
                              ] else ...[
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    if (payment.isConfirmed)
                                      ElevatedButton.icon(
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: const Color(0xFF0D9488),
                                          foregroundColor: Colors.white,
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                          elevation: 0,
                                        ),
                                        onPressed: () => _sendPaymentDialog(payment),
                                        icon: const Icon(Icons.send_rounded, size: 15),
                                        label: Text('إرسال للساكن', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                                      )
                                    else
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: Colors.green.shade50,
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.done_all, size: 14, color: AppColors.success),
                                            const SizedBox(width: 4),
                                            Text(
                                              'تم التسليم للساكن',
                                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.success, fontWeight: FontWeight.bold),
                                            ),
                                          ],
                                        ),
                                      ),

                                    Row(
                                      children: [
                                        // WhatsApp Button
                                        IconButton(
                                          tooltip: 'إرسال بالواتساب',
                                          style: IconButton.styleFrom(
                                            backgroundColor: const Color(0xFF25D366).withAlpha(25),
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                          ),
                                          onPressed: () => _shareViaWhatsApp(
                                            payment.residentPhone,
                                            payment.receiptNumber,
                                            payment.amountPaid,
                                            payment.apartmentUnit ?? '',
                                            receiptUrl: payment.receiptUrl,
                                          ),
                                          icon: const Icon(Icons.chat, size: 18, color: Color(0xFF25D366)),
                                        ),
                                        const SizedBox(width: 6),

                                        // Receipt download/view
                                        if (payment.receiptUrl != null && payment.receiptUrl!.isNotEmpty)
                                          ElevatedButton.icon(
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: AppColors.primary,
                                              foregroundColor: Colors.white,
                                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                              elevation: 0,
                                            ),
                                            onPressed: () async {
                                              final resolved = ApiService().resolveMediaUrl(payment.receiptUrl!);
                                              final uri = Uri.parse(resolved);
                                              try {
                                                final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
                                                if (!launched) {
                                                  await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
                                                }
                                              } catch (_) {
                                                try {
                                                  await launchUrl(uri, mode: LaunchMode.platformDefault);
                                                } catch (e) {
                                                  if (context.mounted) {
                                                    ScaffoldMessenger.of(context).showSnackBar(
                                                      SnackBar(content: Text('تعذر فتح الوصل: $e')),
                                                    );
                                                  }
                                                }
                                              }
                                            },
                                            icon: const Icon(Icons.download_rounded, size: 15, color: Colors.white),
                                            label: Text(
                                              'تحميل الوصل',
                                              style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold),
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
            ),
          ),
        ],
      ),
    );
  }
}
