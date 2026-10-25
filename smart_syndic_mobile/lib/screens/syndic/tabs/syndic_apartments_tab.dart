import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/auth_provider.dart';
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
  String _filter = 'ALL'; // ALL, EXEMPTED, ARREARS, SETTLED, VACANT
  String _sortBy = 'NUMBER_ASC'; // NUMBER_ASC, NUMBER_DESC, NAME_ASC, FLOOR_ASC
  String? _selectedBuildingId; // null = all buildings
  final Set<String> _collapsedBuildingIds = {};

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

  /// Natural comparison for alphanumeric strings (e.g. "1", "2", "10", "A1", "A2")
  int _naturalCompare(String a, String b) {
    final strA = a.trim();
    final strB = b.trim();
    if (strA == strB) return 0;
    if (strA.isEmpty) return 1;
    if (strB.isEmpty) return -1;

    final regExp = RegExp(r'(\d+)|(\D+)');
    final matchesA = regExp.allMatches(strA).toList();
    final matchesB = regExp.allMatches(strB).toList();

    for (int i = 0; i < matchesA.length && i < matchesB.length; i++) {
      final partA = matchesA[i].group(0)!;
      final partB = matchesB[i].group(0)!;

      final numA = int.tryParse(partA);
      final numB = int.tryParse(partB);

      if (numA != null && numB != null) {
        final comp = numA.compareTo(numB);
        if (comp != 0) return comp;
      } else {
        final comp = partA.toLowerCase().compareTo(partB.toLowerCase());
        if (comp != 0) return comp;
      }
    }
    return matchesA.length.compareTo(matchesB.length);
  }

  /// Sort apartments according to current sort criteria
  List<ApartmentModel> _sortApartments(List<ApartmentModel> apartments) {
    final list = List<ApartmentModel>.from(apartments);
    list.sort((a, b) {
      switch (_sortBy) {
        case 'NUMBER_DESC':
          return _naturalCompare(b.unitNumber, a.unitNumber);
        case 'NAME_ASC':
          final nameA = a.resident?['name']?.toString().trim() ?? '';
          final nameB = b.resident?['name']?.toString().trim() ?? '';
          if (nameA.isEmpty && nameB.isNotEmpty) return 1;
          if (nameA.isNotEmpty && nameB.isEmpty) return -1;
          if (nameA.isNotEmpty && nameB.isNotEmpty) {
            final comp = nameA.compareTo(nameB);
            if (comp != 0) return comp;
          }
          return _naturalCompare(a.unitNumber, b.unitNumber);
        case 'FLOOR_ASC':
          final compFloor = a.floor.compareTo(b.floor);
          if (compFloor != 0) return compFloor;
          return _naturalCompare(a.unitNumber, b.unitNumber);
        case 'NUMBER_ASC':
        default:
          return _naturalCompare(a.unitNumber, b.unitNumber);
      }
    });
    return list;
  }

  /// Filter apartments by search query and status filter
  List<ApartmentModel> _filterApartments(List<ApartmentModel> apartments) {
    final query = _searchController.text.trim().toLowerCase();

    return apartments.where((apt) {
      // Search text match
      final unitMatch = apt.unitNumber.toLowerCase().contains(query);
      final residentName = apt.resident?['name']?.toString().toLowerCase() ?? '';
      final residentMatch = residentName.contains(query);
      final buildingNameMatch = (apt.buildingName ?? '').toLowerCase().contains(query);
      final textMatches = query.isEmpty || unitMatch || residentMatch || buildingNameMatch;

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

  /// Extract distinct buildings from provider & apartments
  List<Map<String, dynamic>> _getDistinctBuildings(
    List<ApartmentModel> allApartments,
    List<Map<String, dynamic>> providerBuildings,
  ) {
    final Map<String, Map<String, dynamic>> map = {};
    for (final b in providerBuildings) {
      final id = b['id']?.toString() ?? '';
      if (id.isNotEmpty) {
        map[id] = {
          'id': id,
          'name': b['name']?.toString() ?? 'عمارة',
          'block': b['block']?.toString(),
        };
      }
    }
    for (final apt in allApartments) {
      final bId = apt.buildingId ?? apt.buildingName ?? 'default';
      if (!map.containsKey(bId)) {
        map[bId] = {
          'id': bId,
          'name': apt.buildingName ?? 'عمارة',
          'block': apt.block,
        };
      }
    }
    return map.values.toList();
  }

  String _getSortLabel() {
    switch (_sortBy) {
      case 'NUMBER_DESC':
        return 'رقم الشقة (10 ← 1)';
      case 'NAME_ASC':
        return 'اسم الساكن (أ ← ي)';
      case 'FLOOR_ASC':
        return 'حسب الطابق';
      case 'NUMBER_ASC':
      default:
        return 'رقم الشقة (1 ← 10)';
    }
  }

  Widget _buildSortButton() {
    return PopupMenuButton<String>(
      initialValue: _sortBy,
      onSelected: (val) => setState(() => _sortBy = val),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      tooltip: 'خيارات الترتيب',
      itemBuilder: (context) => [
        PopupMenuItem(
          value: 'NUMBER_ASC',
          child: Row(
            children: [
              const Icon(Icons.format_list_numbered_rtl_rounded, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('رقم الشقة (1 ← 10)', style: GoogleFonts.cairo(fontSize: 12)),
              ),
              if (_sortBy == 'NUMBER_ASC') const Icon(Icons.check, size: 16, color: AppColors.primary),
            ],
          ),
        ),
        PopupMenuItem(
          value: 'NUMBER_DESC',
          child: Row(
            children: [
              const Icon(Icons.format_list_numbered_rtl_rounded, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('رقم الشقة (10 ← 1)', style: GoogleFonts.cairo(fontSize: 12)),
              ),
              if (_sortBy == 'NUMBER_DESC') const Icon(Icons.check, size: 16, color: AppColors.primary),
            ],
          ),
        ),
        PopupMenuItem(
          value: 'NAME_ASC',
          child: Row(
            children: [
              const Icon(Icons.sort_by_alpha_rounded, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('اسم الساكن (أ ← ي)', style: GoogleFonts.cairo(fontSize: 12)),
              ),
              if (_sortBy == 'NAME_ASC') const Icon(Icons.check, size: 16, color: AppColors.primary),
            ],
          ),
        ),
        PopupMenuItem(
          value: 'FLOOR_ASC',
          child: Row(
            children: [
              const Icon(Icons.stairs_rounded, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('حسب الطابق (0 ← الأعلى)', style: GoogleFonts.cairo(fontSize: 12)),
              ),
              if (_sortBy == 'FLOOR_ASC') const Icon(Icons.check, size: 16, color: AppColors.primary),
            ],
          ),
        ),
      ],
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: AppColors.primary.withAlpha(15),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppColors.primary.withAlpha(40)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.swap_vert_rounded, size: 16, color: AppColors.primary),
            const SizedBox(width: 4),
            Text(
              _getSortLabel(),
              style: GoogleFonts.cairo(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: AppColors.primary,
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
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: isSelected ? activeColor : AppColors.background,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? activeColor : AppColors.border),
        ),
        child: Text(
          label,
          style: GoogleFonts.cairo(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _buildBuildingFilterChip({
    required String? id,
    required String label,
    required bool isSelected,
  }) {
    return InkWell(
      onTap: () => setState(() => _selectedBuildingId = id),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.cairo(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _buildApartmentCard(ApartmentModel apt, {required bool isComplex}) {
    final phone = apt.resident?['phone'] as String?;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: !apt.isBillable
              ? Colors.orange.shade300
              : (apt.resident == null
                  ? AppColors.border
                  : (apt.isSettled ? AppColors.border : AppColors.danger.withAlpha(60))),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(4),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () {
          showDialog(
            context: context,
            builder: (_) => ApartmentDetailsDialog(apartment: apt),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // Leading Apartment Number Badge
              Container(
                width: 50,
                height: 50,
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                decoration: BoxDecoration(
                  color: !apt.isBillable
                      ? Colors.orange.shade50
                      : (apt.resident == null
                          ? AppColors.textLight.withAlpha(20)
                          : (apt.isSettled
                              ? AppColors.primary.withAlpha(20)
                              : AppColors.danger.withAlpha(20))),
                  borderRadius: BorderRadius.circular(12),
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
                          size: 16,
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
              const SizedBox(width: 12),

              // Center Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      apt.resident != null
                          ? apt.resident!['name']
                          : 'شقة شاغرة (بدون ساكن)',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                        color: apt.resident != null
                            ? AppColors.textPrimary
                            : AppColors.warning,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      isComplex
                          ? '${apt.block != null ? "بلوك ${apt.block} • " : ""}الطابق ${apt.floor} • ${apt.monthlyFee.toStringAsFixed(0)} درهم'
                          : '${apt.buildingName != null && apt.buildingName!.isNotEmpty ? "${apt.buildingName} • " : ""}الطابق ${apt.floor} • ${apt.monthlyFee.toStringAsFixed(0)} درهم',
                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 3),

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
                              Icon(Icons.shield_outlined, size: 11, color: Colors.orange.shade900),
                              const SizedBox(width: 4),
                              Text(
                                'معفاة من الواجبات',
                                style: GoogleFonts.cairo(
                                  fontSize: 10,
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
                              fontSize: 10,
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
                  padding: const EdgeInsets.all(4),
                  constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                  onPressed: () => _openWhatsApp(phone, apt.unitNumber),
                  icon: const Icon(Icons.chat, color: AppColors.success, size: 18),
                  tooltip: 'مراسلة واتساب',
                ),
              ],

              // Dedicated Exemption Toggle Button (For ALL apartments)
              IconButton(
                visualDensity: VisualDensity.compact,
                padding: const EdgeInsets.all(4),
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                onPressed: () => toggleBillable(context, apt),
                icon: Icon(
                  apt.isBillable ? Icons.money_off_outlined : Icons.restore_rounded,
                  color: apt.isBillable ? AppColors.textLight : Colors.orange.shade800,
                  size: 18,
                ),
                tooltip: apt.isBillable ? 'إعفاء الشقة من واجبات السانديك' : 'إلغاء الإعفاء وإعادة الفوترة',
              ),

              const Icon(Icons.chevron_left, color: AppColors.textLight, size: 18),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBuildingSection({
    required Map<String, dynamic> building,
    required List<ApartmentModel> apartments,
    required int totalInBuilding,
  }) {
    final bId = building['id']?.toString() ?? '';
    final bName = building['name']?.toString() ?? 'عمارة';
    final bBlock = building['block']?.toString();
    final isCollapsed = _collapsedBuildingIds.contains(bId);

    final arrearsCount = apartments.where((a) => a.isBillable && !a.isSettled).length;
    final vacantCount = apartments.where((a) => a.resident == null).length;
    final settledCount = apartments.where((a) => a.isBillable && a.resident != null && a.isSettled).length;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.primary.withAlpha(35), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(6),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Building Header Bar
          InkWell(
            onTap: () {
              setState(() {
                if (isCollapsed) {
                  _collapsedBuildingIds.remove(bId);
                } else {
                  _collapsedBuildingIds.add(bId);
                }
              });
            },
            borderRadius: BorderRadius.vertical(
              top: const Radius.circular(15),
              bottom: isCollapsed ? const Radius.circular(15) : Radius.zero,
            ),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: AppColors.primary.withAlpha(12),
                borderRadius: BorderRadius.vertical(
                  top: const Radius.circular(15),
                  bottom: isCollapsed ? const Radius.circular(15) : Radius.zero,
                ),
                border: Border(
                  bottom: isCollapsed
                      ? BorderSide.none
                      : BorderSide(color: AppColors.primary.withAlpha(25), width: 1),
                ),
              ),
              child: Row(
                children: [
                  // Building Icon
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withAlpha(25),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.apartment_rounded, color: AppColors.primary, size: 22),
                  ),
                  const SizedBox(width: 10),

                  // Building Name and Block
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                bName,
                                style: GoogleFonts.cairo(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15,
                                  color: AppColors.textPrimary,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (bBlock != null && bBlock.isNotEmpty) ...[
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withAlpha(20),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  'بلوك $bBlock',
                                  style: GoogleFonts.cairo(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                        const SizedBox(height: 2),

                        // Stats pills summary
                        Wrap(
                          spacing: 6,
                          runSpacing: 2,
                          children: [
                            Text(
                              '$totalInBuilding شقة',
                              style: GoogleFonts.cairo(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textSecondary,
                              ),
                            ),
                            if (settledCount > 0)
                              Text(
                                '• $settledCount خالص',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.success,
                                ),
                              ),
                            if (arrearsCount > 0)
                              Text(
                                '• $arrearsCount متأخرات',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.danger,
                                ),
                              ),
                            if (vacantCount > 0)
                              Text(
                                '• $vacantCount شاغرة',
                                style: GoogleFonts.cairo(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.warning,
                                ),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // Collapse / Expand Icon
                  Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Icon(
                      isCollapsed ? Icons.keyboard_arrow_down_rounded : Icons.keyboard_arrow_up_rounded,
                      color: AppColors.textSecondary,
                      size: 20,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Building Apartments List (if not collapsed)
          if (!isCollapsed)
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 10, 10, 2),
              child: apartments.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      child: Center(
                        child: Text(
                          _searchController.text.isNotEmpty || _filter != 'ALL'
                              ? 'لا توجد شقق مطابقة للتصفية في هذه العمارة'
                              : 'لا توجد شقق مسجلة في هذه العمارة',
                          style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textLight),
                        ),
                      ),
                    )
                  : Column(
                      children: apartments
                          .map((apt) => _buildApartmentCard(apt, isComplex: true))
                          .toList(),
                    ),
            ),
        ],
      ),
    );
  }

  Widget _buildSingleBuildingView({required List<ApartmentModel> allApartments}) {
    final filtered = _filterApartments(allApartments);
    final sorted = _sortApartments(filtered);

    if (sorted.isEmpty) {
      return EmptyStateWidget(
        icon: Icons.search_off_rounded,
        title: 'لا توجد نتائج مطابقة',
        description: 'لم يتم العثور على أي شقق تطابق معايير البحث الحالية.',
        buttonText: _searchController.text.isNotEmpty ? 'مسح البحث' : null,
        onButtonPressed: _searchController.text.isNotEmpty
            ? () {
                setState(() {
                  _searchController.clear();
                  _filter = 'ALL';
                });
              }
            : null,
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
      itemCount: sorted.length + 1,
      itemBuilder: (context, index) {
        if (index == 0) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 10, right: 4, left: 4),
            child: Row(
              children: [
                Text(
                  'إجمالي الشقق: ${sorted.length}',
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textSecondary,
                  ),
                ),
                const Spacer(),
                Text(
                  'مرتبة: ${_getSortLabel()}',
                  style: GoogleFonts.cairo(
                    fontSize: 11,
                    color: AppColors.textLight,
                  ),
                ),
              ],
            ),
          );
        }
        final apt = sorted[index - 1];
        return _buildApartmentCard(apt, isComplex: false);
      },
    );
  }

  Widget _buildComplexView({
    required List<Map<String, dynamic>> buildings,
    required List<ApartmentModel> allApartments,
  }) {
    final activeBuildings = _selectedBuildingId != null
        ? buildings.where((b) => b['id']?.toString() == _selectedBuildingId).toList()
        : buildings;

    // Filter per building
    final Map<String, List<ApartmentModel>> buildingAptsMap = {};
    int totalMatching = 0;

    for (final b in activeBuildings) {
      final bId = b['id']?.toString() ?? '';
      final bName = b['name']?.toString() ?? '';
      final bApts = allApartments.where((a) =>
          a.buildingId == bId || (a.buildingId == null && a.buildingName == bName)).toList();
      final filtered = _filterApartments(bApts);
      final sorted = _sortApartments(filtered);
      buildingAptsMap[bId] = sorted;
      totalMatching += sorted.length;
    }

    if (totalMatching == 0) {
      return EmptyStateWidget(
        icon: Icons.search_off_rounded,
        title: 'لا توجد نتائج مطابقة',
        description: 'لم يتم العثور على أي شقق تطابق معايير البحث في أي عمارة.',
        buttonText: _searchController.text.isNotEmpty ? 'مسح البحث' : null,
        onButtonPressed: _searchController.text.isNotEmpty
            ? () {
                setState(() {
                  _searchController.clear();
                  _filter = 'ALL';
                  _selectedBuildingId = null;
                });
              }
            : null,
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 12, right: 4, left: 4),
          child: Row(
            children: [
              Text(
                'المجمع: ${activeBuildings.length} عمارات • $totalMatching شقة مطابقة',
                style: GoogleFonts.cairo(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                ),
              ),
              const Spacer(),
              Text(
                'مرتبة: ${_getSortLabel()}',
                style: GoogleFonts.cairo(
                  fontSize: 11,
                  color: AppColors.textLight,
                ),
              ),
            ],
          ),
        ),
        for (final b in activeBuildings) ...[
          Builder(builder: (_) {
            final bId = b['id']?.toString() ?? '';
            final bName = b['name']?.toString() ?? '';
            final bTotalInBuilding = allApartments.where((a) =>
                a.buildingId == bId || (a.buildingId == null && a.buildingName == bName)).length;
            final bSortedApts = buildingAptsMap[bId] ?? [];

            // If user searched/filtered and this building has 0 matching, hide it to avoid clutter
            if (bSortedApts.isEmpty && (_searchController.text.isNotEmpty || _filter != 'ALL')) {
              return const SizedBox.shrink();
            }

            return _buildBuildingSection(
              building: b,
              apartments: bSortedApts,
              totalInBuilding: bTotalInBuilding,
            );
          }),
        ],
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final auth = context.watch<AuthProvider>();
    final allApartments = syndic.apartments;
    final buildings = _getDistinctBuildings(allApartments, syndic.buildings);

    final propertyType = auth.tenant?.propertyType ?? '';
    final isComplex = propertyType == 'RESIDENTIAL_COMPLEX' ||
                      propertyType == 'MULTI_BUILDING_COMPLEX' ||
                      buildings.length > 1;

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
            // Search, Sort & Filter Header
            Container(
              color: Colors.white,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Search Bar
                  TextField(
                    controller: _searchController,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      hintText: isComplex
                          ? 'ابحث برقم الشقة، اسم الساكن، أو العمارة...'
                          : 'ابحث برقم الشقة أو اسم الساكن...',
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

                  // Sort & Status Filter Row
                  Row(
                    children: [
                      // Sort selector
                      _buildSortButton(),
                      const SizedBox(width: 8),

                      // Filter Chips
                      Expanded(
                        child: SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _buildFilterChip('ALL', 'الكل (${allApartments.length})'),
                              const SizedBox(width: 6),
                              _buildFilterChip('EXEMPTED', 'معفاة ($totalExempted)', color: Colors.orange.shade800),
                              const SizedBox(width: 6),
                              _buildFilterChip('ARREARS', 'متأخرات ($totalArrears)', color: AppColors.danger),
                              const SizedBox(width: 6),
                              _buildFilterChip('SETTLED', 'خالصة', color: AppColors.success),
                              const SizedBox(width: 6),
                              _buildFilterChip('VACANT', 'شاغرة ($totalVacant)', color: AppColors.warning),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),

                  // If Complex: Building Filter Bar
                  if (isComplex && buildings.length > 1) ...[
                    const SizedBox(height: 10),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _buildBuildingFilterChip(
                            id: null,
                            label: 'كل العمارات (${allApartments.length})',
                            isSelected: _selectedBuildingId == null,
                          ),
                          for (final b in buildings) ...[
                            const SizedBox(width: 6),
                            Builder(builder: (_) {
                              final bId = b['id']?.toString() ?? '';
                              final bName = b['name']?.toString() ?? 'عمارة';
                              final bBlock = b['block']?.toString();
                              final bCount = allApartments.where((a) =>
                                  a.buildingId == bId || (a.buildingId == null && a.buildingName == bName)).length;
                              final label = '$bName${bBlock != null && bBlock.isNotEmpty ? " ($bBlock)" : ""} ($bCount)';
                              return _buildBuildingFilterChip(
                                id: bId,
                                label: label,
                                isSelected: _selectedBuildingId == bId,
                              );
                            }),
                          ],
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),

            // Content Area: Either Single Building or Complex
            Expanded(
              child: allApartments.isEmpty
                  ? EmptyStateWidget(
                      icon: Icons.apartment,
                      title: 'لا توجد شقق مسجلة حالياً',
                      description: 'اضغط على زر إضافة شقق بالأسفل لإضافة أولى الشقق في الإقامة',
                    )
                  : (isComplex
                      ? _buildComplexView(buildings: buildings, allApartments: allApartments)
                      : _buildSingleBuildingView(allApartments: allApartments)),
            ),
          ],
        ),
      ),
    );
  }
}
