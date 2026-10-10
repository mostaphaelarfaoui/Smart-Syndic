import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/stat_card.dart';
import '../dialogs/record_payment_dialog.dart';
import '../dialogs/record_expense_dialog.dart';
import '../dialogs/generate_report_dialog.dart';

class SyndicDashboardTab extends StatelessWidget {
  final Function(int) onNavigateTab;

  const SyndicDashboardTab({super.key, required this.onNavigateTab});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final syndic = context.watch<SyndicProvider>();
    final kpis = syndic.kpis;

    return RefreshIndicator(
      onRefresh: () async {
        await syndic.fetchDashboardKpis();
        await syndic.fetchApartments();
        await syndic.fetchPendingResidents();
      },
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        children: [
          // Header Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primaryDark, AppColors.primary],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withAlpha(50),
                  blurRadius: 12,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        'مرحباً، ${auth.user?.fullName ?? "السيد السانديك"} 👋',
                        style: GoogleFonts.cairo(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withAlpha(40),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        auth.tenant?.name ?? 'إقامة الفردوس',
                        style: GoogleFonts.cairo(
                          fontSize: 12,
                          color: Colors.white,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'لوحة تحكم وتسيير الشؤون المالية والقانونية للإقامة',
                  style: GoogleFonts.cairo(
                    fontSize: 13,
                    color: Colors.white.withAlpha(200),
                  ),
                ),
                const SizedBox(height: 16),
                // Quick actions
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: AppColors.primaryDark,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () {
                          showDialog(
                            context: context,
                            builder: (_) => const RecordPaymentDialog(),
                          );
                        },
                        icon: const Icon(Icons.add_circle_outline, size: 18),
                        label: Text(
                          'استخلاص واجب',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white.withAlpha(40),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () {
                          showDialog(
                            context: context,
                            builder: (_) => const RecordExpenseDialog(),
                          );
                        },
                        icon: const Icon(Icons.remove_circle_outline, size: 18),
                        label: Text(
                          'تسجيل مصروف',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Alerts if pending residents
          if ((kpis?.pendingResidents ?? 0) > 0)
            InkWell(
              onTap: () => onNavigateTab(3), // Community & Residents tab
              child: Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.warning.withAlpha(25),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.warning),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.person_add_alt_1, color: AppColors.warning, size: 22),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'هناك ${kpis!.pendingResidents} طلبات انضمام جديدة من السكان تنتظر الموافقة',
                        style: GoogleFonts.cairo(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.warning),
                  ],
                ),
              ),
            ),

          // Metric Cards Grid
          GridView.count(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.25,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            children: [
              StatCard(
                title: 'مداخيل الشهر',
                value: '${(kpis?.monthRevenue ?? 0).toStringAsFixed(0)} درهم',
                subtitle: 'نسبة الاستخلاص: ${(kpis?.collectionRate ?? 0).toStringAsFixed(0)}%',
                icon: Icons.trending_up,
                iconColor: AppColors.success,
                onTap: () => onNavigateTab(1), // Payments tab
              ),
              StatCard(
                title: 'مصاريف الشهر',
                value: '${(kpis?.monthExpenses ?? 0).toStringAsFixed(0)} درهم',
                subtitle: 'فواتير وصيانة وعمال',
                icon: Icons.trending_down,
                iconColor: AppColors.danger,
                onTap: () => onNavigateTab(2), // Expenses tab
              ),
              StatCard(
                title: 'الرصيد الصافي المتبقي',
                value: '${(kpis?.netBalance ?? 0).toStringAsFixed(0)} درهم',
                subtitle: 'في صندوق الإقامة',
                icon: Icons.account_balance,
                iconColor: AppColors.primary,
              ),
              StatCard(
                title: 'مجموع المتأخرات',
                value: '${(kpis?.totalArrears ?? 0).toStringAsFixed(0)} درهم',
                subtitle: 'واجبات غير مسددة',
                icon: Icons.warning_amber_rounded,
                iconColor: AppColors.warning,
                onTap: () => onNavigateTab(1),
              ),
            ],
          ),
          // Official Assembly Report Banner
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.primary.withAlpha(20),
                  AppColors.primary.withAlpha(8),
                ],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.primary.withAlpha(60)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.picture_as_pdf, color: Colors.white, size: 24),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'التقرير المالي للجمع العام',
                        style: GoogleFonts.cairo(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        'كشف الحسابات والميزانية (القانون 18-00)',
                        style: GoogleFonts.cairo(
                          fontSize: 11,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () {
                    showDialog(
                      context: context,
                      builder: (_) => const GenerateReportDialog(),
                    );
                  },
                  child: Text(
                    'توليد',
                    style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Invitation Code Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.primary.withAlpha(50)),
              boxShadow: [
                BoxShadow(color: AppColors.primary.withAlpha(10), blurRadius: 10, offset: const Offset(0, 4)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.qr_code_2, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Text(
                      'رمز دعوة السكان للتطبيق',
                      style: GoogleFonts.cairo(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Text(
                          syndic.invitationCode ?? 'جارِ التحميل...',
                          textAlign: TextAlign.center,
                          style: GoogleFonts.cairo(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 2,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      children: [
                        IconButton(
                          onPressed: syndic.invitationCode == null
                              ? null
                              : () {
                                  Clipboard.setData(ClipboardData(text: syndic.invitationCode!));
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text('تم نسخ رمز الدعوة', style: GoogleFonts.cairo())),
                                  );
                                },
                          icon: const Icon(Icons.copy, color: AppColors.primary),
                          tooltip: 'نسخ الرمز',
                        ),
                        IconButton(
                          onPressed: () async {
                            final confirm = await showDialog<bool>(
                              context: context,
                              builder: (ctx) => AlertDialog(
                                title: Text('تجديد رمز الدعوة', style: GoogleFonts.cairo()),
                                content: Text(
                                  'تجديد الرمز سيجعل الرمز القديم غير صالح. هل أنت متأكد؟',
                                  style: GoogleFonts.cairo(),
                                ),
                                actions: [
                                  TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
                                  TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('تجديد')),
                                ],
                              ),
                            );
                            if (confirm == true) {
                              await syndic.regenerateInvitationCode();
                            }
                          },
                          icon: const Icon(Icons.refresh, color: AppColors.warning),
                          tooltip: 'تجديد الرمز',
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'أرسل هذا الرمز للملاك والسكان الجدد لتمكينهم من إنشاء حساب والانضمام لإقامتك.',
                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Collection rate progress card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'نسبة استخلاص هذا الشهر',
                      style: GoogleFonts.cairo(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    Text(
                      '${(kpis?.collectionRate ?? 0).toStringAsFixed(1)}%',
                      style: GoogleFonts.cairo(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: AppColors.primary,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: LinearProgressIndicator(
                    value: ((kpis?.collectionRate ?? 0) / 100).clamp(0.0, 1.0),
                    minHeight: 10,
                    backgroundColor: AppColors.border,
                    valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'إجمالي الشقق: ${kpis?.totalApartments ?? 0} شقة',
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Chart Section
          if (kpis != null && kpis.chartData.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          'حركة المداخيل والمصاريف (آخر الأشهر)',
                          style: GoogleFonts.cairo(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      Row(
                        children: [
                          Container(width: 8, height: 8, color: AppColors.success),
                          const SizedBox(width: 4),
                          Text('مداخيل', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                          const SizedBox(width: 8),
                          Container(width: 8, height: 8, color: AppColors.danger),
                          const SizedBox(width: 4),
                          Text('مصاريف', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    height: 180,
                    child: BarChart(
                      BarChartData(
                        alignment: BarChartAlignment.spaceAround,
                        barTouchData: BarTouchData(enabled: true),
                        titlesData: FlTitlesData(
                          show: true,
                          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          bottomTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (val, meta) {
                                final idx = val.toInt();
                                if (idx >= 0 && idx < kpis.chartData.length) {
                                  return Padding(
                                    padding: const EdgeInsets.only(top: 6),
                                    child: Text(
                                      kpis.chartData[idx].month,
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  );
                                }
                                return const SizedBox();
                              },
                            ),
                          ),
                        ),
                        borderData: FlBorderData(show: false),
                        gridData: const FlGridData(show: false),
                        barGroups: List.generate(kpis.chartData.length, (idx) {
                          final item = kpis.chartData[idx];
                          return BarChartGroupData(
                            x: idx,
                            barRods: [
                              BarChartRodData(
                                toY: item.inflow,
                                color: AppColors.success,
                                width: 12,
                                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                              ),
                              BarChartRodData(
                                toY: item.outflow,
                                color: AppColors.danger,
                                width: 12,
                                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                              ),
                            ],
                          );
                        }),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}
