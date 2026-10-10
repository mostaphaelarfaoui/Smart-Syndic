import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';

class ResidentTransparencyTab extends StatelessWidget {
  const ResidentTransparencyTab({super.key});

  @override
  Widget build(BuildContext context) {
    final resident = context.watch<ResidentProvider>();
    final data = resident.transparencyData;

    final isEnabled = data?['transparency_enabled'] == true;
    final totalInflow = (data?['total_inflow'] as num?)?.toDouble() ?? 0.0;
    final totalOutflow = (data?['total_outflow'] as num?)?.toDouble() ?? 0.0;
    final balance = (data?['balance'] as num?)?.toDouble() ?? 0.0;
    final expensesList = data?['expenses'] != null ? List<Map<String, dynamic>>.from(data!['expenses']) : <Map<String, dynamic>>[];

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () async => await resident.fetchTransparencyLedger(),
        child: !isEnabled && data != null
            ? const EmptyStateWidget(
                icon: Icons.lock_outline,
                title: 'ميزة الشفافية المالية غير مفعلة',
                description: 'لم يقم السانديك بتفعيل الشفافية المالية المباشرة لهذه الإقامة حالياً',
              )
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Transparency Info Banner
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.info.withAlpha(20),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.info.withAlpha(50)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.verified_outlined, color: AppColors.info, size: 28),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'دفتر الشفافية المالية المباشرة',
                                style: GoogleFonts.cairo(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              Text(
                                'يحق لجميع الملاك والساكنين الاطلاع على مداخيل ومصاريف العمارة بكل شفافية ومصداقية.',
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
                  const SizedBox(height: 16),

                  // Summary Metric Cards
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('إجمالي المداخيل', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              const SizedBox(height: 4),
                              Text(
                                '${totalInflow.toStringAsFixed(0)} درهم',
                                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.success),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('إجمالي المصاريف', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              const SizedBox(height: 4),
                              Text(
                                '${totalOutflow.toStringAsFixed(0)} درهم',
                                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.danger),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('الرصيد الصافي', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              const SizedBox(height: 4),
                              Text(
                                '${balance.toStringAsFixed(0)} درهم',
                                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primary),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Section Title
                  Text(
                    'تفاصيل مصاريف ونفقات الإقامة',
                    style: GoogleFonts.cairo(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Expenses Breakdown
                  if (expensesList.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 24),
                      child: Center(
                        child: Text('لا توجد مصاريف مسجلة لهذا الشهر'),
                      ),
                    )
                  else
                    ...expensesList.map((exp) {
                      final amount = (exp['amount'] as num?)?.toDouble() ?? 0.0;
                      final title = exp['title'] ?? '';
                      final cat = exp['category'] ?? '';
                      final date = exp['expense_date'] ?? '';

                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: AppColors.danger.withAlpha(20),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(Icons.outbox, color: AppColors.danger, size: 20),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    title,
                                    style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold),
                                  ),
                                  Text(
                                    '$date • $cat',
                                    style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                                  ),
                                ],
                              ),
                            ),
                            Text(
                              '-${amount.toStringAsFixed(0)} درهم',
                              style: GoogleFonts.cairo(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: AppColors.danger,
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                ],
              ),
      ),
    );
  }
}
