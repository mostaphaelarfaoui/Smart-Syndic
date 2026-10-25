import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/number_utils.dart';
import '../../../models/apartment_model.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/syndic_provider.dart';

class RecordPaymentDialog extends StatefulWidget {
  final String? initialApartmentId;

  const RecordPaymentDialog({super.key, this.initialApartmentId});

  @override
  State<RecordPaymentDialog> createState() => _RecordPaymentDialogState();
}

class _RecordPaymentDialogState extends State<RecordPaymentDialog> {
  final _formKey = GlobalKey<FormState>();
  String? _selectedBuildingId;
  String? _selectedApartmentId;
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _chequeRefController = TextEditingController();
  String _paymentMethod = 'CASH';
  DateTime _paymentDate = DateTime.now();
  final List<String> _selectedMonths = [];
  bool _isSubmitting = false;

  final List<String> _availableMonths = [
    '2026-01', '2026-02', '2026-03', '2026-04',
    '2026-05', '2026-06', '2026-07', '2026-08',
    '2026-09', '2026-10', '2026-11', '2026-12',
  ];

  @override
  void initState() {
    super.initState();
    // Default current month
    final currentMonthStr = DateFormat('yyyy-MM').format(DateTime.now());
    _selectedMonths.add(currentMonthStr);

    final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
    final allApts = syndicProvider.apartments;

    if (allApts.isNotEmpty) {
      ApartmentModel selectedApt;
      if (widget.initialApartmentId != null) {
        selectedApt = allApts.firstWhere(
          (a) => a.id == widget.initialApartmentId,
          orElse: () => allApts.first,
        );
      } else {
        selectedApt = allApts.first;
      }
      _selectedApartmentId = selectedApt.id;
      _selectedBuildingId = selectedApt.buildingId ?? selectedApt.buildingName;
      _amountController.text = selectedApt.monthlyFee.toStringAsFixed(0);
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    _chequeRefController.dispose();
    super.dispose();
  }

  /// Natural comparison for alphanumeric unit numbers (e.g. "1", "2", "10", "A1", "A2")
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

  /// Filter and sort apartments for the selected building
  List<ApartmentModel> _getApartmentsForBuilding(String? bId, List<ApartmentModel> apartments) {
    List<ApartmentModel> list;
    if (bId == null || bId == 'ALL') {
      list = List<ApartmentModel>.from(apartments);
    } else {
      list = apartments.where((a) =>
          a.buildingId == bId || (a.buildingId == null && a.buildingName == bId)).toList();
    }
    list.sort((a, b) => _naturalCompare(a.unitNumber, b.unitNumber));
    return list;
  }

  void _onApartmentChanged(String? newId) {
    setState(() {
      _selectedApartmentId = newId;
      if (newId != null) {
        final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
        final apt = syndicProvider.apartments.firstWhere(
          (a) => a.id == newId,
          orElse: () => syndicProvider.apartments.first,
        );
        _amountController.text = (apt.monthlyFee * (_selectedMonths.isEmpty ? 1 : _selectedMonths.length)).toStringAsFixed(0);
      }
    });
  }

  void _toggleMonth(String month) {
    setState(() {
      if (_selectedMonths.contains(month)) {
        if (_selectedMonths.length > 1) {
          _selectedMonths.remove(month);
        }
      } else {
        _selectedMonths.add(month);
      }

      if (_selectedApartmentId != null) {
        final syndicProvider = Provider.of<SyndicProvider>(context, listen: false);
        final apt = syndicProvider.apartments.firstWhere(
          (a) => a.id == _selectedApartmentId,
          orElse: () => syndicProvider.apartments.first,
        );
        _amountController.text = (apt.monthlyFee * _selectedMonths.length).toStringAsFixed(0);
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedApartmentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('المرجو اختيار الشقة')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    final amount = double.tryParse(_amountController.text) ?? 0.0;

    final result = await syndic.recordPayment(
      apartmentId: _selectedApartmentId!,
      amountPaid: amount,
      paymentMethod: _paymentMethod,
      chequeReference: _paymentMethod == 'CHEQUE' ? _chequeRefController.text : null,
      paymentDate: DateFormat('yyyy-MM-dd').format(_paymentDate),
      coveredMonths: _selectedMonths,
    );

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (result != null) {
        final receiptUrl = result['receipt_url'] as String?;
        final receiptNumber = result['receipt_number'] as String? ?? 'REC-';
        final paidAmount = parseDouble(result['amount_paid'], amount);

        // Find apartment data for unit number, resident info and remaining arrears
        final apt = syndic.apartments.where((a) => a.id == _selectedApartmentId).firstOrNull;
        final unitNumber = apt?.unitNumber ?? '';
        final residentName = apt?.resident != null ? (apt!.resident!['name'] ?? 'ساكن الإقامة') : 'شاغرة';
        final residentPhone = apt?.resident != null ? apt!.resident!['phone'] as String? : null;
        final remainingArrears = apt?.arrears ?? 0.0;
        final coveredCopy = List<String>.from(_selectedMonths);

        Navigator.pop(context, true);


        // Show dedicated receipt success modal dialog with direct download & WhatsApp actions
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (dialogCtx) => PaymentReceiptSuccessDialog(
            receiptNumber: receiptNumber,
            amountPaid: paidAmount,
            unitNumber: unitNumber,
            residentName: residentName,
            residentPhone: residentPhone,
            receiptUrl: receiptUrl,
            coveredMonths: coveredCopy,
            remainingArrears: remainingArrears,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            content: Text(syndic.errorMessage ?? 'حدث خطأ أثناء تسجيل الدفعة'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final auth = context.watch<AuthProvider>();
    final allApartments = syndic.apartments;

    final propertyType = auth.tenant?.propertyType ?? '';
    final buildings = syndic.buildings;

    // Distinct buildings list
    final List<Map<String, dynamic>> availableBuildings = [];
    final Set<String> seenBIds = {};
    for (final b in buildings) {
      final id = b['id']?.toString() ?? '';
      if (id.isNotEmpty && !seenBIds.contains(id)) {
        seenBIds.add(id);
        availableBuildings.add({
          'id': id,
          'name': b['name']?.toString() ?? 'عمارة',
          'block': b['block']?.toString(),
        });
      }
    }
    for (final a in allApartments) {
      final bId = a.buildingId ?? a.buildingName ?? 'default';
      if (!seenBIds.contains(bId)) {
        seenBIds.add(bId);
        availableBuildings.add({
          'id': bId,
          'name': a.buildingName ?? 'عمارة',
          'block': a.block,
        });
      }
    }

    final isComplex = propertyType == 'RESIDENTIAL_COMPLEX' ||
                      propertyType == 'MULTI_BUILDING_COMPLEX' ||
                      availableBuildings.length > 1;

    // In complex mode, default to the first building if not set or invalid
    if (isComplex && availableBuildings.isNotEmpty) {
      if (_selectedBuildingId == null || !availableBuildings.any((b) => b['id']?.toString() == _selectedBuildingId)) {
        _selectedBuildingId = availableBuildings.first['id']?.toString();
      }
    }

    // Filter apartments for current building
    final currentApartments = _getApartmentsForBuilding(
      isComplex ? _selectedBuildingId : null,
      allApartments,
    );

    // Ensure _selectedApartmentId is valid
    if (currentApartments.isNotEmpty) {
      if (_selectedApartmentId == null || !currentApartments.any((a) => a.id == _selectedApartmentId)) {
        _selectedApartmentId = currentApartments.first.id;
        _amountController.text = (currentApartments.first.monthlyFee * (_selectedMonths.isEmpty ? 1 : _selectedMonths.length)).toStringAsFixed(0);
      }
    } else {
      _selectedApartmentId = null;
    }

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withAlpha(25),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.receipt_long, color: AppColors.primary, size: 24),
                          ),
                          const SizedBox(width: 12),
                          Flexible(
                            child: Text(
                              'تسجيل استخلاص واجبات',
                              style: GoogleFonts.cairo(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: AppColors.textLight),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // In Complex: Dedicated Building Dropdown to cleanly separate buildings and apartments
                if (isComplex && availableBuildings.length > 1) ...[
                  DropdownButtonFormField<String>(
                    key: ValueKey('b_$_selectedBuildingId'),
                    isExpanded: true,
                    initialValue: _selectedBuildingId,
                    decoration: const InputDecoration(
                      labelText: 'العمارة / البلوك',
                      prefixIcon: Icon(Icons.apartment_rounded, color: AppColors.primary),
                    ),
                    items: availableBuildings.map((b) {
                      final bId = b['id']?.toString();
                      final bName = b['name']?.toString() ?? 'عمارة';
                      final bBlock = b['block']?.toString();
                      final bCount = allApartments.where((a) =>
                          a.buildingId == bId || (a.buildingId == null && a.buildingName == bName)).length;
                      final label = '$bName${bBlock != null && bBlock.isNotEmpty ? " (بلوك $bBlock)" : ""} - $bCount شقق';

                      return DropdownMenuItem<String>(
                        value: bId,
                        child: Text(label, overflow: TextOverflow.ellipsis),
                      );
                    }).toList(),
                    onChanged: (newBId) {
                      setState(() {
                        _selectedBuildingId = newBId;
                        final bApts = _getApartmentsForBuilding(newBId, allApartments);
                        if (bApts.isNotEmpty) {
                          _selectedApartmentId = bApts.first.id;
                          _amountController.text = (bApts.first.monthlyFee * (_selectedMonths.isEmpty ? 1 : _selectedMonths.length)).toStringAsFixed(0);
                        } else {
                          _selectedApartmentId = null;
                          _amountController.text = '0';
                        }
                      });
                    },
                  ),
                  const SizedBox(height: 16),
                ],

                // Apartment dropdown
                DropdownButtonFormField<String>(
                  key: ValueKey('apt_$_selectedApartmentId'),
                  isExpanded: true,
                  initialValue: _selectedApartmentId,
                  decoration: InputDecoration(
                    labelText: isComplex ? 'الشقة والساكن بالعمارة المحددة' : 'رقم الشقة / الساكن',
                    prefixIcon: const Icon(Icons.meeting_room_outlined),
                  ),
                  items: currentApartments.map((apt) {
                    final resident = !apt.isBillable
                        ? 'شاغرة لدى المنعش العقاري (معفاة)'
                        : (apt.resident != null ? apt.resident!['name'] : 'شاغرة');
                    final unitLabel = formatUnitLabel(apt.unitNumber);

                    return DropdownMenuItem<String>(
                      value: apt.id,
                      child: Text(
                        '$unitLabel - $resident',
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  }).toList(),
                  onChanged: _onApartmentChanged,
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'المرجو اختيار الشقة';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Amount
                TextFormField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'المبلغ المؤدى (درهم)',
                    prefixIcon: Icon(Icons.payments_outlined),
                    suffixText: 'درهم',
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'المرجو إدخال المبلغ';
                    if (double.tryParse(v) == null) return 'مبلغ غير صالح';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Payment Method
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: _paymentMethod,
                  decoration: const InputDecoration(
                    labelText: 'طريقة الأداء',
                    prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'CASH', child: Text('نقداً (Cash)', overflow: TextOverflow.ellipsis)),
                    DropdownMenuItem(value: 'BANK_TRANSFER', child: Text('تحويل بنكي (Virement)', overflow: TextOverflow.ellipsis)),
                    DropdownMenuItem(value: 'CHEQUE', child: Text('شيك بنكي (Chèque)', overflow: TextOverflow.ellipsis)),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _paymentMethod = val);
                  },
                ),
                if (_paymentMethod == 'CHEQUE') ...[
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _chequeRefController,
                    decoration: const InputDecoration(
                      labelText: 'رقم الشيك أو المرجع البنكي',
                      prefixIcon: Icon(Icons.numbers),
                    ),
                    validator: (v) {
                      if (_paymentMethod == 'CHEQUE' && (v == null || v.isEmpty)) {
                        return 'المرجو إدخال رقم الشيك';
                      }
                      return null;
                    },
                  ),
                ],
                const SizedBox(height: 16),

                // Covered months selection
                Text(
                  'الشهور المستخلصة:',
                  style: GoogleFonts.cairo(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _availableMonths.map((m) {
                    final isSelected = _selectedMonths.contains(m);
                    return FilterChip(
                      selected: isSelected,
                      label: Text(m),
                      labelStyle: GoogleFonts.cairo(
                        fontSize: 12,
                        color: isSelected ? Colors.white : AppColors.textPrimary,
                        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      ),
                      selectedColor: AppColors.primary,
                      checkmarkColor: Colors.white,
                      onSelected: (_) => _toggleMonth(m),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 16),

                // Payment Date
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _paymentDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2030),
                    );
                    if (picked != null) {
                      setState(() => _paymentDate = picked);
                    }
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      border: Border.all(color: AppColors.border),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today_outlined, color: AppColors.textSecondary, size: 20),
                        const SizedBox(width: 12),
                        Text(
                          'تاريخ الأداء: ${DateFormat('yyyy-MM-dd').format(_paymentDate)}',
                          style: GoogleFonts.cairo(
                            fontSize: 13,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const Spacer(),
                        const Icon(Icons.arrow_drop_down, color: AppColors.textLight),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          'تأكيد الدفع وإصدار الوصل',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class PaymentReceiptSuccessDialog extends StatelessWidget {
  final String receiptNumber;
  final double amountPaid;
  final String unitNumber;
  final String residentName;
  final String? residentPhone;
  final String? receiptUrl;
  final List<String> coveredMonths;
  final double remainingArrears;

  const PaymentReceiptSuccessDialog({
    super.key,
    required this.receiptNumber,
    required this.amountPaid,
    required this.unitNumber,
    required this.residentName,
    this.residentPhone,
    this.receiptUrl,
    required this.coveredMonths,
    required this.remainingArrears,
  });

  Future<void> _viewReceipt(BuildContext context) async {
    if (receiptUrl == null || receiptUrl!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    final resolved = ApiService().resolveMediaUrl(receiptUrl);
    final uri = Uri.parse(resolved);
    try {
      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) {
        await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
      }
    } catch (_) {
      try {
        await launchUrl(uri, mode: LaunchMode.platformDefault);
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('تعذر فتح الوصل: $e')),
          );
        }
      }
    }
  }

  Future<void> _shareWhatsApp(BuildContext context) async {
    final cleanPhone = (residentPhone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    final monthsText = coveredMonths.isNotEmpty ? coveredMonths.join(', ') : 'واجب شهري';
    final resolvedUrl = ApiService().resolveMediaUrl(receiptUrl);
    final linkText = resolvedUrl.isNotEmpty ? '\nرابط تحميل الوصل:\n$resolvedUrl' : '';
    final formattedUnit = formatUnitLabel(unitNumber);

    final message = Uri.encodeComponent(
      'السلام عليكم ورحمة الله وبركاته،\n'
      'نؤكد لكم استلام واجب السانديك لـ $formattedUnit بمبلغ ${amountPaid.toStringAsFixed(0)} درهم.\n'
      'رقم الوصل: $receiptNumber\n'
      'الشهور المغطاة: $monthsText\n'
      'المستحقات المتبقية على الشقة: ${remainingArrears.toStringAsFixed(0)} درهم\n'
      '$linkText\n\n'
      'تم إيداع الوصل أيضاً في حسابكم الخاص بالتطبيق (ركن الإيصالات).\n'
      'شكراً لالتزامكم وحسن تعاونكم 🙏\n'
      'إدارة السانديك',
    );

    Uri uri;
    if (cleanPhone.isNotEmpty) {
      final phoneFormatted = cleanPhone.startsWith('0') ? '212${cleanPhone.substring(1)}' : cleanPhone;
      uri = Uri.parse('https://wa.me/$phoneFormatted?text=$message');
    } else {
      uri = Uri.parse('https://wa.me/?text=$message');
    }

    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  void _copyReceiptNumber(BuildContext context) {
    Clipboard.setData(ClipboardData(text: receiptNumber));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('تم نسخ رقم الوصل بنجاح ✓'),
        backgroundColor: AppColors.success,
        duration: Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final formattedUnit = formatUnitLabel(unitNumber);

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Success Badge
              Center(
                child: Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: AppColors.success.withAlpha(25),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.check_circle_rounded,
                    color: AppColors.success,
                    size: 44,
                  ),
                ),
              ),
              const SizedBox(height: 16),

              Text(
                'تم تأكيد الدفع وإصدار الوصل بنجاح',
                textAlign: TextAlign.center,
                style: GoogleFonts.cairo(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'تم خصم المبلغ من متأخرات الشقة وإيداع الوصل في حساب الساكن الخاص',
                textAlign: TextAlign.center,
                style: GoogleFonts.cairo(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 20),

              // Details Container
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.background,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    _buildRow(
                      label: 'رقم الوصل الرسمي',
                      value: receiptNumber,
                      isBold: true,
                      trailing: IconButton(
                        icon: const Icon(Icons.copy, size: 16, color: AppColors.primary),
                        tooltip: 'نسخ رقم الوصل',
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        onPressed: () => _copyReceiptNumber(context),
                      ),
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'الشقة والساكن',
                      value: '$formattedUnit - $residentName',
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'المبلغ المستخلص',
                      value: '${amountPaid.toStringAsFixed(0)} درهم',
                      valueColor: AppColors.success,
                      isBold: true,
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'الشهور المغطاة',
                      value: coveredMonths.isNotEmpty ? coveredMonths.join(', ') : 'واجب شهري',
                    ),
                    const Divider(height: 16),
                    _buildRow(
                      label: 'المستحقات المتبقية على الشقة',
                      value: remainingArrears <= 0 ? '0 درهم (خالصة ✓)' : '${remainingArrears.toStringAsFixed(0)} درهم',
                      valueColor: remainingArrears <= 0 ? AppColors.success : AppColors.danger,
                      isBold: true,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Privacy Reassurance Banner
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.primary.withAlpha(30)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.lock_outline, size: 18, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'تم وضع الوصل في حساب صاحب الشقة في ركن إيصالات الدفع، ولن يظهر لباقي الشقق لضمان الخصوصية التامة.',
                        style: GoogleFonts.cairo(
                          fontSize: 11,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Action Buttons
              if (receiptUrl != null && receiptUrl!.isNotEmpty) ...[
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => _viewReceipt(context),
                  icon: const Icon(Icons.picture_as_pdf_outlined, size: 20),
                  label: Text(
                    'عرض وتحميل الوصل الرسمي (PDF)',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
                const SizedBox(height: 10),
              ],

              if (residentPhone != null && residentPhone!.isNotEmpty) ...[
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.success,
                    side: const BorderSide(color: AppColors.success),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => _shareWhatsApp(context),
                  icon: const Icon(Icons.chat, color: AppColors.success, size: 20),
                  label: Text(
                    'إرسال إشعار الدفع عبر واتساب',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                ),
                const SizedBox(height: 10),
              ],

              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text(
                  'إغلاق',
                  style: GoogleFonts.cairo(
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRow({
    required String label,
    required String value,
    Color? valueColor,
    bool isBold = false,
    Widget? trailing,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.cairo(
            fontSize: 12,
            color: AppColors.textSecondary,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.end,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Flexible(
                child: Text(
                  value,
                  textAlign: TextAlign.end,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
                    color: valueColor ?? AppColors.textPrimary,
                  ),
                ),
              ),
              if (trailing != null) ...[
                const SizedBox(width: 4),
                trailing,
              ],
            ],
          ),
        ),
      ],
    );
  }
}
