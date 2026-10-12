import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter/services.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/number_utils.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/resident_provider.dart';
import '../resident_profile_screen.dart';

class ResidentHomeTab extends StatelessWidget {
  final Function(int) onNavigateTab;

  const ResidentHomeTab({super.key, required this.onNavigateTab});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final resident = context.watch<ResidentProvider>();
    final balance = resident.balanceInfo;
    final balanceData = (balance?['balance'] as Map<String, dynamic>?) ?? balance;

    final unitNumber = balance?['apartment']?['unit_number'] ?? '-';
    final floor = balance?['apartment']?['floor']?.toString() ?? '-';
    final monthlyFee = parseDouble(balanceData?['monthly_fee'] ?? balance?['apartment']?['monthly_fee']);
    final arrears = parseDouble(balanceData?['arrears']);
    final isUpToDate = (balanceData?['is_settled'] == true) || arrears <= 0;
    final advancePaidMonths = (balanceData?['advance_paid_months'] as List?)?.cast<String>() ?? [];

    return RefreshIndicator(
      onRefresh: () async {
        await resident.fetchMyBalance();
        await resident.fetchMyPayments();
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Welcome greeting
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ResidentProfileScreen()),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  CircleAvatar(
                    radius: 20,
                    backgroundColor: AppColors.primary.withAlpha(20),
                    child: Text(
                      (auth.user?.firstName.isNotEmpty ?? false) ? auth.user!.firstName.substring(0, 1) : 'S',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primary),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'أهلاً بك، ${auth.user?.fullName ?? "الجار العزيز"} 👋',
                          style: GoogleFonts.cairo(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          'شقة رقم $unitNumber - الطابق $floor',
                          style: GoogleFonts.cairo(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withAlpha(20),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          auth.tenant?.name ?? 'إقامة الفردوس',
                          style: GoogleFonts.cairo(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.arrow_forward_ios, size: 10, color: AppColors.primary),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Main Financial Status Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: isUpToDate
                    ? [const Color(0xFF0F766E), const Color(0xFF14B8A6)]
                    : [const Color(0xFFBE123C), const Color(0xFFF43F5E)],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: (isUpToDate ? AppColors.primary : AppColors.danger).withAlpha(50),
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
                    Text(
                      'الوضعية المالية للشقة',
                      style: GoogleFonts.cairo(
                        fontSize: 14,
                        color: Colors.white.withAlpha(220),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withAlpha(40),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            isUpToDate ? Icons.check_circle : Icons.warning_amber_rounded,
                            color: Colors.white,
                            size: 14,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            isUpToDate ? 'الواجبات مسددة' : 'متأخرات معلقة',
                            style: GoogleFonts.cairo(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  isUpToDate ? '0.00 درهم' : '${arrears.toStringAsFixed(0)} درهم',
                  style: GoogleFonts.cairo(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  isUpToDate
                      ? 'وضعية حسابك سليمة وخالصة تماماً، شكراً لالتزامك الدائم'
                      : 'المبلغ الإجمالي المتبقي بذمتكم لصندوق السانديك',
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    color: Colors.white.withAlpha(220),
                  ),
                ),
                if (advancePaidMonths.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white.withAlpha(45),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.star, size: 14, color: Colors.amberAccent),
                        const SizedBox(width: 6),
                        Text(
                          'مدفوع مسبقاً حتى شهر: ${advancePaidMonths.last}',
                          style: GoogleFonts.cairo(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                const Divider(color: Colors.white24, height: 28),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'الواجب الشهري المعتمد',
                          style: GoogleFonts.cairo(fontSize: 11, color: Colors.white70),
                        ),
                        Text(
                          '${monthlyFee.toStringAsFixed(0)} درهم / شهر',
                          style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ],
                    ),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: isUpToDate ? AppColors.primaryDark : AppColors.danger,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () => onNavigateTab(1), // Go to receipts tab
                      child: Text(
                        'عرض إيصالاتي',
                        style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Condo Bank Account Info Card (for Virement)
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
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withAlpha(20),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.account_balance, color: AppColors.primary, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'الحساب البنكي للإقامة (RIB)',
                          style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          'للأداء عن طريق تحويل بنكي مباشر',
                          style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        '011 780 0000123456789012 34',
                        style: TextStyle(fontFamily: 'monospace', fontSize: 13, fontWeight: FontWeight.bold),
                      ),
                      InkWell(
                        onTap: () {
                          Clipboard.setData(const ClipboardData(text: '011780000012345678901234'));
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('تم نسخ رقم الحساب (RIB) إلى الحافظة ✓')),
                          );
                        },
                        child: const Icon(Icons.copy, size: 18, color: AppColors.primary),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Community Quick shortcuts
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () => onNavigateTab(2), // Transparency
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.info.withAlpha(25),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.visibility_outlined, color: AppColors.info, size: 24),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          'الشفافية المالية',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                        Text(
                          'مداخيل ومصاريف الإقامة',
                          style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: InkWell(
                  onTap: () => onNavigateTab(3), // Community & Polls
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.secondary.withAlpha(25),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.how_to_vote_outlined, color: AppColors.secondary, size: 24),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          'التصويت والمقترحات',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                        Text(
                          'شارك برأيك في القرارات',
                          style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}
