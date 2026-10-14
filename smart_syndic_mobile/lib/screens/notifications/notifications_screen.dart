import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../models/notification_model.dart';
import '../../providers/notification_provider.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  String _filter = 'all'; // 'all' or 'unread'

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().fetchNotifications();
    });
  }

  Color _getTypeColor(String type) {
    switch (type.toLowerCase()) {
      case 'danger':
        return AppColors.danger;
      case 'warning':
        return AppColors.warning;
      case 'success':
        return AppColors.success;
      case 'info':
      default:
        return AppColors.primary;
    }
  }

  @override
  Widget build(BuildContext context) {
    final notifProv = context.watch<NotificationProvider>();
    final allList = notifProv.notifications;
    final unreadList = allList.where((n) => !n.isRead).toList();
    final displayedList = _filter == 'unread' ? unreadList : allList;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'الإشعارات',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
        ),
        actions: [
          if (notifProv.unreadCount > 0)
            TextButton.icon(
              onPressed: () => notifProv.markAllAsRead(),
              icon: const Icon(Icons.done_all, size: 18, color: AppColors.primary),
              label: Text(
                'تحديد كمقروء',
                style: GoogleFonts.cairo(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: AppColors.primary,
                ),
              ),
            ),
          if (allList.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.delete_sweep_outlined, color: AppColors.textSecondary),
              tooltip: 'مسح الكل',
              onPressed: () async {
                final confirm = await showDialog<bool>(
                  context: context,
                  builder: (ctx) => AlertDialog(
                    title: Text('مسح كافة الإشعارات', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                    content: Text('هل أنت متأكد من مسح جميع الإشعارات من السجل؟', style: GoogleFonts.cairo()),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        child: Text('إلغاء', style: GoogleFonts.cairo()),
                      ),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
                        onPressed: () => Navigator.pop(ctx, true),
                        child: Text('مسح الكل', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                );
                if (confirm == true) {
                  notifProv.clearAll();
                }
              },
            ),
        ],
      ),
      body: Column(
        children: [
          // Filter Tabs
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              border: Border(
                bottom: BorderSide(color: Colors.grey.withAlpha(30)),
              ),
            ),
            child: Row(
              children: [
                _buildFilterTab(
                  label: 'الكل',
                  count: allList.length,
                  isSelected: _filter == 'all',
                  onTap: () => setState(() => _filter = 'all'),
                ),
                const SizedBox(width: 8),
                _buildFilterTab(
                  label: 'غير المقروءة',
                  count: notifProv.unreadCount,
                  isSelected: _filter == 'unread',
                  isUnreadTab: true,
                  onTap: () => setState(() => _filter = 'unread'),
                ),
              ],
            ),
          ),

          // Body List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => notifProv.fetchNotifications(),
              child: notifProv.isLoading && allList.isEmpty
                  ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                  : displayedList.isEmpty
                      ? _buildEmptyState()
                      : ListView.separated(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          itemCount: displayedList.length,
                          separatorBuilder: (ctx, index) => Divider(
                            height: 1,
                            indent: 72,
                            color: Colors.grey.withAlpha(25),
                          ),
                          itemBuilder: (context, index) {
                            final item = displayedList[index];
                            return _buildNotificationItem(context, item, notifProv);
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterTab({
    required String label,
    required int count,
    required bool isSelected,
    bool isUnreadTab = false,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary
              : (isUnreadTab && count > 0 ? AppColors.danger.withAlpha(20) : Colors.transparent),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? AppColors.primary
                : (isUnreadTab && count > 0 ? AppColors.danger.withAlpha(60) : Colors.grey.withAlpha(60)),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: GoogleFonts.cairo(
                fontSize: 13,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                color: isSelected
                    ? Colors.white
                    : (isUnreadTab && count > 0 ? AppColors.danger : AppColors.textPrimary),
              ),
            ),
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(
                color: isSelected
                    ? Colors.white.withAlpha(50)
                    : (isUnreadTab ? AppColors.danger : Colors.grey.withAlpha(40)),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '$count',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: isSelected
                      ? Colors.white
                      : (isUnreadTab ? Colors.white : AppColors.textSecondary),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.primary.withAlpha(20),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.notifications_off_outlined, size: 40, color: AppColors.primary),
            ),
            const SizedBox(height: 16),
            Text(
              'لا توجد إشعارات حالياً',
              style: GoogleFonts.cairo(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            Text(
              _filter == 'unread'
                  ? 'جميع الإشعارات تم الاطلاع عليها وقراءتها.'
                  : 'ستظهر هنا كافة الإشعارات والمستجدات المالية والشكاوى فور وصولها.',
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNotificationItem(
    BuildContext context,
    NotificationModel item,
    NotificationProvider notifProv,
  ) {
    final typeColor = _getTypeColor(item.type);

    return Dismissible(
      key: Key(item.id),
      direction: DismissDirection.endToStart,
      background: Container(
        color: AppColors.danger,
        alignment: Alignment.centerLeft,
        padding: const EdgeInsets.symmetric(horizontal: 20),
        child: const Icon(Icons.delete_outline, color: Colors.white),
      ),
      onDismissed: (_) {
        notifProv.deleteNotification(item.id);
      },
      child: InkWell(
        onTap: () {
          if (!item.isRead) {
            notifProv.markAsRead(item.id);
          }
          Navigator.pop(context, item);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          color: item.isRead ? Colors.transparent : AppColors.primary.withAlpha(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Icon Avatar
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: typeColor.withAlpha(25),
                  shape: BoxShape.circle,
                  border: Border.all(color: typeColor.withAlpha(50)),
                ),
                child: Center(
                  child: Text(
                    item.icon,
                    style: const TextStyle(fontSize: 20),
                  ),
                ),
              ),
              const SizedBox(width: 12),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            item.title,
                            style: GoogleFonts.cairo(
                              fontSize: 14,
                              fontWeight: item.isRead ? FontWeight.w600 : FontWeight.bold,
                              color: AppColors.textPrimary,
                            ),
                          ),
                        ),
                        Text(
                          item.timeAgo,
                          style: GoogleFonts.cairo(
                            fontSize: 11,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.message,
                      style: GoogleFonts.cairo(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),

              // Unread Blue Dot
              if (!item.isRead)
                Padding(
                  padding: const EdgeInsets.only(right: 8, top: 4),
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
