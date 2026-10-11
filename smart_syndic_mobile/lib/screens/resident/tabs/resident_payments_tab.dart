import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';

class ResidentPaymentsTab extends StatelessWidget {
  const ResidentPaymentsTab({super.key});

  Future<void> _viewReceipt(BuildContext context, String? receiptUrl) async {
    if (receiptUrl == null || receiptUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    final resolved = ApiService().resolveMediaUrl(receiptUrl);
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
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: payments.length + 1,
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
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
                              Row(
                                children: [
                                  const Icon(Icons.receipt, size: 18, color: AppColors.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'إجمالي الوصولات: ${payments.length}',
                                    style: GoogleFonts.cairo(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
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
                      ],
                    );
                  }

                  final payment = payments[index - 1];

                  return Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
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
                            Row(
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
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Text(
                                          payment.receiptNumber,
                                          style: GoogleFonts.cairo(
                                            fontSize: 14,
                                            fontWeight: FontWeight.bold,
                                            color: AppColors.textPrimary,
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.copy, size: 14, color: AppColors.textLight),
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(),
                                          tooltip: 'نسخ رقم الوصل',
                                          onPressed: () => _copyReceiptNumber(context, payment.receiptNumber),
                                        ),
                                      ],
                                    ),
                                    Text(
                                      'تاريخ الأداء: ${payment.paymentDate}',
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.success.withAlpha(15),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '${payment.amountPaid.toStringAsFixed(0)} درهم',
                                style: GoogleFonts.cairo(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.success,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),

                        // Tags row: Apartment unit, payment method
                        Row(
                          children: [
                            if (payment.apartmentUnit != null) ...[
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
                              const SizedBox(width: 8),
                            ],
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
                            if (payment.chequeReference != null) ...[
                              const SizedBox(width: 8),
                              Text(
                                'مرجع: ${payment.chequeReference}',
                                style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                              ),
                            ],
                          ],
                        ),

                        // Covered Months
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
                                  'شهر $m',
                                  style: GoogleFonts.cairo(fontSize: 11, color: AppColors.primaryDark),
                                ),
                              );
                            }).toList(),
                          ),
                        ],

                        const Divider(height: 24),

                        // Actions: View/Download + Share
                        Row(
                          children: [
                            Expanded(
                              flex: 3,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.primary,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  elevation: 0,
                                ),
                                onPressed: () => _viewReceipt(context, payment.receiptUrl),
                                icon: const Icon(Icons.download_rounded, size: 18),
                                label: Text(
                                  'تحميل ومعاينة الوصل',
                                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              flex: 2,
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: const Color(0xFF25D366),
                                  side: const BorderSide(color: Color(0xFF25D366)),
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: () => _shareReceipt(
                                  context,
                                  payment.receiptNumber,
                                  payment.amountPaid,
                                  payment.receiptUrl,
                                ),
                                icon: const Icon(Icons.share, size: 16, color: Color(0xFF25D366)),
                                label: Text(
                                  'مشاركة',
                                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
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
