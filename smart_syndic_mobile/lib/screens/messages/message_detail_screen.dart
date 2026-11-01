import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../models/message_model.dart';
import '../../providers/message_provider.dart';

class MessageDetailScreen extends StatefulWidget {
  final MessageModel message;

  const MessageDetailScreen({super.key, required this.message});

  @override
  State<MessageDetailScreen> createState() => _MessageDetailScreenState();
}

class _MessageDetailScreenState extends State<MessageDetailScreen> {
  final _replyController = TextEditingController();
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
    super.dispose();
  }

  Future<void> _refreshDetails() async {
    final updated = await context.read<MessageProvider>().fetchMessageDetails(_currentMessage.id);
    if (updated != null && mounted) {
      setState(() => _currentMessage = updated);
    }
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(backgroundColor: AppColors.success, content: Text('تم إرسال الرد بنجاح ✓')),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final msg = _currentMessage;
    final isSuperAdmin = msg.isFromSuperAdmin;

    return Scaffold(
      appBar: AppBar(
        title: Text('تفاصيل الرسالة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Sender Card
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: isSuperAdmin ? const Color(0xff6366f1).withAlpha(15) : AppColors.surface,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: isSuperAdmin ? const Color(0xff6366f1).withAlpha(60) : AppColors.border,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Row 1: Sender (من)
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(
                                color: isSuperAdmin
                                    ? const Color(0xff6366f1).withAlpha(25)
                                    : (msg.isFromResident
                                        ? AppColors.success.withAlpha(25)
                                        : AppColors.primary.withAlpha(25)),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Center(
                                child: Text(
                                  isSuperAdmin ? '🛡️' : (msg.isFromResident ? '👤' : '🏢'),
                                  style: const TextStyle(fontSize: 18),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(
                                        'من (المرسل): ',
                                        style: GoogleFonts.cairo(
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                      Expanded(
                                        child: Text(
                                          msg.senderDisplayName,
                                          style: GoogleFonts.cairo(
                                            fontSize: 13,
                                            fontWeight: FontWeight.bold,
                                            color: AppColors.textPrimary,
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 2),
                                  Wrap(
                                    spacing: 6,
                                    runSpacing: 4,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                        decoration: BoxDecoration(
                                          color: isSuperAdmin
                                              ? const Color(0xff6366f1).withAlpha(25)
                                              : AppColors.primary.withAlpha(20),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          msg.senderRoleLabel,
                                          style: GoogleFonts.cairo(
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                            color: isSuperAdmin ? const Color(0xff6366f1) : AppColors.primary,
                                          ),
                                        ),
                                      ),
                                      if (msg.senderUnitLabel != null)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                          decoration: BoxDecoration(
                                            color: AppColors.background,
                                            borderRadius: BorderRadius.circular(4),
                                            border: Border.all(color: AppColors.border),
                                          ),
                                          child: Text(
                                            msg.senderUnitLabel!,
                                            style: GoogleFonts.cairo(
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold,
                                              color: AppColors.textPrimary,
                                            ),
                                          ),
                                        ),
                                      if (msg.buildingName != null)
                                        Text(
                                          '• عمارة ${msg.buildingName}',
                                          style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                                        ),
                                      if (msg.residentPhone != null && msg.residentPhone!.isNotEmpty)
                                        Text(
                                          '• 📞 ${msg.residentPhone}',
                                          style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                                        ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),

                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 10),
                          child: Divider(height: 1),
                        ),

                        // Row 2: Recipient (إلى)
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(
                                color: const Color(0xff6366f1).withAlpha(20),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Center(
                                child: Text('🎯', style: TextStyle(fontSize: 18)),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(
                                        'إلى (المرسل إليه): ',
                                        style: GoogleFonts.cairo(
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                      Expanded(
                                        child: Text(
                                          msg.recipientLabel,
                                          style: GoogleFonts.cairo(
                                            fontSize: 13,
                                            fontWeight: FontWeight.bold,
                                            color: const Color(0xff4f46e5),
                                          ),
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '🕒 ${msg.createdAt.year}/${msg.createdAt.month}/${msg.createdAt.day} - ${msg.createdAt.hour.toString().padLeft(2, '0')}:${msg.createdAt.minute.toString().padLeft(2, '0')}',
                                    style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Badges: Category & Priority
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withAlpha(20),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          msg.categoryLabel,
                          style: GoogleFonts.cairo(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      if (msg.priority == 'URGENT') ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.danger.withAlpha(20),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            '🚨 عاجل جداً',
                            style: GoogleFonts.cairo(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: AppColors.danger,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Subject
                  Text(
                    msg.subject,
                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 18, height: 1.4),
                  ),
                  const SizedBox(height: 14),

                  // Body
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Text(
                      msg.body,
                      style: GoogleFonts.cairo(fontSize: 14, height: 1.7, color: AppColors.textPrimary),
                    ),
                  ),

                  // Replies / Threads List
                  if (msg.replies.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'الردود والمتابعة (${msg.replies.length})',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                    const SizedBox(height: 10),
                    ...msg.replies.map((reply) => Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: reply.isFromSyndic
                                ? AppColors.primary.withAlpha(15)
                                : AppColors.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    reply.senderName,
                                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12),
                                  ),
                                  Text(
                                    '${reply.createdAt.hour}:${reply.createdAt.minute.toString().padLeft(2, '0')}',
                                    style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                reply.body,
                                style: GoogleFonts.cairo(fontSize: 13, height: 1.5),
                              ),
                            ],
                          ),
                        )),
                  ],
                ],
              ),
            ),
          ),

          // Bottom Reply Bar (disabled for official Super Admin announcements)
          if (!isSuperAdmin)
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
            ),
        ],
      ),
    );
  }
}
