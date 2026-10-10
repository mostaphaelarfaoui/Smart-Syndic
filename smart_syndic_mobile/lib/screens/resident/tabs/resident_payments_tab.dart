import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';

class ResidentPaymentsTab extends StatelessWidget {
  const ResidentPaymentsTab({super.key});

  @override
  Widget build(BuildContext context) {
    final resident = context.watch<ResidentProvider>();
    final payments = resident.myPayments;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () async => await resident.fetchMyPayments(),
        child: payments.isEmpty
            ? const EmptyStateWidget(
                icon: Icons.receipt_long_outlined,
                title: 'لا توجد إيصالات سابقة مسجلة',
                description: 'عند أداء واجب السانديك، سيظهر هنا وصل الأداء الرسمي فورا بصيغة PDF',
              )
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: payments.length,
                itemBuilder: (context, index) {
                  final payment = payments[index];

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
                                  child: const Icon(Icons.check_circle, color: AppColors.success, size: 20),
                                ),
                                const SizedBox(width: 10),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'وصل أداء رقم ${payment.receiptNumber}',
                                      style: GoogleFonts.cairo(
                                        fontSize: 14,
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.textPrimary,
                                      ),
                                    ),
                                    Text(
                                      payment.paymentDate,
                                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                                    ),
                                  ],
                                ),
                              ],
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
                        const SizedBox(height: 12),

                        Row(
                          children: [
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
                                'شيك: ${payment.chequeReference}',
                                style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                              ),
                            ],
                          ],
                        ),

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

                        const Divider(height: 20),

                        // Action: View/Download PDF
                        if (payment.receiptUrl != null)
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              minimumSize: const Size(double.infinity, 38),
                            ),
                            onPressed: () async {
                              final uri = Uri.parse(payment.receiptUrl!);
                              if (await canLaunchUrl(uri)) {
                                await launchUrl(uri);
                              }
                            },
                            icon: const Icon(Icons.picture_as_pdf, size: 16),
                            label: Text(
                              'معاينة وتحميل الوصل الرسمي (PDF)',
                              style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                            ),
                          )
                        else
                          Center(
                            child: Text(
                              'تم التحصيل والتسجيل في الحساب ✓',
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.success),
                            ),
                          ),
                      ],
                    ),
                  );
                },
              ),
      ),
    );
  }
}
