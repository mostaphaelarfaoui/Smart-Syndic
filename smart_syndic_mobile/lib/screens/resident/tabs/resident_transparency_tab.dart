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
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<ResidentProvider>().fetchTransparencyLedger();
      }
    });
  }

  String _formatMonthArabic(String yyyyMm) {
    final parts = yyyyMm.split('-');
    if (parts.length != 2) return yyyyMm;
    const monthNames = {
      '01': 'يناير',
      '02': 'فبراير',
      '03': 'مارس',
      '04': 'أبريل',
      '05': 'مايو',
      '06': 'يونيو',
      '07': 'يوليوز',
      '08': 'غشت',
      '09': 'شتنبر',
      '10': 'أكتوبر',
      '11': 'نونبر',
      '12': 'دجنبر',
    };
    final monthName = monthNames[parts[1]] ?? parts[1];
    return '$monthName ${parts[0]}';
  }

  String _formatUnpaidMonthsSummary(List<String> months) {
    if (months.isEmpty) return '';
    if (months.length == 1) {
      return 'شهر ${_formatMonthArabic(months.first)}';
    } else if (months.length <= 3) {
      return '${months.length} أشهر (${months.map((m) => _formatMonthArabic(m).split(' ').first).join('، ')})';
    } else {
      return '${months.length} أشهر متأخرة';
    }
  }

  void _showApartmentDetails(BuildContext context, Map<String, dynamic> apt) {
    final unit = apt['unit'] ?? '';
    final building = apt['building'] ?? '';
    final isBillable = apt['is_billable'] != false;
    final monthlyFee = parseDouble(apt['monthly_fee']);
    final totalPaid = parseDouble(apt['total_paid']);
    final arrears = parseDouble(apt['arrears']);

    final paidMonths = apt['paid_months'] != null ? List<String>.from(apt['paid_months']) : <String>[];
    final unpaidMonths = apt['unpaid_months'] != null ? List<String>.from(apt['unpaid_months']) : <String>[];
    final partialMonths = apt['partial_months'] != null ? List<Map<String, dynamic>>.from(apt['partial_months']) : <Map<String, dynamic>>[];
    final advanceMonths = apt['advance_paid_months'] != null ? List<String>.from(apt['advance_paid_months']) : <String>[];

    // Accurate arrears computation with safety fallback
    double computedArrears = arrears;
    if (computedArrears <= 0 && isBillable) {
      if (unpaidMonths.isNotEmpty) {
        computedArrears = unpaidMonths.length * monthlyFee;
      }
      for (final pm in partialMonths) {
        computedArrears += parseDouble(pm['remaining_balance']);
      }
    }
    if (!isBillable) {
      computedArrears = 0.0;
    }

    String status = apt['status'] ?? 'ARREARS';
    if (!isBillable) {
      status = 'EXEMPT';
    } else if (computedArrears <= 0 && unpaidMonths.isEmpty && partialMonths.isEmpty) {
      status = 'PAID';
    } else if (partialMonths.isNotEmpty && unpaidMonths.isEmpty) {
      status = 'PARTIAL';
    } else if (partialMonths.isNotEmpty && unpaidMonths.isNotEmpty) {
      status = 'PARTIAL';
    } else {
      status = 'ARREARS';
    }

    Color statusColor;
    String statusTitle;
    IconData statusIcon;

    if (!isBillable) {
      statusColor = Colors.grey;
      statusTitle = 'معفية (شاغرة)';
      statusIcon = Icons.block;
    } else if (status == 'PAID') {
      statusColor = AppColors.success;
      statusTitle = 'خالصة بالكامل ✓';
      statusIcon = Icons.check_circle_outline;
    } else if (status == 'PARTIAL') {
      statusColor = AppColors.warning;
      statusTitle = 'أداء جزئي ⚠️';
      statusIcon = Icons.timelapse;
    } else {
      statusColor = AppColors.danger;
      statusTitle = 'عليها متأخرات ⏳';
      statusIcon = Icons.schedule;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.88,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          children: [
            // Handle bar
            const SizedBox(height: 12),
            Container(
              width: 48,
              height: 5,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            const SizedBox(height: 14),

            // Modal Header: Apartment Info & Status Badge
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: statusColor.withAlpha(25),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: statusColor.withAlpha(60)),
                    ),
                    child: Icon(statusIcon, color: statusColor, size: 26),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          unit,
                          style: GoogleFonts.cairo(
                            fontSize: 17,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          building,
                          style: GoogleFonts.cairo(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: statusColor.withAlpha(20),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: statusColor.withAlpha(70), width: 1.2),
                    ),
                    child: Text(
                      statusTitle,
                      style: GoogleFonts.cairo(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            const Divider(height: 1),

            // 🏷️ بطاقة مبلغ الاشتراك الشهري - بارزة وواضحة جداً
            Container(
              margin: const EdgeInsets.fromLTRB(16, 14, 16, 8),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppColors.primary.withAlpha(16),
                    AppColors.primary.withAlpha(6),
                  ],
                  begin: Alignment.topRight,
                  end: Alignment.bottomLeft,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.primary.withAlpha(45), width: 1.2),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primary.withAlpha(40),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.receipt_long_rounded,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'مبلغ الاشتراك الشهري',
                          style: GoogleFonts.cairo(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          'الواجب الشهري المحدد للشقة',
                          style: GoogleFonts.cairo(
                            fontSize: 11,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.baseline,
                        textBaseline: TextBaseline.alphabetic,
                        children: [
                          Text(
                            monthlyFee.toStringAsFixed(0),
                            style: GoogleFonts.cairo(
                              fontSize: 22,
                              fontWeight: FontWeight.w900,
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            'د.م',
                            style: GoogleFonts.cairo(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary,
                            ),
                          ),
                        ],
                      ),
                      Text(
                        'شهرياً',
                        style: GoogleFonts.cairo(
                          fontSize: 10,
                          color: AppColors.textSecondary,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Financial Summary Row: المتأخرات vs مجموع المؤدى
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  // 1. المتأخرات المستحقة
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                      decoration: BoxDecoration(
                        color: computedArrears > 0 ? AppColors.danger.withAlpha(12) : AppColors.success.withAlpha(12),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: computedArrears > 0 ? AppColors.danger.withAlpha(45) : AppColors.success.withAlpha(45),
                          width: 1.2,
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                computedArrears > 0 ? Icons.error_outline : Icons.check_circle_outline,
                                color: computedArrears > 0 ? AppColors.danger : AppColors.success,
                                size: 15,
                              ),
                              const SizedBox(width: 5),
                              Text(
                                computedArrears > 0 ? 'المتأخرات المستحقة' : 'المتأخرات',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: computedArrears > 0 ? AppColors.danger : AppColors.success,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${computedArrears.toStringAsFixed(0)} د.م',
                            style: GoogleFonts.cairo(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: computedArrears > 0 ? AppColors.danger : AppColors.success,
                            ),
                          ),
                          Text(
                            computedArrears > 0
                                ? '${unpaidMonths.length + partialMonths.length} شهر غير مكتمل'
                                : 'لا توجد متأخرات ✓',
                            style: GoogleFonts.cairo(
                              fontSize: 10,
                              color: computedArrears > 0 ? AppColors.danger : AppColors.success,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  // 2. المبالغ المؤداة
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border, width: 1.2),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(
                                Icons.account_balance_wallet_outlined,
                                color: AppColors.textSecondary,
                                size: 15,
                              ),
                              const SizedBox(width: 5),
                              Text(
                                'المبالغ المؤداة',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${totalPaid.toStringAsFixed(0)} د.م',
                            style: GoogleFonts.cairo(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          Text(
                            '${paidMonths.length} شهر مؤدى',
                            style: GoogleFonts.cairo(
                              fontSize: 10,
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Scrollable Breakdown (الأشهر غير المؤداة، المؤداة جزئياً، المؤداة)
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  // 1. Unpaid Months (الأشهر غير المؤداة المستحقة)
                  if (unpaidMonths.isNotEmpty) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                      decoration: BoxDecoration(
                        color: AppColors.danger.withAlpha(15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.event_busy, color: AppColors.danger, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'الأشهر غير المؤداة المستحقة (${unpaidMonths.length})',
                              style: GoogleFonts.cairo(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.danger,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.danger,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              'واجبة الأداء',
                              style: GoogleFonts.cairo(
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    ...unpaidMonths.map((m) {
                      return Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(
                          color: AppColors.danger.withAlpha(8),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.danger.withAlpha(50), width: 1.2),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.danger.withAlpha(20),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.calendar_today, color: AppColors.danger, size: 16),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _formatMonthArabic(m),
                                    style: GoogleFonts.cairo(
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                  Text(
                                    'اشتراك شهر غير مؤدى ($m)',
                                    style: GoogleFonts.cairo(
                                      fontSize: 11,
                                      color: AppColors.danger,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  '${monthlyFee.toStringAsFixed(0)} د.م',
                                  style: GoogleFonts.cairo(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.danger,
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                  decoration: BoxDecoration(
                                    color: AppColors.danger.withAlpha(20),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text(
                                    'غير مؤدى ⏳',
                                    style: GoogleFonts.cairo(
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 8),
                  ],

                  // 2. Partial Months (الأشهر ذات الأداء الجزئي)
                  if (partialMonths.isNotEmpty) ...[
                    Container(
                      margin: const EdgeInsets.only(top: 4, bottom: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                      decoration: BoxDecoration(
                        color: AppColors.warning.withAlpha(15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.timelapse, color: AppColors.warning, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'أشهر فيها أداء جزئي (${partialMonths.length})',
                              style: GoogleFonts.cairo(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.warning,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    ...partialMonths.map((pm) {
                      final monthStr = pm['month'] ?? '';
                      final paid = parseDouble(pm['amount_paid']);
                      final rem = parseDouble(pm['remaining_balance']);
                      final progress = monthlyFee > 0 ? (paid / monthlyFee).clamp(0.0, 1.0) : 0.5;

                      return Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.warning.withAlpha(10),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.warning.withAlpha(50), width: 1.2),
                        ),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: AppColors.warning.withAlpha(20),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.pie_chart_outline, color: AppColors.warning, size: 16),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _formatMonthArabic(monthStr),
                                        style: GoogleFonts.cairo(
                                          fontSize: 13,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      Text(
                                        'أُدّي: ${paid.toStringAsFixed(0)} د.م من أصل ${monthlyFee.toStringAsFixed(0)} د.م',
                                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: AppColors.danger.withAlpha(15),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: AppColors.danger.withAlpha(40)),
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Text(
                                        'المتبقي',
                                        style: GoogleFonts.cairo(fontSize: 9, color: AppColors.danger),
                                      ),
                                      Text(
                                        '${rem.toStringAsFixed(0)} د.م',
                                        style: GoogleFonts.cairo(
                                          fontSize: 13,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.danger,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: progress,
                                minHeight: 5,
                                backgroundColor: AppColors.warning.withAlpha(30),
                                color: AppColors.success,
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 8),
                  ],

                  // 3. Paid Months (الأشهر المؤداة بالكامل)
                  if (paidMonths.isNotEmpty) ...[
                    Container(
                      margin: const EdgeInsets.only(top: 4, bottom: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                      decoration: BoxDecoration(
                        color: AppColors.success.withAlpha(15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle_outline, color: AppColors.success, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'الأشهر المؤداة بالكامل (${paidMonths.length})',
                              style: GoogleFonts.cairo(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.success,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: paidMonths.map((m) {
                        final isAdvance = advanceMonths.contains(m);
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: isAdvance ? AppColors.secondary.withAlpha(15) : AppColors.success.withAlpha(12),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: isAdvance ? AppColors.secondary.withAlpha(50) : AppColors.success.withAlpha(40),
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                isAdvance ? Icons.rocket_launch : Icons.check,
                                color: isAdvance ? AppColors.secondary : AppColors.success,
                                size: 14,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                '${_formatMonthArabic(m)}${isAdvance ? " (مسبقاً)" : ""}',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: isAdvance ? AppColors.secondary : AppColors.success,
                                ),
                              ),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Success celebration banner if fully paid with no arrears
                  if (unpaidMonths.isEmpty && partialMonths.isEmpty && paidMonths.isNotEmpty) ...[
                    Container(
                      margin: const EdgeInsets.symmetric(vertical: 8),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.success.withAlpha(12),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.success.withAlpha(50)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.verified, color: AppColors.success, size: 30),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'الشقة خالصة بالكامل ✓',
                                  style: GoogleFonts.cairo(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.success,
                                  ),
                                ),
                                Text(
                                  'تم أداء جميع اشتراكات الشقة ولا توجد أي متأخرات مستحقة.',
                                  style: GoogleFonts.cairo(
                                    fontSize: 11,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  // If empty state for this apartment
                  if (paidMonths.isEmpty && unpaidMonths.isEmpty && partialMonths.isEmpty) ...[
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 24),
                      child: Center(
                        child: Text(
                          !isBillable ? 'هذه الشقة معفية من أداء الواجبات الشهرية.' : 'لا توجد حركات مالية مسجلة لهذه الشقة بعد.',
                          style: GoogleFonts.cairo(color: AppColors.textSecondary, fontSize: 12),
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

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
    final partialCount = data?['partial_apartments_count'] ?? 0;
    final arrearsCount = data?['arrears_apartments_count'] ?? 0;
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
                                'نسبة استخلاص مساهمات الإقامة',
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
                          const SizedBox(height: 10),
                          // Stats chips: Paid, Partial, Arrears
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceAround,
                            children: [
                              Row(
                                children: [
                                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.success, shape: BoxShape.circle)),
                                  const SizedBox(width: 4),
                                  Text('خالصة: $paidCount', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                                ],
                              ),
                              Row(
                                children: [
                                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.warning, shape: BoxShape.circle)),
                                  const SizedBox(width: 4),
                                  Text('جزئي: $partialCount', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                                ],
                              ),
                              Row(
                                children: [
                                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.danger, shape: BoxShape.circle)),
                                  const SizedBox(width: 4),
                                  Text('متأخرات: $arrearsCount', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                                ],
                              ),
                            ],
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
                    // Apartments Collection Status Section (Clickable Cards)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Row(
                        children: [
                          const Icon(Icons.touch_app_outlined, size: 16, color: AppColors.textSecondary),
                          const SizedBox(width: 4),
                          Text(
                            'اضغط على أي شقة لمعرفة الأشهر المؤداة والمتأخرة بالتفصيل',
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
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
                        final isBillable = apt['is_billable'] != false;
                        final monthlyFee = parseDouble(apt['monthly_fee']);
                        final arrears = parseDouble(apt['arrears']);

                        final paidMonths = apt['paid_months'] != null ? List<String>.from(apt['paid_months']) : <String>[];
                        final unpaidMonths = apt['unpaid_months'] != null ? List<String>.from(apt['unpaid_months']) : <String>[];
                        final partialMonths = apt['partial_months'] != null ? List<Map<String, dynamic>>.from(apt['partial_months']) : <Map<String, dynamic>>[];

                        // Compute robust arrears and effective status
                        double computedArrears = arrears;
                        if (computedArrears <= 0 && isBillable) {
                          if (unpaidMonths.isNotEmpty) {
                            computedArrears = unpaidMonths.length * monthlyFee;
                          }
                          for (final pm in partialMonths) {
                            computedArrears += parseDouble(pm['remaining_balance']);
                          }
                        }
                        if (!isBillable) {
                          computedArrears = 0.0;
                        }

                        String effectiveStatus = apt['status'] ?? 'ARREARS';
                        if (!isBillable) {
                          effectiveStatus = 'EXEMPT';
                        } else if (computedArrears <= 0 && unpaidMonths.isEmpty && partialMonths.isEmpty) {
                          effectiveStatus = 'PAID';
                        } else if (partialMonths.isNotEmpty && unpaidMonths.isEmpty) {
                          effectiveStatus = 'PARTIAL';
                        } else if (partialMonths.isNotEmpty && unpaidMonths.isNotEmpty) {
                          effectiveStatus = 'PARTIAL';
                        } else {
                          effectiveStatus = 'ARREARS';
                        }

                        Color cardColor;
                        String badgeText;
                        Color badgeColor;
                        IconData icon;
                        String subtitle;

                        if (!isBillable) {
                          cardColor = Colors.grey;
                          badgeText = 'معفية (شاغرة)';
                          badgeColor = Colors.grey;
                          icon = Icons.block;
                          subtitle = 'شقة شاغرة معفية من الواجبات';
                        } else if (effectiveStatus == 'PAID') {
                          cardColor = AppColors.success;
                          badgeText = 'خالصة بالكامل ✓';
                          badgeColor = AppColors.success;
                          icon = Icons.check_circle_outline;
                          subtitle = 'مؤداة بالكامل (${paidMonths.length} أشهر)';
                        } else if (effectiveStatus == 'PARTIAL') {
                          cardColor = AppColors.warning;
                          badgeText = 'أداء جزئي ⚠️';
                          badgeColor = AppColors.warning;
                          icon = Icons.timelapse;
                          subtitle = 'أداء جزئي (${partialMonths.length} أشهر) • متبقي: ${computedArrears.toStringAsFixed(0)} د.م';
                        } else {
                          cardColor = AppColors.danger;
                          badgeText = 'متأخرات ⏳';
                          badgeColor = AppColors.danger;
                          icon = Icons.schedule;
                          final unpaidDesc = _formatUnpaidMonthsSummary(unpaidMonths);
                          subtitle = unpaidDesc.isNotEmpty
                              ? 'متأخرات: ${computedArrears.toStringAsFixed(0)} د.م ($unpaidDesc)'
                              : 'متأخرات: ${computedArrears.toStringAsFixed(0)} د.م (${unpaidMonths.length} أشهر)';
                        }

                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Material(
                            color: Colors.transparent,
                            borderRadius: BorderRadius.circular(14),
                            child: InkWell(
                              onTap: () => _showApartmentDetails(context, apt),
                              borderRadius: BorderRadius.circular(14),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(10),
                                      decoration: BoxDecoration(
                                        color: cardColor.withAlpha(20),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Icon(icon, color: cardColor, size: 22),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Text(
                                                unit,
                                                style: GoogleFonts.cairo(
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.bold,
                                                  color: AppColors.textPrimary,
                                                ),
                                              ),
                                              if (isBillable) ...[
                                                const SizedBox(width: 8),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                                  decoration: BoxDecoration(
                                                    color: AppColors.primary.withAlpha(15),
                                                    borderRadius: BorderRadius.circular(6),
                                                  ),
                                                  child: Text(
                                                    '${monthlyFee.toStringAsFixed(0)} د.م/شهر',
                                                    style: GoogleFonts.cairo(
                                                      fontSize: 10,
                                                      fontWeight: FontWeight.bold,
                                                      color: AppColors.primary,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ],
                                          ),
                                          Text(
                                            building,
                                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            subtitle,
                                            style: GoogleFonts.cairo(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w600,
                                              color: cardColor,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: badgeColor.withAlpha(20),
                                            borderRadius: BorderRadius.circular(20),
                                            border: Border.all(color: badgeColor.withAlpha(60)),
                                          ),
                                          child: Text(
                                            badgeText,
                                            style: GoogleFonts.cairo(
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold,
                                              color: badgeColor,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Text(
                                              'التفاصيل',
                                              style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                                            ),
                                            const Icon(Icons.chevron_left, size: 14, color: AppColors.textSecondary),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ),
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
