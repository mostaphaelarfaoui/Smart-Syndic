import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../models/message_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/message_provider.dart';
import '../../widgets/app_alert.dart';

class MessageDetailScreen extends StatefulWidget {
  final MessageModel message;

  const MessageDetailScreen({super.key, required this.message});

  @override
  State<MessageDetailScreen> createState() => _MessageDetailScreenState();
}

class _MessageDetailScreenState extends State<MessageDetailScreen> {
  final _replyController = TextEditingController();
  final _scrollController = ScrollController();
  late MessageModel _currentMessage;
  bool _isSendingReply = false;

  @override
  void initState() {
    super.initState();
    _currentMessage = widget.message;
    _refreshDetails();
  }

  @override
  void dispose() {
    _replyController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _refreshDetails() async {
    final updated = await context.read<MessageProvider>().fetchMessageDetails(_currentMessage.id);
    if (updated != null && mounted) {
      setState(() => _currentMessage = updated);
      _scrollToBottom();
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendReply() async {
    final text = _replyController.text.trim();
    if (text.isEmpty) return;

    setState(() => _isSendingReply = true);
    final ok = await context.read<MessageProvider>().sendReply(
      messageId: _currentMessage.id,
      body: text,
    );

    if (mounted) {
      setState(() => _isSendingReply = false);
      if (ok) {
        _replyController.clear();
        await _refreshDetails();
        if (mounted) {
          AppAlert.success(context, 'تم إرسال الرد بنجاح ✓');
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final currentUserId = auth.user?.id;
    final msg = _currentMessage;
    final isSuperAdmin = msg.isFromSuperAdmin;
    final isSentByMe = currentUserId != null && msg.senderId == currentUserId;
    final canReply = !isSuperAdmin && (!isSentByMe || msg.replies.isNotEmpty);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isSentByMe ? 'رسالة صادرة' : 'رسالة واردة',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
      body: Column(
        children: [
          // 1. Clean Top Header: shows ONLY recipient (if sent) or sender (if inbox)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: isSentByMe
                  ? const Color(0xff6366f1).withAlpha(12)
                  : (isSuperAdmin ? const Color(0xff6366f1).withAlpha(15) : AppColors.surface),
              border: Border(bottom: BorderSide(color: AppColors.border)),
            ),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: isSentByMe
                        ? const Color(0xff6366f1).withAlpha(20)
                        : (isSuperAdmin
                            ? const Color(0xff6366f1).withAlpha(25)
                            : (msg.isFromResident
                                ? AppColors.success.withAlpha(25)
                                : AppColors.primary.withAlpha(25))),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Center(
                    child: Text(
                      isSentByMe
                          ? '📤'
                          : (isSuperAdmin ? '🛡️' : (msg.isFromResident ? '👤' : '🏢')),
                      style: const TextStyle(fontSize: 18),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              isSentByMe
                                  ? 'إلى: ${msg.recipientLabel}'
                                  : 'من: ${msg.senderDisplayName}',
                              style: GoogleFonts.cairo(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: isSentByMe ? const Color(0xff4f46e5) : AppColors.textPrimary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (!isSentByMe && msg.senderUnitLabel != null) ...[
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
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Text(
                            '${msg.createdAt.year}/${msg.createdAt.month}/${msg.createdAt.day}',
                            style: GoogleFonts.cairo(fontSize: 10.5, color: AppColors.textSecondary),
                          ),
                          const SizedBox(width: 8),
                          Text('•', style: TextStyle(color: AppColors.textSecondary, fontSize: 10)),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 0.5),
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
                          if (msg.priority == 'URGENT') ...[
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 0.5),
                              decoration: BoxDecoration(
                                color: AppColors.danger.withAlpha(15),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                '🚨 عاجل',
                                style: GoogleFonts.cairo(
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.danger,
                                ),
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

          // 2. Chat Conversation View (الدردشة)
          Expanded(
            child: ListView(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
              children: [
                // (A) Initial Original Message Bubble
                _buildChatBubble(
                  isMe: isSentByMe,
                  senderTitle: isSentByMe ? 'أنت' : msg.senderDisplayName,
                  unitBadge: !isSentByMe ? msg.senderUnitLabel : null,
                  time: msg.createdAt,
                  body: msg.body,
                  isInitial: true,
                ),

                // (B) Thread Replies as Chat Bubbles
                if (msg.replies.isNotEmpty) ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    child: Row(
                      children: [
                        const Expanded(child: Divider()),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 10),
                          child: Text(
                            'المحادثة والردود (${msg.replies.length})',
                            style: GoogleFonts.cairo(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                        const Expanded(child: Divider()),
                      ],
                    ),
                  ),
                  ...msg.replies.map((reply) {
                    final bool isReplyMe = currentUserId != null && reply.senderId == currentUserId;
                    return _buildChatBubble(
                      isMe: isReplyMe,
                      senderTitle: isReplyMe ? 'أنت' : reply.senderDisplayName,
                      unitBadge: !isReplyMe ? reply.senderUnitLabel : null,
                      time: reply.createdAt,
                      body: reply.body,
                      isInitial: false,
                    );
                  }),
                ],
              ],
            ),
          ),

          // 3. Bottom Action Bar
          if (canReply)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _replyController,
                        decoration: InputDecoration(
                          hintText: 'اكتب ردك هنا...',
                          hintStyle: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      icon: _isSendingReply
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.send_rounded, color: AppColors.primary),
                      onPressed: _isSendingReply ? null : _sendReply,
                    ),
                  ],
                ),
              ),
            )
          else if (isSentByMe && msg.replies.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.surface,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: SafeArea(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.check_circle_outline_rounded, size: 16, color: Color(0xff10b981)),
                    const SizedBox(width: 6),
                    Text(
                      'تم إرسال هذه الرسالة بنجاح إلى: ${msg.recipientLabel}',
                      style: GoogleFonts.cairo(fontSize: 11.5, color: AppColors.textSecondary, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            )
          else if (isSuperAdmin)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xff6366f1).withAlpha(12),
                border: Border(top: BorderSide(color: const Color(0xff6366f1).withAlpha(30))),
              ),
              child: SafeArea(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('ℹ️ ', style: TextStyle(fontSize: 13)),
                    Text(
                      'إعلان رسمي من الإدارة العامة للمنصة',
                      style: GoogleFonts.cairo(fontSize: 11, color: const Color(0xff4f46e5), fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildChatBubble({
    required bool isMe,
    required String senderTitle,
    String? unitBadge,
    required DateTime time,
    required String body,
    required bool isInitial,
  }) {
    return Align(
      alignment: isMe ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.82,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isMe
              ? const Color(0xff4f46e5).withAlpha(22)
              : AppColors.surface,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(14),
            topRight: const Radius.circular(14),
            bottomLeft: Radius.circular(isMe ? 14 : 3),
            bottomRight: Radius.circular(isMe ? 3 : 14),
          ),
          border: Border.all(
            color: isMe
                ? const Color(0xff4f46e5).withAlpha(60)
                : AppColors.border,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Bubble Header: Sender name + unit + time
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  senderTitle,
                  style: GoogleFonts.cairo(
                    fontWeight: FontWeight.bold,
                    fontSize: 11.5,
                    color: isMe ? const Color(0xff4f46e5) : AppColors.textPrimary,
                  ),
                ),
                if (unitBadge != null) ...[
                  const SizedBox(width: 5),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 0.5),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(3),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Text(
                      unitBadge,
                      style: GoogleFonts.cairo(fontSize: 9, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
                const SizedBox(width: 8),
                Text(
                  '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}',
                  style: GoogleFonts.cairo(fontSize: 9.5, color: AppColors.textSecondary),
                ),
              ],
            ),
            const SizedBox(height: 4),

            // Body
            Text(
              body,
              style: GoogleFonts.cairo(
                fontSize: 13,
                height: 1.5,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
