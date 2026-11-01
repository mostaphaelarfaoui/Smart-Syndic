import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../models/message_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/message_provider.dart';
import 'compose_message_dialog.dart';
import 'message_detail_screen.dart';

class MessagesScreen extends StatefulWidget {
  const MessagesScreen({super.key});

  @override
  State<MessagesScreen> createState() => _MessagesScreenState();
}

class _MessagesScreenState extends State<MessagesScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MessageProvider>().fetchMessages();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final msgProvider = context.watch<MessageProvider>();
    final auth = context.watch<AuthProvider>();
    final isResident = auth.isResident;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Text('صندوق الرسائل', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 18)),
            if (msgProvider.unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.danger,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${msgProvider.unreadCount} جديدة',
                  style: GoogleFonts.cairo(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (msgProvider.unreadCount > 0)
            IconButton(
              icon: const Icon(Icons.done_all_rounded, size: 20),
              tooltip: 'تحديد الكل كمقروء',
              onPressed: () => msgProvider.markAllAsRead(),
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'تحديث',
            onPressed: () => msgProvider.fetchMessages(),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondary,
          labelStyle: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
          indicatorColor: AppColors.primary,
          tabs: [
            Tab(text: '📥 صندوق الوارد (${msgProvider.inbox.length})'),
            Tab(text: '📤 المرسلة (${msgProvider.sent.length})'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // Inbox Tab
          _buildMessagesList(
            messages: msgProvider.inbox,
            isLoading: msgProvider.isLoading,
            emptyMessage: 'لا توجد رسائل واردة حالياً',
            emptySubtitle: 'ستظهر هنا إعلانات الإدارة العامة ومراسلات السانديك والسكان',
            isInbox: true,
          ),

          // Sent Tab
          _buildMessagesList(
            messages: msgProvider.sent,
            isLoading: msgProvider.isLoading,
            emptyMessage: 'لم تقم بإرسال أي رسائل بعد',
            emptySubtitle: 'يمكنك إنشاء رسالة جديدة بالضغط على الزر أدناه',
            isInbox: false,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.edit_outlined, size: 20),
        label: Text(
          isResident ? 'مراسلة السانديك' : 'رسالة جديدة',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
        ),
        onPressed: () async {
          final res = await showDialog<bool>(
            context: context,
            builder: (_) => const ComposeMessageDialog(),
          );
          if (res == true && mounted) {
            if (!mounted) return;
            context.read<MessageProvider>().fetchMessages();
          }
        },
      ),
    );
  }

  Widget _buildMessagesList({
    required List<MessageModel> messages,
    required bool isLoading,
    required String emptyMessage,
    required String emptySubtitle,
    required bool isInbox,
  }) {
    if (isLoading && messages.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (messages.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.mark_email_unread_outlined, color: AppColors.primary, size: 36),
              ),
              const SizedBox(height: 16),
              Text(
                emptyMessage,
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 6),
              Text(
                emptySubtitle,
                textAlign: TextAlign.center,
                style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary, height: 1.5),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => context.read<MessageProvider>().fetchMessages(),
      child: ListView.builder(
        padding: const EdgeInsets.only(top: 8, bottom: 80, left: 12, right: 12),
        itemCount: messages.length,
        itemBuilder: (context, index) {
          final msg = messages[index];
          final isUnread = isInbox && !msg.isRead;
          final isAdmin = msg.isFromSuperAdmin;

          return Card(
            margin: const EdgeInsets.symmetric(vertical: 4),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: BorderSide(
                color: isUnread
                    ? AppColors.primary.withAlpha(100)
                    : (isAdmin ? const Color(0xff6366f1).withAlpha(60) : AppColors.border),
                width: isUnread ? 1.5 : 1,
              ),
            ),
            elevation: isUnread ? 2 : 0,
            color: isUnread
                ? AppColors.primary.withAlpha(10)
                : (isAdmin ? const Color(0xff6366f1).withAlpha(10) : AppColors.surface),
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MessageDetailScreen(message: msg)),
                );
              },
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Avatar icon
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: !isInbox
                            ? const Color(0xff6366f1).withAlpha(20)
                            : (isAdmin
                                ? const Color(0xff6366f1).withAlpha(25)
                                : (msg.isFromResident
                                    ? AppColors.success.withAlpha(25)
                                    : AppColors.primary.withAlpha(25))),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Text(
                          !isInbox
                              ? '📤'
                              : (isAdmin ? '🛡️' : (msg.isFromResident ? '👤' : '🏢')),
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
                                child: Row(
                                  children: [
                                    Flexible(
                                      child: Text(
                                        isInbox
                                            ? 'من: ${msg.senderDisplayName}'
                                            : 'إلى: ${msg.recipientLabel}',
                                        style: GoogleFonts.cairo(
                                          fontWeight: isUnread ? FontWeight.w900 : FontWeight.bold,
                                          fontSize: 13,
                                          color: isInbox ? AppColors.textPrimary : const Color(0xff4f46e5),
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (isInbox && msg.senderUnitLabel != null) ...[
                                      const SizedBox(width: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                        decoration: BoxDecoration(
                                          color: AppColors.surface,
                                          borderRadius: BorderRadius.circular(4),
                                          border: Border.all(color: AppColors.border),
                                        ),
                                        child: Text(
                                          msg.senderUnitLabel!,
                                          style: GoogleFonts.cairo(
                                            fontSize: 9,
                                            fontWeight: FontWeight.bold,
                                            color: AppColors.textPrimary,
                                          ),
                                        ),
                                      ),
                                    ],
                                    if (isAdmin) ...[
                                      const SizedBox(width: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                        decoration: BoxDecoration(
                                          color: const Color(0xff6366f1).withAlpha(30),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          'إعلان رسمي',
                                          style: GoogleFonts.cairo(
                                            fontSize: 9,
                                            fontWeight: FontWeight.bold,
                                            color: const Color(0xff6366f1),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                '${msg.createdAt.year}/${msg.createdAt.month}/${msg.createdAt.day}',
                                style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            msg.subject,
                            style: GoogleFonts.cairo(
                              fontWeight: isUnread ? FontWeight.bold : FontWeight.w600,
                              fontSize: 13,
                              color: isUnread ? AppColors.primary : AppColors.textPrimary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 2),
                          Text(
                            msg.body,
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary, height: 1.4),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 6),

                          // Clean Category Badge & Replies Count
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1.5),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withAlpha(15),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  msg.categoryLabel,
                                  style: GoogleFonts.cairo(
                                    fontSize: 9.5,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                              if (msg.replies.isNotEmpty) ...[
                                const SizedBox(width: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                  decoration: BoxDecoration(
                                    color: const Color(0xff10b981).withAlpha(15),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(Icons.chat_bubble_outline_rounded, size: 10, color: Color(0xff10b981)),
                                      const SizedBox(width: 3),
                                      Text(
                                        '${msg.replies.length} ردود',
                                        style: GoogleFonts.cairo(
                                          fontSize: 9.5,
                                          fontWeight: FontWeight.bold,
                                          color: const Color(0xff10b981),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),

                    // Unread dot
                    if (isUnread) ...[
                      const SizedBox(width: 8),
                      Container(
                        width: 8,
                        height: 8,
                        decoration: const BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
