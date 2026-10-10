import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/resident_provider.dart';
import '../settings/server_settings_dialog.dart';
import '../settings/profile_settings_screen.dart';
import 'tabs/resident_home_tab.dart';
import 'tabs/resident_payments_tab.dart';
import 'tabs/resident_transparency_tab.dart';
import 'tabs/resident_community_tab.dart';

class ResidentMainScreen extends StatefulWidget {
  const ResidentMainScreen({super.key});

  @override
  State<ResidentMainScreen> createState() => _ResidentMainScreenState();
}

class _ResidentMainScreenState extends State<ResidentMainScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  void _loadData() {
    final res = Provider.of<ResidentProvider>(context, listen: false);
    res.fetchMyBalance();
    res.fetchMyPayments();
    res.fetchTransparencyLedger();
    res.fetchPolls();
    res.fetchMySuggestions();
  }

  void _navigateTab(int index) {
    setState(() => _currentIndex = index);
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('تسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        content: Text('هل أنت متأكد من رغبتك في تسجيل الخروج من حساب الساكن؟', style: GoogleFonts.cairo()),
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
    final titles = [
      'فضاء الساكن',
      'إيصالات الأداء الرسمية',
      'دفتر الشفافية المالية',
      'التصويت والمقترحات',
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
            child: const Icon(Icons.apartment, color: AppColors.primary, size: 22),
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
          ResidentHomeTab(onNavigateTab: _navigateTab),
          const ResidentPaymentsTab(),
          const ResidentTransparencyTab(),
          const ResidentCommunityTab(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (idx) => setState(() => _currentIndex = idx),
        indicatorColor: AppColors.primary.withAlpha(30),
        backgroundColor: Colors.white,
        elevation: 8,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home, color: AppColors.primary),
            label: 'وضعيتي',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_outlined),
            selectedIcon: Icon(Icons.receipt, color: AppColors.primary),
            label: 'إيصالاتي',
          ),
          NavigationDestination(
            icon: Icon(Icons.account_balance_outlined),
            selectedIcon: Icon(Icons.account_balance, color: AppColors.primary),
            label: 'الشفافية',
          ),
          NavigationDestination(
            icon: Icon(Icons.how_to_vote_outlined),
            selectedIcon: Icon(Icons.how_to_vote, color: AppColors.primary),
            label: 'التصويت',
          ),
        ],
      ),
    );
  }
}
