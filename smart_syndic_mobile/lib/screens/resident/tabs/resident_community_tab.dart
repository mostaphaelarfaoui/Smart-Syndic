import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/resident_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/submit_suggestion_dialog.dart';

class ResidentCommunityTab extends StatefulWidget {
  const ResidentCommunityTab({super.key});

  @override
  State<ResidentCommunityTab> createState() => _ResidentCommunityTabState();
}

class _ResidentCommunityTabState extends State<ResidentCommunityTab> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final Map<String, String> _selectedOptions = {}; // pollId -> optionId
  final Map<String, bool> _votingProgress = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final resident = context.watch<ResidentProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: PreferredSize(
        preferredSize: const Size.fromHeight(48),
        child: Container(
          color: Colors.white,
          child: TabBar(
            controller: _tabController,
            indicatorColor: AppColors.primary,
            labelColor: AppColors.primary,
            unselectedLabelColor: AppColors.textSecondary,
            labelStyle: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
            unselectedLabelStyle: GoogleFonts.cairo(fontSize: 13),
            tabs: const [
              Tab(text: 'استطلاعات الرأي والتصويت'),
              Tab(text: 'اقتراحاتي وملاحظاتي'),
            ],
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const SubmitSuggestionDialog(),
          );
        },
        icon: const Icon(Icons.add_comment_outlined),
        label: Text('تقديم فكرة جديدة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // 1. Polls & Voting View
          _buildPollsView(context, resident),

          // 2. Suggestions View
          _buildSuggestionsView(context, resident),
        ],
      ),
    );
  }

  Widget _buildPollsView(BuildContext context, ResidentProvider resident) {
    final polls = resident.polls;

    return RefreshIndicator(
      onRefresh: () async => await resident.fetchPolls(),
      child: polls.isEmpty
          ? const EmptyStateWidget(
              icon: Icons.how_to_vote_outlined,
              title: 'لا توجد استطلاعات رأي نشطة حالياً',
              description: 'عندما يطرح السانديك تصويتاً جديداً للمصادقة على قرار يهم الإقامة، سيظهر هنا للمشاركة برأيك',
            )
          : ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
              itemCount: polls.length,
              itemBuilder: (context, index) {
                final poll = polls[index];
                final total = poll.totalVotes;
                final hasVoted = poll.hasVoted;
                final isSubmitting = _votingProgress[poll.id] == true;

                return Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              poll.title,
                              style: GoogleFonts.cairo(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: hasVoted ? AppColors.success.withAlpha(20) : AppColors.primary.withAlpha(20),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              hasVoted ? 'صوتك مسجل ✓' : 'مفتوح للتصويت',
                              style: GoogleFonts.cairo(
                                fontSize: 11,
                                color: hasVoted ? AppColors.success : AppColors.primary,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (poll.description != null && poll.description!.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(
                          poll.description!,
                          style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
                        ),
                      ],
                      const SizedBox(height: 16),

                      // If resident already voted, show the progress results
                      if (hasVoted) ...[
                        ...poll.options.map((opt) {
                          final pct = total > 0 ? (opt.votesCount / total) : 0.0;
                          final isUserChoice = poll.userVotedOptionId == opt.id;

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Row(
                                      children: [
                                        if (isUserChoice) ...[
                                          const Icon(Icons.check_circle, size: 16, color: AppColors.primary),
                                          const SizedBox(width: 6),
                                        ],
                                        Text(
                                          opt.optionText,
                                          style: GoogleFonts.cairo(
                                            fontSize: 13,
                                            fontWeight: isUserChoice ? FontWeight.bold : FontWeight.w500,
                                            color: isUserChoice ? AppColors.primary : AppColors.textPrimary,
                                          ),
                                        ),
                                      ],
                                    ),
                                    Text(
                                      '${(pct * 100).toStringAsFixed(0)}% (${opt.votesCount})',
                                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(6),
                                  child: LinearProgressIndicator(
                                    value: pct,
                                    minHeight: 8,
                                    backgroundColor: AppColors.border,
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                      isUserChoice ? AppColors.primary : AppColors.secondary,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }),
                      ] else ...[
                        // Interactive voting radio list
                        ...poll.options.map((opt) {
                          final isSelected = _selectedOptions[poll.id] == opt.id;

                          return InkWell(
                            onTap: () {
                              setState(() {
                                _selectedOptions[poll.id] = opt.id;
                              });
                            },
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: isSelected ? AppColors.primary.withAlpha(15) : AppColors.background,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: isSelected ? AppColors.primary : AppColors.border,
                                  width: isSelected ? 1.5 : 1,
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    isSelected ? Icons.radio_button_checked : Icons.radio_button_off,
                                    color: isSelected ? AppColors.primary : AppColors.textLight,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      opt.optionText,
                                      style: GoogleFonts.cairo(
                                        fontSize: 13,
                                        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                        color: isSelected ? AppColors.primary : AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }),
                        const SizedBox(height: 12),
                        ElevatedButton(
                          onPressed: (_selectedOptions[poll.id] == null || isSubmitting)
                              ? null
                              : () async {
                                  setState(() => _votingProgress[poll.id] = true);
                                  final optionId = _selectedOptions[poll.id]!;
                                  final ok = await resident.castVote(poll.id, optionId);
                                  if (mounted) {
                                    setState(() => _votingProgress[poll.id] = false);
                                    if (ok) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(
                                          backgroundColor: AppColors.success,
                                          content: Text('تم تسجيل صوتك بنجاح ✓'),
                                        ),
                                      );
                                    }
                                  }
                                },
                          child: isSubmitting
                              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : Text('تأكيد صوتي في هذا الاستطلاع', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                        ),
                      ],

                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'إجمالي الأصوات: $total',
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                          ),
                          Text(
                            'تاريخ الإغلاق: ${poll.expiresAt}',
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
    );
  }

  Widget _buildSuggestionsView(BuildContext context, ResidentProvider resident) {
    final suggestions = resident.mySuggestions;

    return RefreshIndicator(
      onRefresh: () async {
        await resident.fetchMySuggestions();
      },
      child: suggestions.isEmpty
          ? EmptyStateWidget(
              icon: Icons.lightbulb_outline,
              title: 'لم تقم بتقديم أي اقتراح بعد',
              description: 'هل لديك فكرة أو ملاحظة لتحسين الإقامة؟ شاركها مع السانديك بكل بساطة وسرية',
              buttonText: 'تقديم أول فكرة',
              onButtonPressed: () {
                showDialog(
                  context: context,
                  builder: (_) => const SubmitSuggestionDialog(),
                );
              },
            )
          : ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
              itemCount: suggestions.length,
              itemBuilder: (context, index) {
                final item = suggestions[index];

                return Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
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
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withAlpha(20),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              item.statusArabic,
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        item.description,
                        style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
                      ),
                      if (item.adminNotes != null && item.adminNotes!.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.background,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.comment_outlined, size: 16, color: AppColors.primary),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'رد السانديك: ${item.adminNotes}',
                                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.primaryDark),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                );
              },
            ),
    );
  }
}
