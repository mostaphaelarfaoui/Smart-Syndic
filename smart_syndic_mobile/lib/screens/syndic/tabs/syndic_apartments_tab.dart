import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/add_apartment_dialog.dart';
import '../dialogs/apartment_details_dialog.dart';

class SyndicApartmentsTab extends StatefulWidget {
  const SyndicApartmentsTab({super.key});

  @override
  State<SyndicApartmentsTab> createState() => _SyndicApartmentsTabState();
}

class _SyndicApartmentsTabState extends State<SyndicApartmentsTab> {
  final TextEditingController _searchController = TextEditingController();
  String _filter = 'ALL'; // ALL, SETTLED, ARREARS, VACANT

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _openWhatsApp(String phone, String unit) async {
    var cleanPhone = phone.replaceAll(RegExp(r'\s+'), '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '212${cleanPhone.substring(1)}';
    } else if (cleanPhone.startsWith('+')) {
      cleanPhone = cleanPhone.substring(1);
    }
    final message = Uri.encodeComponent('السلام عليكم، بخصوص الشقة رقم $unit.');
    final uri = Uri.parse('https://wa.me/$cleanPhone?text=$message');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  List<ApartmentModel> _filterApartments(List<ApartmentModel> apartments) {
    final query = _searchController.text.trim().toLowerCase();

    return apartments.where((apt) {
      // Search text match
      final unitMatch = apt.unitNumber.toLowerCase().contains(query);
      final residentName = apt.resident?['name']?.toString().toLowerCase() ?? '';
      final residentMatch = residentName.contains(query);
      final textMatches = query.isEmpty || unitMatch || residentMatch;

      if (!textMatches) return false;

      // Filter category match
      if (_filter == 'SETTLED') {
        return apt.resident != null && apt.isSettled;
      } else if (_filter == 'ARREARS') {
        return !apt.isSettled;
      } else if (_filter == 'VACANT') {
        return apt.resident == null;
      }

      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final allApartments = syndic.apartments;
    final filteredApartments = _filterApartments(allApartments);

    final totalVacant = allApartments.where((a) => a.resident == null).length;
    final totalArrears = allApartments.where((a) => !a.isSettled).length;

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const AddApartmentDialog(),
          );
        },
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add_home_work_outlined),
        label: Text('إضافة شقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: RefreshIndicator(
        onRefresh: () => syndic.fetchApartments(),
        child: Column(
          children: [
            // Search & Filter Header
            Container(
              color: Colors.white,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: Column(
                children: [
                  // Search Bar
                  TextField(
                    controller: _searchController,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      hintText: 'ابحث برقم الشقة أو اسم الساكن...',
                      hintStyle: GoogleFonts.cairo(fontSize: 13, color: AppColors.textLight),
                      prefixIcon: const Icon(Icons.search, color: AppColors.primary, size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                setState(() {});
                              },
                            )
                          : null,
                      filled: true,
                      fillColor: AppColors.background,
                      contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('ALL', 'الكل (${allApartments.length})'),
                        const SizedBox(width: 8),
                        _buildFilterChip('ARREARS', 'متأخرات ($totalArrears)', color: AppColors.danger),
                        const SizedBox(width: 8),
                        _buildFilterChip('SETTLED', 'خالصة الذمة', color: AppColors.success),
                        const SizedBox(width: 8),
                        _buildFilterChip('VACANT', 'شاغرة ($totalVacant)', color: AppColors.warning),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Apartment List
            Expanded(
              child: filteredApartments.isEmpty
                  ? EmptyStateWidget(
                      icon: Icons.apartment,
                      title: 'لا توجد نتائج مطابقة',
                      description: 'لم يتم العثور على أي شقق تطابق معايير البحث الحالية.',
                      buttonText: _searchController.text.isNotEmpty ? 'مسح البحث' : 'إضافة شقة الآن',
                      onButtonPressed: () {
                        if (_searchController.text.isNotEmpty) {
                          setState(() {
                            _searchController.clear();
                            _filter = 'ALL';
                          });
                        } else {
                          showDialog(
                            context: context,
                            builder: (_) => const AddApartmentDialog(),
                          );
                        }
                      },
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
                      itemCount: filteredApartments.length,
                      itemBuilder: (context, index) {
                        final apt = filteredApartments[index];
                        final phone = apt.resident?['phone'] as String?;

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: apt.resident == null
                                  ? AppColors.border
                                  : (apt.isSettled ? AppColors.border : AppColors.danger.withAlpha(60)),
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withAlpha(6),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(16),
                            onTap: () {
                              showDialog(
                                context: context,
                                builder: (_) => ApartmentDetailsDialog(apartment: apt),
                              );
                            },
                            child: Padding(
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                children: [
                                  // Leading Apartment Number Badge
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      color: apt.resident == null
                                          ? AppColors.textLight.withAlpha(20)
                                          : (apt.isSettled
                                              ? AppColors.primary.withAlpha(20)
                                              : AppColors.danger.withAlpha(20)),
                                      borderRadius: BorderRadius.circular(14),
                                    ),
                                    child: Center(
                                      child: Column(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(
                                            Icons.meeting_room,
                                            size: 18,
                                            color: apt.resident == null
                                                ? AppColors.textLight
                                                : (apt.isSettled ? AppColors.primary : AppColors.danger),
                                          ),
                                          Text(
                                            apt.unitNumber,
                                            style: GoogleFonts.cairo(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 12,
                                              color: AppColors.textPrimary,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 14),

                                  // Center Info
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Expanded(
                                              child: Text(
                                                apt.resident != null
                                                    ? apt.resident!['name']
                                                    : 'شقة شاغرة (بدون ساكن)',
                                                style: GoogleFonts.cairo(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 14,
                                                  color: apt.resident != null
                                                      ? AppColors.textPrimary
                                                      : AppColors.warning,
                                                ),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          'الطابق: ${apt.floor} • الواجب: ${apt.monthlyFee.toStringAsFixed(0)} درهم',
                                          style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                        ),
                                        const SizedBox(height: 4),

                                        // Balance tag
                                        if (apt.resident != null)
                                          Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                                decoration: BoxDecoration(
                                                  color: apt.isSettled
                                                      ? AppColors.success.withAlpha(20)
                                                      : AppColors.danger.withAlpha(20),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  apt.isSettled
                                                      ? 'خالص ✓'
                                                      : 'متأخرات: ${apt.arrears.toStringAsFixed(0)} د.م',
                                                  style: GoogleFonts.cairo(
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.bold,
                                                    color: apt.isSettled ? AppColors.success : AppColors.danger,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                      ],
                                    ),
                                  ),

                                  // WhatsApp button if phone available
                                  if (phone != null && phone.isNotEmpty) ...[
                                    IconButton(
                                      onPressed: () => _openWhatsApp(phone, apt.unitNumber),
                                      icon: const Icon(Icons.chat, color: AppColors.success, size: 22),
                                      tooltip: 'مراسلة واتساب',
                                    ),
                                  ],

                                  const Icon(Icons.chevron_left, color: AppColors.textLight, size: 20),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String key, String label, {Color? color}) {
    final isSelected = _filter == key;
    final activeColor = color ?? AppColors.primary;

    return InkWell(
      onTap: () => setState(() => _filter = key),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? activeColor : AppColors.background,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? activeColor : AppColors.border),
        ),
        child: Text(
          label,
          style: GoogleFonts.cairo(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}
