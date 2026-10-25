import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../models/payment_model.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';
import '../../common/receipt_viewer_screen.dart';

class ResidentPaymentsTab extends StatelessWidget {
  const ResidentPaymentsTab({super.key});

  void _viewReceipt(BuildContext context, PaymentModel payment) {
    if (payment.receiptUrl == null || payment.receiptUrl!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    ReceiptViewerScreen.open(
      context,
      receiptUrl: payment.receiptUrl!,
      receiptNumber: payment.receiptNumber,
      amountPaid: payment.amountPaid,
    );
  }

  void _downloadReceipt(BuildContext context, PaymentModel payment) {
    if (payment.receiptUrl == null || payment.receiptUrl!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    ReceiptViewerScreen.downloadDirectly(
      context,
      receiptUrl: payment.receiptUrl!,
      receiptNumber: payment.receiptNumber,
    );
  }

  Future<void> _shareReceipt(BuildContext context, String receiptNumber, double amount, String? receiptUrl) async {
    final resolvedUrl = receiptUrl != null ? ApiService().resolveMediaUrl(receiptUrl) : '';
    final linkText = resolvedUrl.isNotEmpty ? '\nرابط تحميل الوصل:\n$resolvedUrl\n' : '';

    final message = Uri.encodeComponent(
      'وصل أداء واجب السانديك الرسمي:\n'
      'رقم الوصل: $receiptNumber\n'
      'المبلغ: ${amount.toStringAsFixed(0)} درهم\n'
      '$linkText'
      'تطبيق Smart Syndic 🏢',
    );

    final uri = Uri.parse('https://wa.me/?text=$message');
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  void _copyReceiptNumber(BuildContext context, String receiptNumber) {
    Clipboard.setData(ClipboardData(text: receiptNumber));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('تم نسخ رقم الوصل بنجاح ✓'),
        backgroundColor: AppColors.success,
        duration: Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final resident = context.watch<ResidentProvider>();
    final payments = resident.myPayments;
    final totalAmount = payments.fold<double>(0.0, (sum, p) => sum + p.amountPaid);

    final Map<String, List<PaymentModel>> grouped = {};
    for (final p in payments) {
      String year = 'أخرى';
      if (p.paymentDate.length >= 4) {
        year = p.paymentDate.substring(0, 4);
      } else if (p.coveredMonths.isNotEmpty && p.coveredMonths.first.length >= 4) {
        year = p.coveredMonths.first.substring(0, 4);
      }
      grouped.putIfAbsent(year, () => []).add(p);
    }
    final sortedYears = grouped.keys.toList()..sort((a, b) => b.compareTo(a));

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () async {
          await resident.fetchMyPayments();
          await resident.fetchMyBalance();
        },
        child: payments.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  _buildPrivacyBanner(),
                  const SizedBox(height: 40),
                  const EmptyStateWidget(
                    icon: Icons.receipt_long_outlined,
                    title: 'لا توجد إيصالات سابقة مسجلة لشقتكم',
                    description: 'عند أداء واجب السانديك، سيتم إيداع الوصل الرسمي المعتمد هنا فوراً لتحميله والاحتفاظ به بكل خصوصية.',
                  ),
                ],
              )
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildPrivacyBanner(),
                  const SizedBox(height: 14),
                  // Quick stats summary
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Row(
                            children: [
                              const Icon(Icons.receipt, size: 18, color: AppColors.primary),
                              const SizedBox(width: 8),
                              Flexible(
                                child: Text(
                                  'إجمالي الوصولات: ${payments.length}',
                                  style: GoogleFonts.cairo(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.textPrimary,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'المجموع: ${totalAmount.toStringAsFixed(0)} درهم',
                          style: GoogleFonts.cairo(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: AppColors.success,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Classified by year
                  for (final year in sortedYears) ...[
                    Builder(
                      builder: (context) {
                        final yearPayments = grouped[year]!;
                        final yearTotal = yearPayments.fold<double>(0.0, (sum, p) => sum + p.amountPaid);

                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Container(
                              margin: const EdgeInsets.only(top: 4, bottom: 12),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              decoration: BoxDecoration(
                                color: AppColors.primary.withAlpha(12),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: AppColors.primary.withAlpha(30)),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      const Icon(Icons.calendar_month_rounded, size: 16, color: AppColors.primary),
                                      const SizedBox(width: 6),
                                      Text(
                                        'سنة $year',
                                        style: GoogleFonts.cairo(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13,
                                          color: AppColors.primaryDark,
                                        ),
                                      ),
                                      const SizedBox(width: 8),
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
                                      fontSize: 12.5,
                                      color: AppColors.success,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            for (final payment in yearPayments) _buildPaymentCard(context, payment),
                          ],
                        );
                      },
                    ),
                  ],
                ],
              ),
      ),
    );
  }

  Widget _buildPaymentCard(BuildContext context, PaymentModel payment) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(6),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: AppColors.success.withAlpha(20),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.check_circle_rounded, color: AppColors.success, size: 20),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  payment.receiptNumber,
                                  style: GoogleFonts.cairo(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.textPrimary,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const SizedBox(width: 4),
                              InkWell(
                                borderRadius: BorderRadius.circular(4),
                                onTap: () => _copyReceiptNumber(context, payment.receiptNumber),
                                child: const Padding(
                                  padding: EdgeInsets.all(2.0),
                                  child: Icon(Icons.copy, size: 14, color: AppColors.textLight),
                                ),
                              ),
                            ],
                          ),
                          Text(
                            'تاريخ الأداء: ${payment.paymentDate}',
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.success.withAlpha(15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${payment.amountPaid.toStringAsFixed(0)} درهم',
                  style: GoogleFonts.cairo(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: AppColors.success,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Tags row: Apartment unit, payment method, covered months
          Wrap(
            spacing: 8,
            runSpacing: 6,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              if (payment.apartmentUnit != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    'شقة ${payment.apartmentUnit}',
                    style: GoogleFonts.cairo(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primaryDark,
                    ),
                  ),
                ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
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
              if (payment.coveredMonths.isNotEmpty)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.success.withAlpha(15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    payment.coveredMonths.length == 1
                        ? 'شهر ${payment.coveredMonths.first}'
                        : 'أشهر: ${payment.coveredMonths.join(", ")}',
                    style: GoogleFonts.cairo(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: AppColors.success,
                    ),
                  ),
                ),
              if (payment.chequeReference != null)
                Text(
                  'مرجع: ${payment.chequeReference}',
                  style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                ),
            ],
          ),

          const Divider(height: 24),

          // Actions: Preview + Direct Download + WhatsApp Share
          Row(
            children: [
              // In-App Preview
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 4),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    elevation: 0,
                  ),
                  onPressed: () => _viewReceipt(context, payment),
                  icon: const Icon(Icons.visibility_rounded, size: 16),
                  label: Text(
                    'معاينة الوصل',
                    style: GoogleFonts.cairo(fontSize: 11.5, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(width: 8),

              // Direct Download
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: const BorderSide(color: AppColors.primary),
                    padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 4),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () => _downloadReceipt(context, payment),
                  icon: const Icon(Icons.download_rounded, size: 16),
                  label: Text(
                    'تحميل مباشر',
                    style: GoogleFonts.cairo(fontSize: 11.5, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(width: 8),

              // WhatsApp Share
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFF25D366).withAlpha(20),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFF25D366).withAlpha(60)),
                ),
                child: IconButton(
                  tooltip: 'مشاركة عبر واتساب',
                  padding: const EdgeInsets.all(8),
                  constraints: const BoxConstraints(),
                  icon: const Icon(Icons.chat, size: 18, color: Color(0xFF25D366)),
                  onPressed: () => _shareReceipt(
                    context,
                    payment.receiptNumber,
                    payment.amountPaid,
                    payment.receiptUrl,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPrivacyBanner() {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.primary.withAlpha(15),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.primary.withAlpha(35)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.primary.withAlpha(25),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.shield_outlined, color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'إيصالات الأداء الخاصة بشقتكم 🔒',
                  style: GoogleFonts.cairo(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                    color: AppColors.primaryDark,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'جميع وصولات الأداء خاصة ومحمية بسرية تامة، ولا يمكن لأي شقة أخرى في الإقامة الاطلاع عليها.',
                  style: GoogleFonts.cairo(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
