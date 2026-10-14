import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/resident_provider.dart';
import '../../core/services/api_service.dart';
import '../settings/server_settings_dialog.dart';
import 'resident_profile_screen.dart';
import 'tabs/resident_home_tab.dart';
import 'tabs/resident_payments_tab.dart';
import 'tabs/resident_transparency_tab.dart';
import 'tabs/resident_community_tab.dart';
import '../../providers/notification_provider.dart';
import '../notifications/notifications_screen.dart';
import '../../models/notification_model.dart';

class ResidentMainScreen extends StatefulWidget {
  const ResidentMainScreen({super.key});

  @override
  State<ResidentMainScreen> createState() => _ResidentMainScreenState();
}

class _ResidentMainScreenState extends State<ResidentMainScreen> {
  int _currentIndex = 0;
  final GlobalKey<ResidentCommunityTabState> _communityKey = GlobalKey<ResidentCommunityTabState>();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  void _loadData() {
    final res = Provider.of<ResidentProvider>(context, listen: false);
    res.fetchProfile();
    res.fetchMyBalance();
    res.fetchMyPayments();
    res.fetchTransparencyLedger();
    res.fetchPolls();
    res.fetchMySuggestions();

    final notif = Provider.of<NotificationProvider>(context, listen: false);
    notif.fetchNotifications(silent: true);
    notif.startSilentPolling();
  }

  void _navigateTab(int index, [int? subIndex]) {
    setState(() => _currentIndex = index);
    if (index == 3 && subIndex != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _communityKey.currentState?.switchToSubTab(subIndex);
      });
    }
  }

  void _handleNotificationNavigation(NotificationModel notif) {
    final target = (notif.targetScreen ?? '').toLowerCase();
    final url = (notif.url ?? '').toLowerCase();

    if (target == 'payments' || target == 'receipt' || url.contains('payments') || url.contains('receipt')) {
      _navigateTab(1); // Payments tab
    } else if (target == 'transparency' || url.contains('transparency') || target == 'expenses' || url.contains('expenses')) {
      _navigateTab(2); // Transparency ledger tab
    } else if (target == 'polls' || url.contains('polls')) {
      _navigateTab(3, 0); // Community tab -> Polls & voting
    } else if (target == 'community' || target == 'suggestions' || target == 'complaints' || url.contains('community') || url.contains('suggestions')) {
      _navigateTab(3, 1); // Community tab -> Suggestions & complaints
    } else {
      _navigateTab(0); // Home tab
    }
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
      Provider.of<ResidentProvider>(context, listen: false).clear();
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
          // Notifications Bell Icon with live unread badge
          Consumer<NotificationProvider>(
            builder: (context, notifProv, _) {
              return Stack(
                alignment: Alignment.center,
                children: [
                  IconButton(
                    icon: const Icon(Icons.notifications_none_rounded, color: AppColors.primary),
                    tooltip: 'الإشعارات',
                    onPressed: () async {
                      final selectedNotif = await Navigator.push<NotificationModel>(
                        context,
                        MaterialPageRoute(builder: (_) => const NotificationsScreen()),
                      );
                      if (selectedNotif != null) {
                        _handleNotificationNavigation(selectedNotif);
                      }
                    },
                  ),
                  if (notifProv.unreadCount > 0)
                    Positioned(
                      top: 6,
                      right: 6,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.danger,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Theme.of(context).cardColor, width: 1.5),
                        ),
                        constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
                        child: Text(
                          notifProv.unreadCount > 99 ? '99+' : '${notifProv.unreadCount}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.person_outline, color: AppColors.primary),
            tooltip: 'ملفي الشخصي',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ResidentProfileScreen()),
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
          ValueListenableBuilder<bool>(
            valueListenable: ApiService().isNetworkActive,
            builder: (context, isActive, child) {
              if (isActive) {
                return const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.0),
                  child: Center(
                    child: SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                );
              }
              return const SizedBox.shrink();
            },
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
          ResidentCommunityTab(key: _communityKey),
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
