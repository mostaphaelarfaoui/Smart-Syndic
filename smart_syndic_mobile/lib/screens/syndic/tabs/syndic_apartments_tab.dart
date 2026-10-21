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

// Feature 4 helper — toggle billable from apartment list
extension _BillableToggle on _SyndicApartmentsTabState {
  Future<void> toggleBillable(BuildContext ctx, ApartmentModel apt) async {
    final isCurrentlyBillable = apt.isBillable;
    final actionTitle = isCurrentlyBillable ? 'إعفاء الشقة من واجبات السانديك' : 'إلغاء الإعفاء وإعادة الفوترة';
    final actionMessage = isCurrentlyBillable
        ? 'هل أنت متأكد من رغبتك في إعفاء الشقة ${apt.unitNumber} من واجبات السانديك الشهرية؟\n\n• لن تُحسب عليها أي متأخرات.\n• سيتم استثناؤها من المداخيل الشهرية المتوقعة للإقامة.'
        : 'هل تريد إلغاء إعفاء الشقة ${apt.unitNumber} وإعادة إدراجها في حسابات واجبات السانديك الشهرية والمتأخرات؟';

    final confirm = await showDialog<bool>(
      context: ctx,
      builder: (c) => AlertDialog(
        title: Row(
          children: [
            Icon(
              isCurrentlyBillable ? Icons.money_off_rounded : Icons.restore_rounded,
              color: isCurrentlyBillable ? AppColors.warning : AppColors.success,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                actionTitle,
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ),
          ],
        ),
        content: Text(
          actionMessage,
          style: GoogleFonts.cairo(fontSize: 13, height: 1.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(c, false),
            child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: isCurrentlyBillable ? AppColors.warning : AppColors.success,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(c, true),
            child: Text(
              isCurrentlyBillable ? 'تأكيد الإعفاء' : 'إعادة الفوترة',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: Colors.white),
            ),
          ),
        ],
      ),
    );
    if (confirm != true || !ctx.mounted) return;
    final syndic = Provider.of<SyndicProvider>(ctx, listen: false);
    final ok = await syndic.toggleApartmentBillable(apt.id);
    if (ctx.mounted) {
      ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(
        content: Text(
          ok
              ? (isCurrentlyBillable ? 'تم إعفاء الشقة من واجبات السانديك بنجاح ✓' : 'تم إلغاء الإعفاء وإعادة فوترة الشقة بنجاح ✓')
              : (syndic.errorMessage ?? 'حدث خطأ'),
          style: GoogleFonts.cairo(),
        ),
        backgroundColor: ok ? AppColors.success : AppColors.danger,
      ));
    }
  }
}


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
      if (_filter == 'EXEMPTED') {
        return !apt.isBillable;
      } else if (_filter == 'SETTLED') {
        return apt.isBillable && apt.resident != null && apt.isSettled;
      } else if (_filter == 'ARREARS') {
        return apt.isBillable && !apt.isSettled;
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
    final totalArrears = allApartments.where((a) => a.isBillable && !a.isSettled).length;
    final totalExempted = allApartments.where((a) => !a.isBillable).length;

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        heroTag: 'fab_add_apartments',
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const AddApartmentDialog(),
          );
        },
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 3,
        icon: const Icon(Icons.add_home_work_rounded, size: 20),
        label: Text('إضافة شقق', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
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
                        _buildFilterChip('EXEMPTED', 'معفاة ($totalExempted)', color: Colors.orange.shade800),
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
                      title: allApartments.isEmpty ? 'لا توجد شقق مسجلة حالياً' : 'لا توجد نتائج مطابقة',
                      description: allApartments.isEmpty
                          ? 'اضغط على زر إضافة شقق بالأسفل لإضافة أولى الشقق في الإقامة'
                          : 'لم يتم العثور على أي شقق تطابق معايير البحث الحالية.',
                      buttonText: _searchController.text.isNotEmpty ? 'مسح البحث' : null,
                      onButtonPressed: _searchController.text.isNotEmpty
                          ? () {
                              setState(() {
                                _searchController.clear();
                                _filter = 'ALL';
                              });
                            }
                          : null,
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
                              color: !apt.isBillable
                                  ? Colors.orange.shade300
                                  : (apt.resident == null
                                      ? AppColors.border
                                      : (apt.isSettled ? AppColors.border : AppColors.danger.withAlpha(60))),
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
                                    width: 54,
                                    height: 54,
                                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: !apt.isBillable
                                          ? Colors.orange.shade50
                                          : (apt.resident == null
                                              ? AppColors.textLight.withAlpha(20)
                                              : (apt.isSettled
                                                  ? AppColors.primary.withAlpha(20)
                                                  : AppColors.danger.withAlpha(20))),
                                      borderRadius: BorderRadius.circular(14),
                                      border: !apt.isBillable ? Border.all(color: Colors.orange.shade200) : null,
                                    ),
                                    child: Center(
                                      child: FittedBox(
                                        fit: BoxFit.scaleDown,
                                        child: Column(
                                          mainAxisSize: MainAxisSize.min,
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Icon(
                                              !apt.isBillable
                                                  ? Icons.money_off_rounded
                                                  : Icons.meeting_room,
                                              size: 18,
                                              color: !apt.isBillable
                                                  ? Colors.orange.shade800
                                                  : (apt.resident == null
                                                      ? AppColors.textLight
                                                      : (apt.isSettled ? AppColors.primary : AppColors.danger)),
                                            ),
                                            Text(
                                              apt.unitNumber,
                                              style: GoogleFonts.cairo(
                                                fontWeight: FontWeight.bold,
                                                fontSize: 11,
                                                height: 1.1,
                                                color: AppColors.textPrimary,
                                              ),
                                              maxLines: 1,
                                            ),
                                          ],
                                        ),
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
                                          '${apt.block != null ? "بلوك ${apt.block} • " : ""}${apt.buildingName ?? ""} • الطابق ${apt.floor} • ${apt.monthlyFee.toStringAsFixed(0)} درهم',
                                          style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                        ),
                                        const SizedBox(height: 4),

                                        // Status Tag: Exempted OR Settled/Arrears
                                        if (!apt.isBillable)
                                          FittedBox(
                                            fit: BoxFit.scaleDown,
                                            alignment: AlignmentDirectional.centerStart,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: Colors.orange.shade50,
                                                borderRadius: BorderRadius.circular(6),
                                                border: Border.all(color: Colors.orange.shade300),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Icon(Icons.shield_outlined, size: 12, color: Colors.orange.shade900),
                                                  const SizedBox(width: 4),
                                                  Text(
                                                    'معفاة من الواجبات',
                                                    style: GoogleFonts.cairo(
                                                      fontSize: 11,
                                                      fontWeight: FontWeight.bold,
                                                      color: Colors.orange.shade900,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          )
                                        else if (apt.resident != null)
                                          FittedBox(
                                            fit: BoxFit.scaleDown,
                                            alignment: AlignmentDirectional.centerStart,
                                            child: Container(
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
                                          ),
                                      ],
                                    ),
                                  ),

                                  // WhatsApp button if phone available
                                  if (phone != null && phone.isNotEmpty) ...[
                                    IconButton(
                                      visualDensity: VisualDensity.compact,
                                      padding: const EdgeInsets.all(6),
                                      constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                                      onPressed: () => _openWhatsApp(phone, apt.unitNumber),
                                      icon: const Icon(Icons.chat, color: AppColors.success, size: 20),
                                      tooltip: 'مراسلة واتساب',
                                    ),
                                  ],

                                  // Dedicated Exemption Toggle Button (For ALL apartments)
                                  IconButton(
                                    visualDensity: VisualDensity.compact,
                                    padding: const EdgeInsets.all(6),
                                    constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                                    onPressed: () => toggleBillable(context, apt),
                                    icon: Icon(
                                      apt.isBillable ? Icons.money_off_outlined : Icons.restore_rounded,
                                      color: apt.isBillable ? AppColors.textLight : Colors.orange.shade800,
                                      size: 20,
                                    ),
                                    tooltip: apt.isBillable ? 'إعفاء الشقة من واجبات السانديك' : 'إلغاء الإعفاء وإعادة الفوترة',
                                  ),

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
