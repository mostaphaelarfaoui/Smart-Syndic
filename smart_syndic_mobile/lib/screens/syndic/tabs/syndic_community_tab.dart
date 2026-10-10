import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/convert_to_poll_dialog.dart';
import '../dialogs/create_poll_dialog.dart';

class SyndicCommunityTab extends StatefulWidget {
  const SyndicCommunityTab({super.key});

  @override
  State<SyndicCommunityTab> createState() => _SyndicCommunityTabState();
}

class _SyndicCommunityTabState extends State<SyndicCommunityTab> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final pendingCount = syndic.pendingResidents.length;

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
            tabs: [
              Tab(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('طلبات الانضمام'),
                    if (pendingCount > 0) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.danger,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          '$pendingCount',
                          style: GoogleFonts.cairo(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const Tab(text: 'صندوق الاقتراحات'),
              const Tab(text: 'استطلاعات الرأي'),
            ],
          ),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // 1. Pending Residents View
          _buildPendingResidentsView(context, syndic),

          // 2. Suggestions View
          _buildSuggestionsView(context, syndic),

          // 3. Polls View
          _buildPollsView(context, syndic),
        ],
      ),
      floatingActionButton: _tabController.index == 2
          ? FloatingActionButton.extended(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => const CreatePollDialog(),
                );
              },
              backgroundColor: AppColors.primary,
              icon: const Icon(Icons.add, color: Colors.white),
              label: Text('تصويت جديد', style: GoogleFonts.cairo(color: Colors.white, fontWeight: FontWeight.bold)),
            )
          : null,
    );
  }

  Widget _buildPendingResidentsView(BuildContext context, SyndicProvider syndic) {
    final pending = syndic.pendingResidents;

    return RefreshIndicator(
      onRefresh: () async => await syndic.fetchPendingResidents(),
      child: pending.isEmpty
          ? const EmptyStateWidget(
              icon: Icons.check_circle_outline,
              title: 'لا توجد طلبات انضمام معلقة',
              description: 'جميع طلبات سكان العمارة تمت مراجعتها والموافقة عليها',
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: pending.length,
              itemBuilder: (context, index) {
                final item = pending[index];
                final residentId = item['id']?.toString() ?? '';
                final name = '${item['first_name'] ?? ''} ${item['last_name'] ?? ''}'.trim();
                final phone = item['phone'] ?? '';
                final email = item['email'] ?? '';
                final unit = item['apartment']?['unit_number'] ?? '-';
                final occupancy = item['pivot']?['occupancy_type'] == 'TENANT' ? 'مكتري' : 'مالك';

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
                          Row(
                            children: [
                              CircleAvatar(
                                backgroundColor: AppColors.primary.withAlpha(25),
                                child: Text(
                                  name.isNotEmpty ? name[0] : 'س',
                                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primary),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    name,
                                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 15),
                                  ),
                                  Text(
                                    'شقة $unit ($occupancy)',
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: AppColors.warning.withAlpha(25),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              'قيد التحقق',
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.warning, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(Icons.phone, size: 14, color: AppColors.textLight),
                          const SizedBox(width: 6),
                          Text(phone, style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
                          const SizedBox(width: 16),
                          const Icon(Icons.email, size: 14, color: AppColors.textLight),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              email,
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const Divider(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.success,
                                padding: const EdgeInsets.symmetric(vertical: 10),
                              ),
                              onPressed: () async {
                                final ok = await syndic.approveResident(residentId);
                                if (context.mounted && ok) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      backgroundColor: AppColors.success,
                                      content: Text('تمت الموافقة على الساكن وتفعيل حسابه ✓'),
                                    ),
                                  );
                                }
                              },
                              icon: const Icon(Icons.check, size: 16),
                              label: Text('قبول وتفعيل', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: OutlinedButton.icon(
                              style: OutlinedButton.styleFrom(
                                foregroundColor: AppColors.danger,
                                side: const BorderSide(color: AppColors.danger),
                                padding: const EdgeInsets.symmetric(vertical: 10),
                              ),
                              onPressed: () async {
                                final ok = await syndic.rejectResident(residentId);
                                if (context.mounted && ok) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('تم رفض الطلب')),
                                  );
                                }
                              },
                              icon: const Icon(Icons.close, size: 16),
                              label: Text('رفض الطلب', style: GoogleFonts.cairo(fontSize: 12)),
                            ),
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

  Widget _buildSuggestionsView(BuildContext context, SyndicProvider syndic) {
    final suggestions = syndic.suggestions;

    return RefreshIndicator(
      onRefresh: () async => await syndic.fetchSuggestions(),
      child: suggestions.isEmpty
          ? const EmptyStateWidget(
              icon: Icons.lightbulb_outline,
              title: 'صندوق الاقتراحات فارغ',
              description: 'لم يقم أي ساكن بإرسال اقتراحات أو ملاحظات حتى الآن',
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: suggestions.length,
              itemBuilder: (context, index) {
                final suggestion = suggestions[index];

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
                              suggestion.title,
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
                              suggestion.statusArabic,
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        suggestion.description,
                        style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(Icons.person_outline, size: 14, color: AppColors.textLight),
                          const SizedBox(width: 4),
                          Text(
                            suggestion.userName ?? 'ساكن الإقامة',
                            style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                          ),
                          if (suggestion.apartmentUnit != null) ...[
                            const SizedBox(width: 12),
                            Text(
                              'شقة ${suggestion.apartmentUnit}',
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                            ),
                          ],
                        ],
                      ),
                      if (suggestion.status == 'PENDING') ...[
                        const Divider(height: 20),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.secondary,
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            minimumSize: const Size(double.infinity, 36),
                          ),
                          onPressed: () {
                            showDialog(
                              context: context,
                              builder: (_) => ConvertToPollDialog(suggestion: suggestion),
                            );
                          },
                          icon: const Icon(Icons.how_to_vote, size: 16),
                          label: Text(
                            'تحويل هذا الاقتراح لتصويت عام للسكان',
                            style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
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

  Widget _buildPollsView(BuildContext context, SyndicProvider syndic) {
    final polls = syndic.polls;

    return RefreshIndicator(
      onRefresh: () async => await syndic.fetchPolls(),
      child: polls.isEmpty
          ? const EmptyStateWidget(
              icon: Icons.how_to_vote_outlined,
              title: 'لا توجد استطلاعات رأي نشطة',
              description: 'يمكنك تحويل أي اقتراح إلى تصويت من تبويب صندوق الاقتراحات',
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: polls.length,
              itemBuilder: (context, index) {
                final poll = polls[index];
                final total = poll.totalVotes;

                return InkWell(
                  onTap: () => _showPollDetailsDialog(context, poll),
                  borderRadius: BorderRadius.circular(16),
                  child: Container(
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
                              poll.title,
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
                              color: poll.isActive ? AppColors.success.withAlpha(20) : AppColors.textLight.withAlpha(20),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              poll.isActive ? 'جارٍ التصويت' : 'مكتمل',
                              style: GoogleFonts.cairo(
                                fontSize: 11,
                                color: poll.isActive ? AppColors.success : AppColors.textSecondary,
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
                      const SizedBox(height: 14),

                      // Options breakdown
                      ...poll.options.map((opt) {
                        final pct = total > 0 ? (opt.votesCount / total) : 0.0;
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    opt.optionText,
                                    style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600),
                                  ),
                                  Text(
                                    '${opt.votesCount} صوت (${(pct * 100).toStringAsFixed(0)}%)',
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(6),
                                child: LinearProgressIndicator(
                                  value: pct,
                                  minHeight: 8,
                                  backgroundColor: AppColors.border,
                                  valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),

                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              'إجمالي الأصوات: $total صوت',
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                            ),
                          ),
                          Text(
                            'ينتهي في: ${poll.expiresAt}',
                            style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
    );
  }

  void _showPollDetailsDialog(BuildContext context, dynamic poll) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.7,
          padding: const EdgeInsets.all(20),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'تفاصيل تصويت السكان',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primary),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: ListView.builder(
                  itemCount: poll.options.length,
                  itemBuilder: (context, idx) {
                    final opt = poll.options[idx];
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withAlpha(20),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${opt.optionText} (${opt.votesCount} أصوات)',
                            style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                          ),
                        ),
                        if (opt.detailedVotes.isEmpty)
                          Padding(
                            padding: const EdgeInsets.all(12.0),
                            child: Text('لم يقم أحد بالتصويت على هذا الخيار', style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
                          )
                        else
                          ...opt.detailedVotes.map<Widget>((vote) {
                            return ListTile(
                              leading: CircleAvatar(
                                radius: 16,
                                backgroundColor: AppColors.primary.withAlpha(25),
                                child: const Icon(Icons.person, size: 16, color: AppColors.primary),
                              ),
                              title: Text(vote['resident_name'] ?? 'مجهول', style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600)),
                              subtitle: Text('شقة ${vote['apartment']} - عمارة ${vote['building']}', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                              dense: true,
                            );
                          }).toList(),
                        const SizedBox(height: 16),
                      ],
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
