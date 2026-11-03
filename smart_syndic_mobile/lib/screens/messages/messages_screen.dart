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

class _MessagesScreenState extends State<MessagesScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedFilter = 'all'; // 'all', 'unread', 'admin', 'resident'

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MessageProvider>().fetchMessages();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _formatTime(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inDays == 0 && dt.day == now.day) {
      final hour = dt.hour.toString().padLeft(2, '0');
      final minute = dt.minute.toString().padLeft(2, '0');
      return '$hour:$minute';
    } else if (diff.inDays == 1 || (diff.inDays == 0 && dt.day != now.day)) {
      return 'أمس';
    } else if (diff.inDays < 7) {
      const days = ['الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];
      return days[dt.weekday - 1];
    }
    return '${dt.year}/${dt.month}/${dt.day}';
  }

  List<MessageModel> _filterConversations(List<MessageModel> list, String? currentUserId) {
    return list.where((msg) {
      if (_selectedFilter == 'unread' && msg.isRead) return false;
      if (_selectedFilter == 'admin' && !msg.isFromSuperAdmin) return false;
      if (_selectedFilter == 'resident' && !msg.isFromResident) return false;

      if (_searchQuery.trim().isNotEmpty) {
        final q = _searchQuery.trim().toLowerCase();
        final partner = msg.chatPartnerTitle(currentUserId).toLowerCase();
        final badge = (msg.chatPartnerBadge(currentUserId) ?? '').toLowerCase();
        final body = msg.latestMessageBody.toLowerCase();
        final subject = msg.subject.toLowerCase();
        if (!partner.contains(q) && !badge.contains(q) && !body.contains(q) && !subject.contains(q)) {
          return false;
        }
      }
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final msgProvider = context.watch<MessageProvider>();
    final auth = context.watch<AuthProvider>();
    final isResident = auth.isResident;
    final currentUserId = auth.user?.id;

    final filteredConversations = _filterConversations(msgProvider.conversations, currentUserId);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        title: Row(
          children: [
            Text(
              'الرسائل',
              style: GoogleFonts.cairo(
                fontWeight: FontWeight.w900,
                fontSize: 19,
                color: AppColors.textPrimary,
              ),
            ),
            if (msgProvider.unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xff6366f1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${msgProvider.unreadCount} جديدة',
                  style: GoogleFonts.cairo(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (msgProvider.unreadCount > 0)
            IconButton(
              icon: const Icon(Icons.done_all_rounded, size: 21, color: Color(0xff6366f1)),
              tooltip: 'تحديد الكل كمقروء',
              onPressed: () => msgProvider.markAllAsRead(),
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppColors.textSecondary),
            tooltip: 'تحديث',
            onPressed: () => msgProvider.fetchMessages(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Instagram Style Search Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            child: Container(
              height: 42,
              decoration: BoxDecoration(
                color: const Color(0xfff1f5f9),
                borderRadius: BorderRadius.circular(22),
                border: Border.all(color: const Color(0xffe2e8f0)),
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (v) => setState(() => _searchQuery = v),
                style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textPrimary),
                decoration: InputDecoration(
                  hintText: 'بحث في المحادثات أو السكان...',
                  hintStyle: GoogleFonts.cairo(fontSize: 12.5, color: const Color(0xff94a3b8)),
                  prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xff94a3b8)),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.close_rounded, size: 18, color: Color(0xff94a3b8)),
                          onPressed: () {
                            _searchController.clear();
                            setState(() => _searchQuery = '');
                          },
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 9),
                ),
              ),
            ),
          ),

          // Filter Pills
          Container(
            color: Colors.white,
            padding: const EdgeInsets.only(bottom: 8, left: 14, right: 14),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildFilterChip('الكل', 'all', msgProvider.conversations.length),
                  const SizedBox(width: 8),
                  _buildFilterChip('غير مقروءة', 'unread', msgProvider.unreadCount),
                  const SizedBox(width: 8),
                  _buildFilterChip(
                    '🛡️ الإعلانات',
                    'admin',
                    msgProvider.conversations.where((m) => m.isFromSuperAdmin).length,
                  ),
                  const SizedBox(width: 8),
                  _buildFilterChip(
                    '👤 السكان',
                    'resident',
                    msgProvider.conversations.where((m) => m.isFromResident).length,
                  ),
                ],
              ),
            ),
          ),

          const Divider(height: 1, thickness: 1, color: Color(0xffe2e8f0)),

          // Unified Conversations List
          Expanded(
            child: _buildConversationsList(
              conversations: filteredConversations,
              isLoading: msgProvider.isLoading,
              currentUserId: currentUserId,
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: const Color(0xff4f46e5),
        foregroundColor: Colors.white,
        elevation: 3,
        icon: const Icon(Icons.edit_outlined, size: 19),
        label: Text(
          isResident ? 'مراسلة السانديك' : 'رسالة جديدة',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
        ),
        onPressed: () async {
          final provider = context.read<MessageProvider>();
          final res = await showDialog<bool>(
            context: context,
            builder: (_) => const ComposeMessageDialog(),
          );
          if (res == true && mounted) {
            provider.fetchMessages();
          }
        },
      ),
    );
  }

  Widget _buildFilterChip(String label, String key, int count) {
    final isSelected = _selectedFilter == key;
    return InkWell(
      onTap: () => setState(() => _selectedFilter = key),
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xff4f46e5) : const Color(0xfff8fafc),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: isSelected ? const Color(0xff4f46e5) : const Color(0xffe2e8f0),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: GoogleFonts.cairo(
                fontSize: 11.5,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                color: isSelected ? Colors.white : AppColors.textSecondary,
              ),
            ),
            if (count > 0) ...[
              const SizedBox(width: 5),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected ? Colors.white.withAlpha(50) : const Color(0xffe2e8f0),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$count',
                  style: GoogleFonts.cairo(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: isSelected ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildConversationsList({
    required List<MessageModel> conversations,
    required bool isLoading,
    required String? currentUserId,
  }) {
    if (isLoading && conversations.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (conversations.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  color: const Color(0xff4f46e5).withAlpha(15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xff4f46e5), size: 38),
              ),
              const SizedBox(height: 16),
              Text(
                _selectedFilter == 'unread'
                    ? 'لا توجد رسائل غير مقروءة'
                    : (_searchQuery.isNotEmpty ? 'لا توجد نتائج مطابقة لبحثك' : 'لا توجد محادثات حالياً'),
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 6),
              Text(
                _selectedFilter == 'unread'
                    ? 'تمت قراءة جميع المحادثات.'
                    : 'ابدأ محادثة جديدة للتواصل مع الإدارة أو السكان.',
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
      child: ListView.separated(
        padding: const EdgeInsets.only(top: 4, bottom: 85),
        itemCount: conversations.length,
        separatorBuilder: (context, index) => const Divider(height: 1, indent: 72, endIndent: 16, color: Color(0xfff1f5f9)),
        itemBuilder: (context, index) {
          final msg = conversations[index];
          final isUnread = !msg.isRead;
          final isAdmin = msg.isFromSuperAdmin;
          final partnerName = msg.chatPartnerTitle(currentUserId);
          final partnerBadge = msg.chatPartnerBadge(currentUserId);
          final timeStr = _formatTime(msg.latestActivityAt);

          return Material(
            color: isUnread ? const Color(0xff4f46e5).withAlpha(8) : Colors.white,
            child: InkWell(
              onTap: () async {
                final provider = context.read<MessageProvider>();
                await Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MessageDetailScreen(message: msg)),
                );
                if (mounted) {
                  // Silent refresh to update read status immediately
                  provider.fetchMessages(silent: true);
                }
              },
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Avatar Icon
                    Stack(
                      clipBehavior: Clip.none,
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            gradient: isAdmin
                                ? const LinearGradient(
                                    colors: [Color(0xff4f46e5), Color(0xff7c3aed)],
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                  )
                                : (msg.isFromResident
                                    ? const LinearGradient(
                                        colors: [Color(0xff059669), Color(0xff10b981)],
                                        begin: Alignment.topLeft,
                                        end: Alignment.bottomRight,
                                      )
                                    : const LinearGradient(
                                        colors: [Color(0xff0284c7), Color(0xff38bdf8)],
                                        begin: Alignment.topLeft,
                                        end: Alignment.bottomRight,
                                      )),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: (isAdmin
                                        ? const Color(0xff4f46e5)
                                        : (msg.isFromResident ? const Color(0xff10b981) : const Color(0xff0284c7)))
                                    .withAlpha(40),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Center(
                            child: Icon(
                              isAdmin
                                  ? Icons.verified_user_rounded
                                  : (msg.isFromResident ? Icons.person_rounded : Icons.apartment_rounded),
                              color: Colors.white,
                              size: 24,
                            ),
                          ),
                        ),
                        if (msg.replies.isNotEmpty)
                          Positioned(
                            bottom: -2,
                            right: -2,
                            child: Container(
                              padding: const EdgeInsets.all(3),
                              decoration: const BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                              ),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                decoration: BoxDecoration(
                                  color: const Color(0xff10b981),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '${msg.replies.length}',
                                  style: GoogleFonts.cairo(
                                    color: Colors.white,
                                    fontSize: 8.5,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(width: 12),

                    // Partner Name, Badge, Last Message
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Row(
                                  children: [
                                    Flexible(
                                      child: Text(
                                        partnerName,
                                        style: GoogleFonts.cairo(
                                          fontWeight: isUnread ? FontWeight.w900 : FontWeight.w600,
                                          fontSize: isUnread ? 14.5 : 13.5,
                                          color: AppColors.textPrimary,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (partnerBadge != null) ...[
                                      const SizedBox(width: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                        decoration: BoxDecoration(
                                          color: isAdmin
                                              ? const Color(0xff6366f1).withAlpha(20)
                                              : const Color(0xfff1f5f9),
                                          borderRadius: BorderRadius.circular(6),
                                          border: Border.all(
                                            color: isAdmin
                                                ? const Color(0xff6366f1).withAlpha(40)
                                                : const Color(0xffe2e8f0),
                                          ),
                                        ),
                                        child: Text(
                                          partnerBadge,
                                          style: GoogleFonts.cairo(
                                            fontSize: 9.5,
                                            fontWeight: FontWeight.bold,
                                            color: isAdmin ? const Color(0xff4f46e5) : AppColors.textPrimary,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                timeStr,
                                style: GoogleFonts.cairo(
                                  fontSize: 10.5,
                                  fontWeight: isUnread ? FontWeight.bold : FontWeight.normal,
                                  color: isUnread ? const Color(0xff4f46e5) : AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),

                          // Last Message Preview & Unread Dot
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  msg.lastMessagePreview(currentUserId),
                                  style: GoogleFonts.cairo(
                                    fontWeight: isUnread ? FontWeight.bold : FontWeight.normal,
                                    fontSize: isUnread ? 12.5 : 12,
                                    color: isUnread ? AppColors.textPrimary : const Color(0xff64748b),
                                    height: 1.35,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (isUnread) ...[
                                const SizedBox(width: 8),
                                Container(
                                  width: 9,
                                  height: 9,
                                  decoration: const BoxDecoration(
                                    color: Color(0xff4f46e5),
                                    shape: BoxShape.circle,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),
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
