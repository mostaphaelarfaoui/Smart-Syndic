import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/syndic_provider.dart';
import '../../core/services/api_service.dart';
import '../settings/server_settings_dialog.dart';
import '../settings/profile_settings_screen.dart';
import '../settings/stamp_settings_screen.dart'; // Feature 1
import '../settings/residence_settings_screen.dart';
import 'tabs/syndic_dashboard_tab.dart';
import 'tabs/syndic_apartments_tab.dart';
import 'tabs/syndic_payments_tab.dart';
import 'tabs/syndic_expenses_tab.dart';
import 'tabs/syndic_community_tab.dart';
import '../../models/notification_model.dart';
import '../../providers/notification_provider.dart';
import '../notifications/notifications_screen.dart';

class SyndicMainScreen extends StatefulWidget {
  const SyndicMainScreen({super.key});

  @override
  State<SyndicMainScreen> createState() => _SyndicMainScreenState();
}

class _SyndicMainScreenState extends State<SyndicMainScreen> {
  int _currentIndex = 0;
  final GlobalKey<SyndicCommunityTabState> _communityKey = GlobalKey<SyndicCommunityTabState>();

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

    final notif = Provider.of<NotificationProvider>(context, listen: false);
    notif.fetchNotifications(silent: true);
    notif.startSilentPolling();
  }

  void _navigateTab(int index, [int? subIndex]) {
    setState(() => _currentIndex = index);
    if (index == 4 && subIndex != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _communityKey.currentState?.switchToSubTab(subIndex);
      });
    }
  }

  void _handleNotificationNavigation(NotificationModel notif) {
    final target = (notif.targetScreen ?? '').toLowerCase();
    final url = (notif.url ?? '').toLowerCase();

    if (target == 'payments' || url.contains('payments')) {
      _navigateTab(2); // Payments tab
    } else if (target == 'apartments' || url.contains('apartments')) {
      _navigateTab(1); // Apartments tab
    } else if (target == 'expenses' || url.contains('expenses')) {
      _navigateTab(3); // Expenses tab
    } else if (target == 'community' || target == 'suggestions' || target == 'complaints' || url.contains('community')) {
      _navigateTab(4, 0); // Community tab -> Complaints & Suggestions
    } else if (target == 'polls' || url.contains('polls')) {
      _navigateTab(4, 1); // Community tab -> Polls
    } else if (target == 'pending_residents' || url.contains('residents')) {
      _navigateTab(4, 2); // Community tab -> Pending Residents
    } else {
      _navigateTab(0); // Dashboard
    }
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
      Provider.of<SyndicProvider>(context, listen: false).clear();
      await Provider.of<AuthProvider>(context, listen: false).logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final pendingCount = syndic.pendingResidents.length;

    final titles = [
      'لوحة قيادة السانديك',
      'إدارة الشقق',
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
            icon: const Icon(Icons.settings_outlined, color: AppColors.primary),
            tooltip: 'إعدادات الإقامة والسانديك',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ResidenceSettingsScreen()),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.person_outline, color: AppColors.primary),
            tooltip: 'حسابي',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ProfileSettingsScreen()),
            ),
          ),
          // Feature 1 — Stamp settings
          IconButton(
            icon: const Icon(Icons.verified_outlined, color: AppColors.primary),
            tooltip: 'طابع الإقامة',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const StampSettingsScreen()),
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
          SyndicDashboardTab(onNavigateTab: _navigateTab),
          const SyndicApartmentsTab(),
          const SyndicPaymentsTab(),
          const SyndicExpensesTab(),
          SyndicCommunityTab(key: _communityKey),
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
            icon: Icon(Icons.apartment_outlined),
            selectedIcon: Icon(Icons.apartment, color: AppColors.primary),
            label: 'الشقق',
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
