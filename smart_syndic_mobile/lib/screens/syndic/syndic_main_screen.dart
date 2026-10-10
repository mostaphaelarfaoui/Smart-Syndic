import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/syndic_provider.dart';
import '../settings/server_settings_dialog.dart';
import '../settings/profile_settings_screen.dart';
import 'tabs/syndic_dashboard_tab.dart';
import 'tabs/syndic_payments_tab.dart';
import 'tabs/syndic_expenses_tab.dart';
import 'tabs/syndic_community_tab.dart';

class SyndicMainScreen extends StatefulWidget {
  const SyndicMainScreen({super.key});

  @override
  State<SyndicMainScreen> createState() => _SyndicMainScreenState();
}

class _SyndicMainScreenState extends State<SyndicMainScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialData();
    });
  }

  void _loadInitialData() {
    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    syndic.fetchDashboardKpis();
    syndic.fetchApartments();
    syndic.fetchPayments();
    syndic.fetchExpenses();
    syndic.fetchPendingResidents();
    syndic.fetchSuggestions();
    syndic.fetchPolls();
  }

  void _navigateTab(int index) {
    setState(() => _currentIndex = index);
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('تسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        content: Text('هل أنت متأكد من رغبتك في تسجيل الخروج من حساب السانديك؟', style: GoogleFonts.cairo()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo()),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('تسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await Provider.of<AuthProvider>(context, listen: false).logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final pendingCount = syndic.pendingResidents.length;

    final titles = [
      'لوحة قيادة السانديك',
      'إدارة استخلاص الواجبات',
      'دفتر مصاريف الإقامة',
      'شؤون السكان والمجتمع',
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_currentIndex]),
        leading: Padding(
          padding: const EdgeInsets.all(10),
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.primary.withAlpha(25),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.admin_panel_settings, color: AppColors.primary, size: 22),
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_outline, color: AppColors.primary),
            tooltip: 'حسابي',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ProfileSettingsScreen()),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.dns_outlined, color: AppColors.textSecondary),
            tooltip: 'إعدادات الخادم',
            onPressed: () => showDialog(
              context: context,
              builder: (_) => const ServerSettingsDialog(),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: AppColors.danger),
            tooltip: 'تسجيل الخروج',
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          SyndicDashboardTab(onNavigateTab: _navigateTab),
          const SyndicPaymentsTab(),
          const SyndicExpensesTab(),
          const SyndicCommunityTab(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (idx) => setState(() => _currentIndex = idx),
        indicatorColor: AppColors.primary.withAlpha(30),
        backgroundColor: Colors.white,
        elevation: 8,
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard, color: AppColors.primary),
            label: 'الرئيسية',
          ),
          const NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long, color: AppColors.primary),
            label: 'المداخيل',
          ),
          const NavigationDestination(
            icon: Icon(Icons.outbox_outlined),
            selectedIcon: Icon(Icons.outbox, color: AppColors.primary),
            label: 'المصاريف',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: pendingCount > 0,
              label: Text('$pendingCount'),
              child: const Icon(Icons.people_outline),
            ),
            selectedIcon: Badge(
              isLabelVisible: pendingCount > 0,
              label: Text('$pendingCount'),
              child: const Icon(Icons.people, color: AppColors.primary),
            ),
            label: 'السكان',
          ),
        ],
      ),
    );
  }
}
