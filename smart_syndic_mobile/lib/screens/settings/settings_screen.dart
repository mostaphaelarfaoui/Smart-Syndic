import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';
import '../../providers/auth_provider.dart';
import '../syndic/dialogs/generate_report_dialog.dart';
import 'profile_settings_screen.dart';
import 'residence_settings_screen.dart';
import 'stamp_settings_screen.dart';
import 'server_settings_dialog.dart';

/// 🏠 Unified Settings Screen
/// Groups all settings into clean sections (iOS/Material style).
/// Replaces the scattered buttons in AppBar of SyndicMainScreen.
class SettingsScreen extends StatelessWidget {
  /// If true → show Residence & Stamp sections (Syndic mode)
  /// If false → show only Account & About sections (Resident mode)
  final bool isSyndic;

  const SettingsScreen({super.key, this.isSyndic = true});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    final String fullName =
        '${user?.firstName ?? ''} ${user?.lastName ?? ''}'.trim();
    final String email = user?.email ?? '';
    final String role = isSyndic ? 'سانديك الإقامة' : 'ساكن';

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: Text(
          'الإعدادات',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
        foregroundColor: AppColors.textPrimary,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: AppColors.border),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        children: [
          // ─── Profile Header ──────────────────────────────────────────────
          _ProfileHeader(
            fullName: fullName,
            email: email,
            role: role,
            isSyndic: isSyndic,
          ),

          const SizedBox(height: 28),

          // ─── Section 1: Residence Settings (Syndic only) ─────────────────
          if (isSyndic) ...[
            _SectionHeader(
              icon: Icons.home_work_outlined,
              label: 'إعدادات الإقامة',
              color: AppColors.primary,
            ),
            _SettingsGroup(items: [
              _SettingsTile(
                icon: Icons.apartment_outlined,
                iconBg: AppColors.primary,
                title: 'بيانات الإقامة',
                subtitle: 'الاسم، المدينة، نوع الملكية، الحساب البنكي (RIB)',
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const ResidenceSettingsScreen(),
                  ),
                ),
              ),
              _SettingsTile(
                icon: Icons.verified_outlined,
                iconBg: const Color(0xFF7C3AED),
                title: 'خاتم وطابع السانديك',
                subtitle: 'رفع وإدارة الخاتم الرسمي في التوصيلات PDF',
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const StampSettingsScreen(),
                  ),
                ),
              ),
              _SettingsTile(
                icon: Icons.archive_outlined,
                iconBg: const Color(0xFF059669),
                title: 'تصدير أرشيف وسجلات الإقامة',
                subtitle: 'تصدير الشقق، السكان، والتقارير المالية Excel و PDF',
                onTap: () => _showExportArchiveDialog(context),
                isLast: true,
              ),
            ]),
            const SizedBox(height: 24),
          ],

          // ─── Section 2: Account & Profile ────────────────────────────────
          _SectionHeader(
            icon: Icons.person_outline,
            label: 'الحساب الشخصي',
            color: const Color(0xFF0EA5E9),
          ),
          _SettingsGroup(items: [
            _SettingsTile(
              icon: Icons.edit_outlined,
              iconBg: const Color(0xFF0EA5E9),
              title: 'تعديل المعلومات والتفضيلات',
              subtitle: 'الاسم، الهاتف (${user?.locale == "fr" ? "Français" : "العربية"} • ${user?.themePreference == "dark" ? "الوضع الليلي" : "الوضع النهاري"})',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ProfileSettingsScreen(),
                ),
              ),
            ),
            _SettingsTile(
              icon: Icons.notifications_active_outlined,
              iconBg: const Color(0xFF6366F1),
              title: 'تفضيلات التنبيهات والإشعارات',
              subtitle: 'إشعارات الأداء المالي، انضمام السكان، والتصويتات',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ProfileSettingsScreen(),
                ),
              ),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 24),

          // ─── Section 3: Security ─────────────────────────────────────────
          _SectionHeader(
            icon: Icons.shield_outlined,
            label: 'الأمان وكلمة المرور',
            color: const Color(0xFFF59E0B),
          ),
          _SettingsGroup(items: [
            _SettingsTile(
              icon: Icons.lock_outline,
              iconBg: const Color(0xFFF59E0B),
              title: 'تغيير كلمة المرور',
              subtitle: 'يتطلب إدخال كلمة المرور الحالية للتأكيد',
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ProfileSettingsScreen(),
                ),
              ),
            ),
            _SettingsTile(
              icon: Icons.devices_outlined,
              iconBg: const Color(0xFF6366F1),
              title: 'الأجهزة والجلسات النشطة',
              subtitle: 'إنهاء الجلسات المفتوحة على الأجهزة الأخرى بنقرة واحدة',
              onTap: () => _confirmLogoutOtherDevices(context),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 24),

          // ─── Section 4: Logout ───────────────────────────────────────────
          _SectionHeader(
            icon: Icons.security_outlined,
            label: 'الخروج والأمان',
            color: const Color(0xFFEF4444),
          ),
          _SettingsGroup(items: [
            _SettingsTile(
              icon: Icons.logout,
              iconBg: const Color(0xFFEF4444),
              title: 'تسجيل الخروج',
              subtitle: 'إنهاء الجلسة الحالية على هذا الجهاز',
              isDestructive: true,
              onTap: () => _confirmLogout(context),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 24),

          // ─── Section 5: About / Developer ────────────────────────────────
          _SectionHeader(
            icon: Icons.info_outline,
            label: 'حول التطبيق',
            color: AppColors.textSecondary,
          ),
          _SettingsGroup(items: [
            _SettingsTile(
              icon: Icons.dns_outlined,
              iconBg: AppColors.textSecondary,
              title: 'إعدادات الخادم',
              subtitle: 'تعديل عنوان السيرفر (للمطورين فقط)',
              onTap: () => showDialog(
                context: context,
                builder: (_) => const ServerSettingsDialog(),
              ),
            ),
            _SettingsTile(
              icon: Icons.verified_user_outlined,
              iconBg: const Color(0xFF64748B),
              title: 'إصدار التطبيق',
              subtitle: 'Smart Syndic — النسخة الحالية',
              onTap: null,
              trailing: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(20),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  'v1.0',
                  style: GoogleFonts.cairo(
                    color: AppColors.primary,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 32),

          // Footer
          Center(
            child: Text(
              'Smart Syndic © 2026\nجميع الحقوق محفوظة',
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontSize: 11,
                color: AppColors.textLight,
                height: 1.6,
              ),
            ),
          ),

          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'تسجيل الخروج',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
        ),
        content: Text(
          'هل أنت متأكد من رغبتك في تسجيل الخروج من حسابك؟',
          style: GoogleFonts.cairo(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء',
                style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('تسجيل الخروج',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      await context.read<AuthProvider>().logout();
    }
  }

  Future<void> _confirmLogoutOtherDevices(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.security, color: Color(0xFF6366F1)),
            const SizedBox(width: 8),
            Text(
              'الأجهزة والجلسات النشطة',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
            ),
          ],
        ),
        content: Text(
          'هل أنت متأكد من رغبتك في إنهاء جميع الجلسات المفتوحة على الهواتف والمتصفحات الأخرى؟ ستبقى متصلاً فقط على هذا الجهاز الحالي.',
          style: GoogleFonts.cairo(fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6366F1),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('تأكيد وتسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final success = await auth.logoutOtherDevices();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success
                  ? 'تم إنهاء الجلسات على جميع الأجهزة الأخرى بنجاح'
                  : (auth.errorMessage ?? 'حدث خطأ'),
              style: GoogleFonts.cairo(),
            ),
            backgroundColor: success ? AppColors.success : AppColors.danger,
          ),
        );
      }
    }
  }

  void _showExportArchiveDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        final serverUrl = ApiService().baseUrl.replaceAll('/api/v1', '');
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    const Icon(Icons.archive_outlined, color: Color(0xFF059669), size: 26),
                    const SizedBox(width: 10),
                    Text(
                      'تصدير أرشيف وسجلات الإقامة',
                      style: GoogleFonts.cairo(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  'تحميل وتصدير نسخ رقمية من سجلات الإقامة بصيغة CSV و PDF:',
                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                ),
                const SizedBox(height: 16),
                ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFECFDF5),
                    child: Icon(Icons.picture_as_pdf, color: Color(0xFF059669)),
                  ),
                  title: Text('كشف الحساب والتقرير المالي الرسمي (PDF)',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Text('استخراج تقرير مالي رسمي مفصل ومختوم للإقامة',
                      style: GoogleFonts.cairo(fontSize: 11)),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 14),
                  onTap: () {
                    Navigator.pop(ctx);
                    showDialog(
                      context: context,
                      builder: (_) => const GenerateReportDialog(),
                    );
                  },
                ),
                const Divider(),
                ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFEFF6FF),
                    child: Icon(Icons.table_view_outlined, color: Color(0xFF2563EB)),
                  ),
                  title: Text('سجل الشقق والوحدات (Excel / CSV)',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Text('أرقام الشقق، الطوابق، الواجب الشهري، والشاغلين',
                      style: GoogleFonts.cairo(fontSize: 11)),
                  trailing: const Icon(Icons.download, size: 20),
                  onTap: () {
                    Navigator.pop(ctx);
                    launchUrl(Uri.parse('$serverUrl/portal/settings/export?type=apartments'),
                        mode: LaunchMode.externalApplication);
                  },
                ),
                ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFF5F3FF),
                    child: Icon(Icons.people_outline, color: Color(0xFF7C3AED)),
                  ),
                  title: Text('دليل السكان والاتصال (Excel / CSV)',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Text('الملاك، المكترين، أرقام الهواتف، والبريد الإلكتروني',
                      style: GoogleFonts.cairo(fontSize: 11)),
                  trailing: const Icon(Icons.download, size: 20),
                  onTap: () {
                    Navigator.pop(ctx);
                    launchUrl(Uri.parse('$serverUrl/portal/settings/export?type=residents'),
                        mode: LaunchMode.externalApplication);
                  },
                ),
                ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFFFFBEB),
                    child: Icon(Icons.payments_outlined, color: Color(0xFFD97706)),
                  ),
                  title: Text('سجل الواجبات الشهرية والمداخيل (Excel / CSV)',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Text('سجل كامل لوصولات الأداء، المبالغ، والتواريخ',
                      style: GoogleFonts.cairo(fontSize: 11)),
                  trailing: const Icon(Icons.download, size: 20),
                  onTap: () {
                    Navigator.pop(ctx);
                    launchUrl(Uri.parse('$serverUrl/portal/settings/export?type=payments'),
                        mode: LaunchMode.externalApplication);
                  },
                ),
                ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFFEF2F2),
                    child: Icon(Icons.receipt_long_outlined, color: Color(0xFFDC2626)),
                  ),
                  title: Text('سجل المصاريف والفواتير (Excel / CSV)',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Text('سجل النفقات، الشركات، وتواريخ الصرف',
                      style: GoogleFonts.cairo(fontSize: 11)),
                  trailing: const Icon(Icons.download, size: 20),
                  onTap: () {
                    Navigator.pop(ctx);
                    launchUrl(Uri.parse('$serverUrl/portal/settings/export?type=expenses'),
                        mode: LaunchMode.externalApplication);
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Profile Header Widget
// ─────────────────────────────────────────────────────────────────────────────
class _ProfileHeader extends StatelessWidget {
  final String fullName;
  final String email;
  final String role;
  final bool isSyndic;

  const _ProfileHeader({
    required this.fullName,
    required this.email,
    required this.role,
    required this.isSyndic,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isSyndic
              ? [AppColors.primary, AppColors.primaryLight]
              : [const Color(0xFF0EA5E9), const Color(0xFF38BDF8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: (isSyndic ? AppColors.primary : const Color(0xFF0EA5E9))
                .withAlpha(60),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          // Avatar Circle
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(40),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white.withAlpha(80), width: 2),
            ),
            child: Center(
              child: Text(
                fullName.isNotEmpty ? fullName[0].toUpperCase() : '؟',
                style: GoogleFonts.cairo(
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  fullName.isNotEmpty ? fullName : 'المستخدم',
                  style: GoogleFonts.cairo(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 3),
                Text(
                  email,
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    color: Colors.white.withAlpha(200),
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 6),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.white.withAlpha(35),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.white.withAlpha(60)),
                  ),
                  child: Text(
                    role,
                    style: GoogleFonts.cairo(
                      fontSize: 11,
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
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

// ─────────────────────────────────────────────────────────────────────────────
// Section Header Widget
// ─────────────────────────────────────────────────────────────────────────────
class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;

  const _SectionHeader({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, right: 4),
      child: Row(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: GoogleFonts.cairo(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: color,
              letterSpacing: 0.3,
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Settings Group (Card wrapping multiple tiles)
// ─────────────────────────────────────────────────────────────────────────────
class _SettingsGroup extends StatelessWidget {
  final List<_SettingsTile> items;

  const _SettingsGroup({required this.items});

  @override
  Widget build(BuildContext context) {
    return Container(
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
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Column(children: items),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Individual Settings Tile
// ─────────────────────────────────────────────────────────────────────────────
class _SettingsTile extends StatelessWidget {
  final IconData icon;
  final Color iconBg;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;
  final Widget? trailing;
  final bool isLast;
  final bool isDestructive;

  const _SettingsTile({
    required this.icon,
    required this.iconBg,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.trailing,
    this.isLast = false,
    this.isDestructive = false,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        InkWell(
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                // Icon box
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: iconBg,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: Colors.white, size: 20),
                ),
                const SizedBox(width: 14),
                // Title + subtitle
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: GoogleFonts.cairo(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: isDestructive
                              ? AppColors.danger
                              : AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: GoogleFonts.cairo(
                          fontSize: 11,
                          color: AppColors.textLight,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                // Trailing widget
                trailing ??
                    Icon(
                      Icons.arrow_forward_ios,
                      size: 14,
                      color: onTap != null
                          ? AppColors.textSecondary
                          : Colors.transparent,
                    ),
              ],
            ),
          ),
        ),
        if (!isLast)
          Divider(
            height: 1,
            thickness: 1,
            indent: 70,
            endIndent: 0,
            color: AppColors.border.withAlpha(120),
          ),
      ],
    );
  }
}
