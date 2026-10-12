import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../settings/server_settings_dialog.dart';

class RegisterSyndicScreen extends StatefulWidget {
  const RegisterSyndicScreen({super.key});

  @override
  State<RegisterSyndicScreen> createState() => _RegisterSyndicScreenState();
}

class _BuildingConfig {
  final TextEditingController blockController;
  final TextEditingController nameController;
  final TextEditingController unitsController;
  final TextEditingController feeController;

  _BuildingConfig({
    String? block,
    String? name,
    String? units,
    String? fee,
  })  : blockController = TextEditingController(text: block),
        nameController = TextEditingController(text: name),
        unitsController = TextEditingController(text: units),
        feeController = TextEditingController(text: fee);

  void dispose() {
    blockController.dispose();
    nameController.dispose();
    unitsController.dispose();
    feeController.dispose();
  }
}

class _RegisterSyndicScreenState extends State<RegisterSyndicScreen> {
  final _formKey = GlobalKey<FormState>();

  // Property Type: SINGLE_BUILDING or MULTI_BUILDING_COMPLEX
  String _propertyType = 'SINGLE_BUILDING';

  // Personal Info Controllers (clean without default values)
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  // Single Building Controllers (clean without default values)
  final _orgNameController = TextEditingController();
  final _singleTotalUnitsController = TextEditingController();
  final _singleFeeController = TextEditingController();

  // Feature 9 — Initial balances (Cash & Bank)
  final _initialTreasuryBalanceController = TextEditingController();
  final _initialBankBalanceController = TextEditingController();

  // Multi Building Complex List (starts with 1 empty building)
  final List<_BuildingConfig> _complexBuildings = [
    _BuildingConfig(),
  ];

  bool _obscurePassword = true;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _orgNameController.dispose();
    _singleTotalUnitsController.dispose();
    _singleFeeController.dispose();
    _initialTreasuryBalanceController.dispose();
    _initialBankBalanceController.dispose();
    for (var b in _complexBuildings) {
      b.dispose();
    }
    super.dispose();
  }

  void _addComplexBuilding() {
    setState(() {
      _complexBuildings.add(_BuildingConfig());
    });
  }

  void _removeComplexBuilding(int index) {
    if (_complexBuildings.length <= 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يجب أن يحتوي المجمع السكني على عمارة واحدة على الأقل')),
      );
      return;
    }
    setState(() {
      _complexBuildings[index].dispose();
      _complexBuildings.removeAt(index);
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();

    List<Map<String, dynamic>>? buildingsPayload;
    int? totalUnits;
    double? defaultFee;

    if (_propertyType == 'SINGLE_BUILDING') {
      totalUnits = int.tryParse(_singleTotalUnitsController.text.trim()) ?? 0;
      defaultFee = double.tryParse(_singleFeeController.text.trim()) ?? 200.0;
    } else {
      buildingsPayload = _complexBuildings.map((b) {
        return {
          'block': b.blockController.text.trim(),
          'name': b.nameController.text.trim(),
          'total_units': int.tryParse(b.unitsController.text.trim()) ?? 0,
          'monthly_fee': double.tryParse(b.feeController.text.trim()) ?? 200.0,
        };
      }).toList();
      defaultFee = double.tryParse(_complexBuildings.first.feeController.text.trim()) ?? 200.0;
    }

    final rawPhone = _phoneController.text.trim().replaceAll(RegExp(r'[\s\-\.\(\)]'), '');
    final cleanPhone = rawPhone.startsWith('212') && !rawPhone.startsWith('+') ? '+$rawPhone' : rawPhone;

    final initialTreasury = double.tryParse(_initialTreasuryBalanceController.text.trim());
    final initialBank = double.tryParse(_initialBankBalanceController.text.trim());

    final success = await auth.registerSyndic(
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim().toLowerCase(),
      phone: cleanPhone,
      password: _passwordController.text,
      organizationName: _orgNameController.text.trim(),
      propertyType: _propertyType,
      totalUnits: totalUnits,
      defaultMonthlyFee: defaultFee,
      initialTreasuryBalance: initialTreasury,
      initialBankBalance: initialBank,
      buildings: buildingsPayload,
    );

    if (mounted) {
      if (success) {
        final contact = auth.pendingContactInfo;
        final adminPhone = contact?['phone'] ?? '+212 600 000 000';
        final adminEmail = contact?['email'] ?? 'support@smartsyndic.com';
        final adminWhatsapp = contact?['whatsapp'] ?? adminPhone;

        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (ctx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Row(
              children: [
                const Icon(Icons.hourglass_top_rounded, color: Colors.amber, size: 28),
                const SizedBox(width: 8),
                Expanded(
                  child: Text('طلبك في انتظار الموافقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'تم تسجيل حساب السانديك بنجاح! الإقامة حالياً في انتظار تفعيل السوبر أدمن (SuperAdmin) قبل أن تتمكن من الدخول.',
                    style: GoogleFonts.cairo(fontSize: 13, height: 1.5),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withAlpha(15),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.primary.withAlpha(40)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'معلومات التواصل مع إدارة المنصة للتفعيل:',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primary),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const Icon(Icons.phone, size: 16, color: AppColors.textSecondary),
                            const SizedBox(width: 6),
                            Text(adminPhone, style: GoogleFonts.cairo(fontSize: 12)),
                            const Spacer(),
                            IconButton(
                              icon: const Icon(Icons.call, size: 18, color: Colors.green),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: () async {
                                final uri = Uri.parse('tel:${adminPhone.replaceAll(" ", "")}');
                                if (await canLaunchUrl(uri)) await launchUrl(uri);
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(Icons.chat, size: 16, color: Color(0xFF25D366)),
                            const SizedBox(width: 6),
                            Text('واتساب الدعم', style: GoogleFonts.cairo(fontSize: 12)),
                            const Spacer(),
                            IconButton(
                              icon: const Icon(Icons.open_in_new, size: 18, color: Color(0xFF25D366)),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: () async {
                                final clean = adminWhatsapp.replaceAll(RegExp(r'[^0-9]'), '');
                                final uri = Uri.parse('https://wa.me/$clean?text=${Uri.encodeComponent("السلام عليكم، لقد سجلت حساب سانديك جديد في Smart Syndic وأنتظر الموافقة.")}');
                                if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(Icons.email_outlined, size: 16, color: AppColors.textSecondary),
                            const SizedBox(width: 6),
                            Expanded(child: Text(adminEmail, style: GoogleFonts.cairo(fontSize: 12), overflow: TextOverflow.ellipsis)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: () {
                  Navigator.pop(ctx);
                  Navigator.pop(context);
                },
                child: Text('فهمت، العودة لتسجيل الدخول', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              auth.errorMessage ?? 'حدث خطأ أثناء التسجيل',
              style: GoogleFonts.cairo(fontWeight: FontWeight.w600),
            ),
            backgroundColor: AppColors.danger,
            duration: const Duration(seconds: 6),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('تسجيل سانديك جديد', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
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
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Hero Info
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(15),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.primary.withAlpha(40)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.business_center, color: AppColors.primary, size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'اختر طبيعة العقار لتهيئة الشقق والعمارات تلقائياً وتسهيل إدارة مجمعك السكني.',
                        style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textPrimary, height: 1.4),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Property Type Selection Cards
              Text(
                '1. نوع العقار الخاضع لإدارتك:',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildTypeCard(
                      type: 'SINGLE_BUILDING',
                      title: 'عمارة واحدة',
                      subtitle: 'عمارة سكنية مستقلة بها شقق',
                      icon: Icons.apartment,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildTypeCard(
                      type: 'MULTI_BUILDING_COMPLEX',
                      title: 'مجمع سكني',
                      subtitle: 'مجمع يضم عدة بلوكات وعمارات',
                      icon: Icons.domain,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Property Details Form
              Text(
                '2. بيانات العقار والشقق:',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 12),

              TextFormField(
                controller: _orgNameController,
                decoration: InputDecoration(
                  labelText: _propertyType == 'SINGLE_BUILDING' ? 'اسم العمارة أو الإقامة' : 'اسم المجمع السكني',
                  hintText: _propertyType == 'SINGLE_BUILDING' ? 'مثال: عمارة الأمل' : 'مثال: إقامة الفردوس السكنية',
                  prefixIcon: const Icon(Icons.home_work_outlined),
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'اسم العقار مطلوب' : null,
              ),
              const SizedBox(height: 16),

              // Configuration based on Property Type
              if (_propertyType == 'SINGLE_BUILDING') ...[
                // Single Building Unit Settings
                Container(
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
                        children: [
                          const Icon(Icons.meeting_room_outlined, size: 20, color: AppColors.primary),
                          const SizedBox(width: 8),
                          Text(
                            'بيانات شقق العمارة:',
                            style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _singleTotalUnitsController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'عدد الشقق في العمارة',
                          hintText: 'مثلاً: 12',
                          prefixIcon: Icon(Icons.meeting_room_outlined),
                        ),
                        validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'الرجاء إدخال عدد الشقق' : null,
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _singleFeeController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'الواجب الشهري للشقة (درهم)',
                          hintText: 'مثلاً: 200',
                          prefixIcon: Icon(Icons.payments_outlined),
                        ),
                        validator: (v) => (double.tryParse(v ?? '') ?? 0) < 0 ? 'الواجب غير صحيح' : null,
                      ),
                      const SizedBox(height: 12),
                      Builder(builder: (_) {
                        final total = int.tryParse(_singleTotalUnitsController.text) ?? 0;
                        if (total <= 0) return const SizedBox.shrink();
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: AppColors.success.withAlpha(15),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.check_circle_outline, size: 16, color: AppColors.success),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'سيتم إنشاء $total شقة تلقائياً عند اكتمال التسجيل ✓',
                                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.success),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                    ],
                  ),
                ),
              ] else ...[
                // Multi-Building Complex Settings
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            'عمارات وبلوكات المجمع السكني:',
                            style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 8),
                        TextButton.icon(
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            minimumSize: Size.zero,
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          ),
                          onPressed: _addComplexBuilding,
                          icon: const Icon(Icons.add, size: 16),
                          label: Text('+ إضافة عمارة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ...List.generate(_complexBuildings.length, (index) {
                      final b = _complexBuildings[index];
                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(14),
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
                                Text(
                                  'عمارة #${index + 1}',
                                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.primary),
                                ),
                                if (_complexBuildings.length > 1)
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline, color: AppColors.danger, size: 20),
                                    onPressed: () => _removeComplexBuilding(index),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    controller: b.blockController,
                                    decoration: const InputDecoration(
                                      labelText: 'اسم البلوك',
                                      hintText: 'مثلاً: A أو 1',
                                    ),
                                    validator: (v) => (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  flex: 3,
                                  child: TextFormField(
                                    controller: b.nameController,
                                    decoration: const InputDecoration(
                                      labelText: 'اسم العمارة',
                                      hintText: 'مثلاً: عمارة الياسمين',
                                    ),
                                    validator: (v) => (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  flex: 3,
                                  child: TextFormField(
                                    controller: b.unitsController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(
                                      labelText: 'عدد الشقق في العمارة',
                                      hintText: 'مثلاً: 10',
                                      prefixIcon: Icon(Icons.meeting_room_outlined),
                                    ),
                                    validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'خطأ' : null,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    controller: b.feeController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(
                                      labelText: 'الواجب (د.م)',
                                      hintText: 'مثلاً: 200',
                                      prefixIcon: Icon(Icons.payments_outlined),
                                    ),
                                    validator: (v) => (double.tryParse(v ?? '') ?? 0) < 0 ? 'خطأ' : null,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    }),
                  ],
                ),
              ],
              const SizedBox(height: 24),

              // Feature 9: Initial Balances (Cash & Bank)
              Text(
                '3. الأرصدة الافتتاحية الأولية (اختياري):',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 6),
              Text(
                'أدخل المبالغ المتوفرة حالياً في صندوق الإقامة وحسابها البنكي قبل بدء التحصيل عبر التطبيق (يتم قفلها تلقائياً).',
                style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 12),

              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    TextFormField(
                      controller: _initialTreasuryBalanceController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'رصيد الصندوق النقدي (كاش)',
                        hintText: 'مثلاً: 1500.00',
                        prefixIcon: Icon(Icons.account_balance_wallet_outlined, color: Colors.green),
                        suffixText: 'د.م',
                      ),
                      validator: (v) {
                        if (v != null && v.trim().isNotEmpty) {
                          if (double.tryParse(v.trim()) == null || double.parse(v.trim()) < 0) {
                            return 'يرجى إدخال مبلغ صحيح';
                          }
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _initialBankBalanceController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'رصيد الحساب البنكي',
                        hintText: 'مثلاً: 12500.00',
                        prefixIcon: Icon(Icons.account_balance_outlined, color: Colors.blue),
                        suffixText: 'د.م',
                      ),
                      validator: (v) {
                        if (v != null && v.trim().isNotEmpty) {
                          if (double.tryParse(v.trim()) == null || double.parse(v.trim()) < 0) {
                            return 'يرجى إدخال مبلغ صحيح';
                          }
                        }
                        return null;
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Personal Information
              Text(
                '4. المعلومات الشخصية للسانديك:',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _firstNameController,
                      decoration: const InputDecoration(
                        labelText: 'الاسم الشخصي',
                        hintText: 'مثلاً: محمد',
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'الاسم مطلوب' : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _lastNameController,
                      decoration: const InputDecoration(
                        labelText: 'الاسم العائلي',
                        hintText: 'مثلاً: الإدريسي',
                      ),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'النسب مطلوب' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),

              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'رقم الهاتف',
                  hintText: 'مثلاً: +212661000000',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return 'رقم الهاتف مطلوب';
                  final cleaned = v.trim().replaceAll(RegExp(r'[\s\-\.\(\)]'), '');
                  final normalized = cleaned.startsWith('212') && !cleaned.startsWith('+') ? '+$cleaned' : cleaned;
                  final regExp = RegExp(r'^(?:(?:\+|00)212|0)[5-7]\d{8}$');
                  if (!regExp.hasMatch(normalized)) {
                    return 'رقم مغربي غير صحيح (مثال: 0612345678 أو +212612345678)';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 14),

              TextFormField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  labelText: 'البريد الإلكتروني',
                  hintText: 'مثلاً: syndic@example.com',
                  prefixIcon: Icon(Icons.email_outlined),
                ),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return 'البريد الإلكتروني مطلوب';
                  if (!v.contains('@')) return 'صيغة البريد الإلكتروني غير صحيحة';
                  return null;
                },
              ),
              const SizedBox(height: 14),

              TextFormField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                decoration: InputDecoration(
                  labelText: 'كلمة المرور',
                  hintText: '8 أحرف على الأقل',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility),
                    onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                  ),
                ),
                validator: (v) {
                  if (v == null || v.isEmpty) return 'كلمة المرور مطلوبة';
                  if (v.length < 8) return 'يجب ألا تقل عن 8 أحرف';
                  return null;
                },
              ),
              const SizedBox(height: 14),

              TextFormField(
                controller: _confirmPasswordController,
                obscureText: _obscurePassword,
                decoration: const InputDecoration(
                  labelText: 'تأكيد كلمة المرور',
                  hintText: 'أعد كتابة كلمة المرور',
                  prefixIcon: Icon(Icons.lock_outline),
                ),
                validator: (v) {
                  if (v != _passwordController.text) return 'كلمات المرور غير متطابقة';
                  return null;
                },
              ),
              const SizedBox(height: 30),

              // Submit Button
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: auth.isLoading ? null : _submit,
                  child: auth.isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : Text(
                          'تأكيد تسجيل السانديك وتهيئة العقار',
                          style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTypeCard({
    required String type,
    required String title,
    required String subtitle,
    required IconData icon,
  }) {
    final isSelected = _propertyType == type;

    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => setState(() => _propertyType = type),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary.withAlpha(20) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 2 : 1,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withAlpha(30),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ]
              : [],
        ),
        child: Column(
          children: [
            Icon(
              icon,
              size: 32,
              color: isSelected ? AppColors.primary : AppColors.textSecondary,
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: GoogleFonts.cairo(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: isSelected ? AppColors.primary : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: GoogleFonts.cairo(
                fontSize: 10,
                color: AppColors.textSecondary,
                height: 1.3,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
