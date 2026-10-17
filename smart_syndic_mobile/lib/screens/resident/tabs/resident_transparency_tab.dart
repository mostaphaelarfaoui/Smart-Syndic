import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/number_utils.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';

class ResidentTransparencyTab extends StatefulWidget {
  const ResidentTransparencyTab({super.key});

  @override
  State<ResidentTransparencyTab> createState() => _ResidentTransparencyTabState();
}

class _ResidentTransparencyTabState extends State<ResidentTransparencyTab> {
  int _selectedView = 0; // 0 = Expenses, 1 = Apartments Collection Status

  @override
  Widget build(BuildContext context) {
    final resident = context.watch<ResidentProvider>();
    final data = resident.transparencyData;

    // Loading indicator when no data yet
    if (resident.isLoading && data == null) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(
          child: CircularProgressIndicator(color: AppColors.primary),
        ),
      );
    }

    final isEnabled = data?['transparency_enabled'] == true || data?['enabled'] == true;
    final totalInflow = parseDouble(data?['total_inflow']);
    final totalOutflow = parseDouble(data?['total_outflow']);
    final balance = parseDouble(data?['balance']);
    final collectionRate = parseDouble(data?['collection_rate']);
    final paidCount = data?['paid_apartments_count'] ?? 0;
    final totalCount = data?['total_apartments_count'] ?? 0;

    final expensesList = data?['expenses'] != null
        ? List<Map<String, dynamic>>.from(data!['expenses'])
        : <Map<String, dynamic>>[];

    final collectionList = data?['collection_status'] != null
        ? List<Map<String, dynamic>>.from(data!['collection_status'])
        : <Map<String, dynamic>>[];

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
                                'يحق لجميع الملاك والساكنين الاطلاع على مداخيل ومصاريف الإقامة بكل شفافية ومصداقية.',
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
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('المداخيل', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              const SizedBox(height: 4),
                              Text(
                                '${totalInflow.toStringAsFixed(0)} د.م',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.success),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('المصاريف', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              const SizedBox(height: 4),
                              Text(
                                '${totalOutflow.toStringAsFixed(0)} د.م',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.danger),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(12),
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
                                '${balance.toStringAsFixed(0)} د.م',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.primary),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Collection Progress Card
                  if (totalCount > 0)
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'نسبة استخلاص مساهمات الشهر',
                                style: GoogleFonts.cairo(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              Text(
                                '${collectionRate.toStringAsFixed(1)}% ($paidCount من $totalCount شقة)',
                                style: GoogleFonts.cairo(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.primary,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: LinearProgressIndicator(
                              value: (collectionRate / 100).clamp(0.0, 1.0),
                              minHeight: 8,
                              backgroundColor: AppColors.border,
                              color: AppColors.primary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 16),

                  // Segmented View Toggle
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.grey.shade200,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.all(4),
                    child: Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedView = 0),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              decoration: BoxDecoration(
                                color: _selectedView == 0 ? Colors.white : Colors.transparent,
                                borderRadius: BorderRadius.circular(10),
                                boxShadow: _selectedView == 0
                                    ? [BoxShadow(color: Colors.black.withAlpha(15), blurRadius: 4, offset: const Offset(0, 1))]
                                    : null,
                              ),
                              child: Center(
                                child: Text(
                                  'نفقات ومصاريف الإقامة (${expensesList.length})',
                                  style: GoogleFonts.cairo(
                                    fontSize: 12,
                                    fontWeight: _selectedView == 0 ? FontWeight.bold : FontWeight.normal,
                                    color: _selectedView == 0 ? AppColors.primary : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedView = 1),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              decoration: BoxDecoration(
                                color: _selectedView == 1 ? Colors.white : Colors.transparent,
                                borderRadius: BorderRadius.circular(10),
                                boxShadow: _selectedView == 1
                                    ? [BoxShadow(color: Colors.black.withAlpha(15), blurRadius: 4, offset: const Offset(0, 1))]
                                    : null,
                              ),
                              child: Center(
                                child: Text(
                                  'وضعية أداء الشقق (${collectionList.length})',
                                  style: GoogleFonts.cairo(
                                    fontSize: 12,
                                    fontWeight: _selectedView == 1 ? FontWeight.bold : FontWeight.normal,
                                    color: _selectedView == 1 ? AppColors.primary : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Content based on selected toggle
                  if (_selectedView == 0) ...[
                    // Expenses Section
                    if (expensesList.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 28),
                        child: Center(
                          child: Text('لا توجد مصاريف مسجلة حالياً'),
                        ),
                      )
                    else
                      ...expensesList.map((exp) {
                        final amount = parseDouble(exp['amount']);
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
                  ] else ...[
                    // Apartments Collection Status Section
                    if (collectionList.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 28),
                        child: Center(
                          child: Text('لا توجد شقق مسجلة في الإقامة'),
                        ),
                      )
                    else
                      ...collectionList.map((apt) {
                        final unit = apt['unit'] ?? '';
                        final building = apt['building'] ?? '';
                        final floor = apt['floor'] ?? '';
                        final status = apt['status'] ?? 'OUTSTANDING';
                        final isBillable = apt['is_billable'] != false;
                        final isPaid = status == 'PAID';

                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
                                  color: !isBillable
                                      ? Colors.grey.withAlpha(30)
                                      : isPaid
                                          ? AppColors.success.withAlpha(25)
                                          : AppColors.warning.withAlpha(25),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Icon(
                                  !isBillable
                                      ? Icons.block
                                      : isPaid
                                          ? Icons.check_circle_outline
                                          : Icons.schedule,
                                  color: !isBillable
                                      ? Colors.grey
                                      : isPaid
                                          ? AppColors.success
                                          : AppColors.warning,
                                  size: 18,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      unit,
                                      style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold),
                                    ),
                                    Text(
                                      '$building • الطابق $floor',
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: !isBillable
                                      ? Colors.grey.shade100
                                      : isPaid
                                          ? AppColors.success.withAlpha(20)
                                          : AppColors.warning.withAlpha(20),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(
                                    color: !isBillable
                                        ? Colors.grey.shade300
                                        : isPaid
                                            ? AppColors.success.withAlpha(60)
                                            : AppColors.warning.withAlpha(60),
                                  ),
                                ),
                                child: Text(
                                  !isBillable
                                      ? 'معفية (شاغرة)'
                                      : isPaid
                                          ? 'تم الأداء ✓'
                                          : 'غير مؤداة ⏳',
                                  style: GoogleFonts.cairo(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: !isBillable
                                        ? Colors.grey.shade600
                                        : isPaid
                                            ? AppColors.success
                                            : AppColors.warning,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                  ],
                ],
              ),
      ),
    );
  }
}
