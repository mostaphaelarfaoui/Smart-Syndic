import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';

class RegisterSyndicScreen extends StatefulWidget {
  const RegisterSyndicScreen({super.key});

  @override
  State<RegisterSyndicScreen> createState() => _RegisterSyndicScreenState();
}

class _BuildingConfig {
  final TextEditingController blockController;
  final TextEditingController nameController;
  final TextEditingController floorsController;
  final TextEditingController unitsPerFloorController;
  final TextEditingController feeController;

  _BuildingConfig({
    String block = 'A',
    String name = 'عمارة 1',
    String floors = '4',
    String unitsPerFloor = '2',
    String fee = '200',
  })  : blockController = TextEditingController(text: block),
        nameController = TextEditingController(text: name),
        floorsController = TextEditingController(text: floors),
        unitsPerFloorController = TextEditingController(text: unitsPerFloor),
        feeController = TextEditingController(text: fee);

  void dispose() {
    blockController.dispose();
    nameController.dispose();
    floorsController.dispose();
    unitsPerFloorController.dispose();
    feeController.dispose();
  }
}

class _RegisterSyndicScreenState extends State<RegisterSyndicScreen> {
  final _formKey = GlobalKey<FormState>();

  // Property Type: SINGLE_BUILDING or MULTI_BUILDING_COMPLEX
  String _propertyType = 'SINGLE_BUILDING';

  // Personal Info Controllers
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  // Single Building Controllers
  final _orgNameController = TextEditingController();
  final _singleFloorsController = TextEditingController(text: '4');
  final _singleUnitsPerFloorController = TextEditingController(text: '2');
  final _singleFeeController = TextEditingController(text: '200');

  // Multi Building Complex List
  final List<_BuildingConfig> _complexBuildings = [
    _BuildingConfig(block: 'A', name: 'عمارة 1', floors: '4', unitsPerFloor: '2', fee: '200'),
    _BuildingConfig(block: 'A', name: 'عمارة 2', floors: '4', unitsPerFloor: '2', fee: '200'),
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
    _singleFloorsController.dispose();
    _singleUnitsPerFloorController.dispose();
    _singleFeeController.dispose();
    for (var b in _complexBuildings) {
      b.dispose();
    }
    super.dispose();
  }

  void _addComplexBuilding() {
    setState(() {
      final nextNum = _complexBuildings.length + 1;
      _complexBuildings.add(
        _BuildingConfig(
          block: 'B',
          name: 'عمارة $nextNum',
          floors: '4',
          unitsPerFloor: '2',
          fee: '200',
        ),
      );
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
    int? totalFloors;
    int? unitsPerFloor;
    double? defaultFee;

    if (_propertyType == 'SINGLE_BUILDING') {
      totalFloors = int.tryParse(_singleFloorsController.text.trim()) ?? 1;
      unitsPerFloor = int.tryParse(_singleUnitsPerFloorController.text.trim()) ?? 1;
      defaultFee = double.tryParse(_singleFeeController.text.trim()) ?? 200.0;
    } else {
      buildingsPayload = _complexBuildings.map((b) {
        return {
          'block': b.blockController.text.trim(),
          'name': b.nameController.text.trim(),
          'total_floors': int.tryParse(b.floorsController.text.trim()) ?? 1,
          'units_per_floor': int.tryParse(b.unitsPerFloorController.text.trim()) ?? 1,
          'monthly_fee': double.tryParse(b.feeController.text.trim()) ?? 200.0,
        };
      }).toList();
      defaultFee = double.tryParse(_complexBuildings.first.feeController.text.trim()) ?? 200.0;
    }

    final success = await auth.registerSyndic(
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      phone: _phoneController.text.trim(),
      password: _passwordController.text,
      organizationName: _orgNameController.text.trim(),
      propertyType: _propertyType,
      totalFloors: totalFloors,
      unitsPerFloor: unitsPerFloor,
      defaultMonthlyFee: defaultFee,
      buildings: buildingsPayload,
    );

    if (mounted) {
      if (success) {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (ctx) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: Row(
              children: [
                const Icon(Icons.check_circle, color: AppColors.success, size: 28),
                const SizedBox(width: 8),
                Text('تم التسجيل بنجاح', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
              ],
            ),
            content: Text(
              'تم إنشاء حساب السانديك وتوليد الشقق التابعة للعقار بنجاح! طلبكم قيد المراجعة من طرف الإدارة وسيتم تفعيله قريباً.',
              style: GoogleFonts.cairo(fontSize: 14),
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
                child: Text('العودة لتسجيل الدخول', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(auth.errorMessage ?? 'حدث خطأ أثناء التسجيل'),
            backgroundColor: AppColors.danger,
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
                // Single Building Floor & Unit Settings
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
                          const Icon(Icons.layers, size: 20, color: AppColors.primary),
                          const SizedBox(width: 8),
                          Text(
                            'توليد الشقق الذكي بالطوابق:',
                            style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _singleFloorsController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'عدد الطوابق', prefixIcon: Icon(Icons.stairs)),
                              validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'مطلوب' : null,
                              onChanged: (_) => setState(() {}),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _singleUnitsPerFloorController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'شقق كل طابق', prefixIcon: Icon(Icons.meeting_room)),
                              validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'مطلوب' : null,
                              onChanged: (_) => setState(() {}),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _singleFeeController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'الواجب الشهري للشقة (درهم)',
                          prefixIcon: Icon(Icons.payments_outlined),
                        ),
                        validator: (v) => (double.tryParse(v ?? '') ?? 0) < 0 ? 'الواجب غير صحيح' : null,
                      ),
                      const SizedBox(height: 12),
                      Builder(builder: (_) {
                        final floors = int.tryParse(_singleFloorsController.text) ?? 0;
                        final perFloor = int.tryParse(_singleUnitsPerFloorController.text) ?? 0;
                        final total = floors * perFloor;
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
                              Text(
                                'سيتم إنشاء $total شقة تلقائياً عند اكتمال التسجيل ✓',
                                style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.success),
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
                        Text(
                          'عمارات وبلوكات المجمع السكني:',
                          style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
                        ),
                        TextButton.icon(
                          onPressed: _addComplexBuilding,
                          icon: const Icon(Icons.add, size: 18),
                          label: Text('+ إضافة عمارة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
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
                                    decoration: const InputDecoration(labelText: 'اسم البلوك (مثلاً A)'),
                                    validator: (v) => v!.isEmpty ? 'مطلوب' : null,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  flex: 3,
                                  child: TextFormField(
                                    controller: b.nameController,
                                    decoration: const InputDecoration(labelText: 'اسم العمارة'),
                                    validator: (v) => v!.isEmpty ? 'مطلوب' : null,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: b.floorsController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'عدد الطوابق'),
                                    validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'خطأ' : null,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextFormField(
                                    controller: b.unitsPerFloorController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'شقق كل طابق'),
                                    validator: (v) => (int.tryParse(v ?? '') ?? 0) <= 0 ? 'خطأ' : null,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextFormField(
                                    controller: b.feeController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'الواجب (د.م)'),
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

              // Personal Information
              Text(
                '3. المعلومات الشخصية للسانديك:',
                style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _firstNameController,
                      decoration: const InputDecoration(labelText: 'الاسم الشخصي', prefixIcon: Icon(Icons.person_outline)),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'الاسم مطلوب' : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _lastNameController,
                      decoration: const InputDecoration(labelText: 'الاسم العائلي'),
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
                  labelText: 'رقم الهاتف (مثال: +212661000000)',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'رقم الهاتف مطلوب' : null,
              ),
              const SizedBox(height: 14),

              TextFormField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  labelText: 'البريد الإلكتروني',
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
