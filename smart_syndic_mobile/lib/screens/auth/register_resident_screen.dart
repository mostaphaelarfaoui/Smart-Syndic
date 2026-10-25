import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../settings/server_settings_dialog.dart';

class RegisterResidentScreen extends StatefulWidget {
  const RegisterResidentScreen({super.key});

  @override
  State<RegisterResidentScreen> createState() => _RegisterResidentScreenState();
}

class _RegisterResidentScreenState extends State<RegisterResidentScreen> {
  final _inviteCodeController = TextEditingController();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isVerified = false;
  String? _tenantName;
  bool _isComplex = false;
  List<dynamic> _buildings = [];

  String? _selectedBlock;
  String? _selectedBuildingId;
  String? _selectedApartmentId;
  Map<String, dynamic>? _selectedApartmentData;

  String _occupancyType = 'OWNER';
  bool _obscurePassword = true;

  @override
  void dispose() {
    _inviteCodeController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _verifyCode() async {
    final code = _inviteCodeController.text.trim();
    if (code.isEmpty) return;

    final auth = context.read<AuthProvider>();
    final data = await auth.verifyInviteCode(code);

    if (data != null && mounted) {
      setState(() {
        _isVerified = true;
        _tenantName = data['tenant_name'] ?? 'الإقامة';
        _isComplex = data['is_complex'] == true;
        _buildings = (data['buildings'] as List?) ?? [];

        // Pre-select first building if available
        if (_buildings.isNotEmpty) {
          _selectedBuildingId = _buildings[0]['id'];
          _selectedBlock = _buildings[0]['block'];
        }
      });
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(auth.errorMessage ?? 'رمز الدعوة غير صالح', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  List<dynamic> _getFilteredBuildings() {
    if (_selectedBlock == null || _selectedBlock!.isEmpty) {
      return _buildings;
    }
    return _buildings.where((b) => b['block'] == _selectedBlock).toList();
  }

  List<dynamic> _getCurrentApartments() {
    if (_selectedBuildingId == null) return [];
    dynamic currentB;
    for (final b in _buildings) {
      if (b['id'] == _selectedBuildingId) {
        currentB = b;
        break;
      }
    }
    if (currentB == null) return [];
    return (currentB['apartments'] as List?) ?? [];
  }

  void _submitRegistration() async {
    if (_selectedApartmentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('يرجى اختيار الشقة للمتابعة', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    // Check Occupancy Requirement 4
    if (_selectedApartmentData != null && _selectedApartmentData!['is_occupied'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            '⚠️ هذه الشقة مشغولة ومسجل بها ساكن مسبقاً، يرجى اختيار شقة أخرى شاغرة أو التواصل مع السانديك.',
            style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
          ),
          backgroundColor: AppColors.danger,
          duration: const Duration(seconds: 4),
        ),
      );
      return;
    }

    if (_firstNameController.text.trim().isEmpty ||
        _lastNameController.text.trim().isEmpty ||
        _phoneController.text.trim().isEmpty ||
        _emailController.text.trim().isEmpty ||
        _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('يرجى تعبئة كافة الحقول المطلوبة', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final rawPhone = _phoneController.text.trim().replaceAll(RegExp(r'[\s\-\.\(\)]'), '');
    final cleanPhone = rawPhone.startsWith('212') && !rawPhone.startsWith('+') ? '+$rawPhone' : rawPhone;
    final phoneRegExp = RegExp(r'^(?:(?:\+|00)212|0)[5-7]\d{8}$');
    if (!phoneRegExp.hasMatch(cleanPhone)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('رقم هاتف مغربي غير صحيح (مثال: 0612345678 أو +212612345678)', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final auth = context.read<AuthProvider>();
    final success = await auth.registerResident(
      inviteCode: _inviteCodeController.text.trim(),
      apartmentId: _selectedApartmentId!,
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim().toLowerCase(),
      phone: cleanPhone,
      password: _passwordController.text,
      occupancyType: _occupancyType,
    );

    if (success && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              const Icon(Icons.check_circle, color: AppColors.success, size: 28),
              const SizedBox(width: 8),
              Text('تم إرسال طلبكم بنجاح', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
            ],
          ),
          content: Text(
            'تم تسجيل طلب الانضمام لدى إدارة العقار بنجاح. سيقوم السانديك بمراجعة وتفعيل حسابكم قريباً.',
            style: GoogleFonts.cairo(),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context, _emailController.text.trim().toLowerCase());
              },
              child: Text('العودة لتسجيل الدخول', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(auth.errorMessage ?? 'فشل التسجيل، يرجى المحاولة لاحقاً', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    // Collect unique blocks if available
    final blocks = _buildings
        .map((b) => b['block'] as String?)
        .where((b) => b != null && b.isNotEmpty)
        .toSet()
        .toList();

    final filteredBuildings = _getFilteredBuildings();
    final currentApartments = _getCurrentApartments();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('تسجيل ساكن جديد', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings, color: AppColors.primary),
            tooltip: 'إعدادات السيرفر',
            onPressed: () => showDialog(
              context: context,
              builder: (_) => const ServerSettingsDialog(),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!_isVerified) ...[
              // Step 1: Invite Code Card
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.border),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withAlpha(5), blurRadius: 10, offset: const Offset(0, 4)),
                  ],
                ),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withAlpha(20),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.vpn_key_outlined, size: 40, color: AppColors.primary),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'أدخل رمز دعوة الإقامة',
                      style: GoogleFonts.cairo(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'يتم تزويدك بهذا الرمز من طرف سانديك الإقامة للانضمام إلى العمارة واختيار شقتك.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary, height: 1.4),
                    ),
                    const SizedBox(height: 20),
                    TextField(
                      controller: _inviteCodeController,
                      textDirection: TextDirection.ltr,
                      textAlign: TextAlign.center,
                      style: const TextStyle(letterSpacing: 4, fontWeight: FontWeight.bold, fontSize: 20),
                      decoration: const InputDecoration(
                        hintText: 'ABCD-1234',
                      ),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: auth.isLoading ? null : _verifyCode,
                        child: auth.isLoading
                            ? const SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Text(
                                'التحقق من الرمز وعرض الشقق',
                                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14),
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ] else ...[
              // Step 2: Residence Verified Banner
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.success.withAlpha(20),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.success),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.verified, color: AppColors.success, size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'العقار: $_tenantName',
                            style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primaryDark, fontSize: 15),
                          ),
                          Text(
                            _isComplex ? 'مجمع سكني متعدد العمارات والبلوكات' : 'عمارة سكنية مستقلة',
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Block Selection (if complex with blocks)
              if (blocks.isNotEmpty) ...[
                Text('اختر البلوك:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: blocks.map((b) {
                    final isSel = _selectedBlock == b;
                    return ChoiceChip(
                      label: Text('بلوك $b', style: GoogleFonts.cairo(fontSize: 12)),
                      selected: isSel,
                      onSelected: (val) {
                        setState(() {
                          _selectedBlock = val ? b : null;
                          final filtered = _getFilteredBuildings();
                          if (filtered.isNotEmpty) {
                            _selectedBuildingId = filtered[0]['id'];
                          } else {
                            _selectedBuildingId = null;
                          }
                          _selectedApartmentId = null;
                          _selectedApartmentData = null;
                        });
                      },
                    );
                  }).toList(),
                ),
                const SizedBox(height: 14),
              ],

              // Building Selection (if more than 1 building)
              if (_buildings.length > 1) ...[
                Text('اختر العمارة:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: filteredBuildings.any((b) => b['id'] == _selectedBuildingId) ? _selectedBuildingId : null,
                  decoration: const InputDecoration(prefixIcon: Icon(Icons.business_outlined)),
                  items: filteredBuildings.map<DropdownMenuItem<String>>((b) {
                    final blockLabel = (b['block'] != null && b['block'].toString().isNotEmpty) ? '(${b['block']}) ' : '';
                    return DropdownMenuItem<String>(
                      value: b['id']?.toString(),
                      child: Text('$blockLabel${b['name']}', style: GoogleFonts.cairo(), overflow: TextOverflow.ellipsis),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedBuildingId = val;
                      _selectedApartmentId = null;
                      _selectedApartmentData = null;
                    });
                  },
                ),
                const SizedBox(height: 14),
              ],

              // Apartment Selection & Occupancy Status (Requirement 4 & 3)
              Text('اختر شقتك السكنية:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 8),
              if (currentApartments.isEmpty)
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'لا توجد شقق مسجلة في هذه العمارة حالياً',
                    style: GoogleFonts.cairo(color: AppColors.textSecondary, fontSize: 12),
                  ),
                )
              else
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: currentApartments.any((a) => a['id'] == _selectedApartmentId) ? _selectedApartmentId : null,
                  decoration: const InputDecoration(
                    labelText: 'قائمة الشقق المتوفرة',
                    prefixIcon: Icon(Icons.meeting_room_outlined),
                  ),
                  items: currentApartments.map<DropdownMenuItem<String>>((apt) {
                    final isOccupied = apt['is_occupied'] == true;
                    return DropdownMenuItem<String>(
                      value: apt['id']?.toString(),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              '${apt['unit_number']}',
                              style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: isOccupied ? AppColors.danger.withAlpha(20) : AppColors.success.withAlpha(20),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              isOccupied ? 'مشغولة ⚠️' : 'شاغرة ✓',
                              style: GoogleFonts.cairo(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isOccupied ? AppColors.danger : AppColors.success,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedApartmentId = val;
                      dynamic aptData;
                      for (final a in currentApartments) {
                        if (a['id'] == val) {
                          aptData = a;
                          break;
                        }
                      }
                      _selectedApartmentData = aptData;
                    });
                  },
                ),

              // Occupied Alert (Requirement 4)
              if (_selectedApartmentData != null && _selectedApartmentData!['is_occupied'] == true) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.danger.withAlpha(15),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.danger),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.warning_amber_rounded, color: AppColors.danger, size: 22),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '⚠️ تنبيه: هذه الشقة مشغولة ومسجل بها ساكن مسبقاً! يرجى اختيار شقة أخرى شاغرة.',
                          style: GoogleFonts.cairo(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: AppColors.danger,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 16),


              // Occupancy Type (Owner / Tenant)
              Text('الصفة القانونية:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 8),
              SegmentedButton<String>(
                segments: [
                  ButtonSegment(value: 'OWNER', label: Text('مالك الشقة', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold))),
                  ButtonSegment(value: 'TENANT', label: Text('مكترٍ مقيم', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold))),
                ],
                selected: {_occupancyType},
                onSelectionChanged: (set) => setState(() => _occupancyType = set.first),
              ),
              const SizedBox(height: 20),

              // Personal Information Section
              Text('المعلومات الشخصية:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 10),

              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _firstNameController,
                      decoration: const InputDecoration(labelText: 'الاسم الشخصي', prefixIcon: Icon(Icons.person_outline)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _lastNameController,
                      decoration: const InputDecoration(labelText: 'الاسم العائلي'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(
                  labelText: 'رقم الهاتف (مثال: +212661000000)',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(
                  labelText: 'البريد الإلكتروني',
                  prefixIcon: Icon(Icons.email_outlined),
                ),
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                textDirection: TextDirection.ltr,
                decoration: InputDecoration(
                  labelText: 'كلمة المرور',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility),
                    onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Submit Button
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: (_selectedApartmentData != null && _selectedApartmentData!['is_occupied'] == true)
                        ? Colors.grey
                        : AppColors.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: auth.isLoading ? null : _submitRegistration,
                  child: auth.isLoading
                      ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : Text(
                          'تأكيد إرسال طلب الانضمام',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ],
        ),
      ),
    );
  }
}
