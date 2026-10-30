import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/api_constants.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';
import '../../providers/syndic_provider.dart';
import 'stamp_settings_screen.dart';

class ResidenceSettingsScreen extends StatefulWidget {
  const ResidenceSettingsScreen({super.key});

  @override
  State<ResidenceSettingsScreen> createState() => _ResidenceSettingsScreenState();
}

class _ResidenceSettingsScreenState extends State<ResidenceSettingsScreen> {
  final _api = ApiService();
  final _formKey = GlobalKey<FormState>();

  bool _isLoading = true;
  bool _isSaving = false;

  final _nameController = TextEditingController();
  final _cityController = TextEditingController();
  final _feeController = TextEditingController();
  final _dueDayController = TextEditingController(text: '5');
  final _ribController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();

  bool _applyToExistingApartments = false;
  bool _isTransparencyEnabled = true;

  String? _invitationCode;
  String? _propertyType;
  int _totalApartments = 0;
  bool _hasStamp = false;
  double _initialTreasuryBalance = 0.0;
  double _initialBankBalance = 0.0;
  bool _initialBalanceLocked = false;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _cityController.dispose();
    _feeController.dispose();
    _dueDayController.dispose();
    _ribController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _loadSettings() async {
    setState(() => _isLoading = true);
    try {
      final res = await _api.get(ApiConstants.syndicSettings);
      final tenant = res['tenant'] as Map<String, dynamic>?;

      if (tenant != null && mounted) {
        setState(() {
          _nameController.text = tenant['name']?.toString() ?? '';
          _cityController.text = tenant['city']?.toString() ?? '';
          _propertyType = tenant['property_type']?.toString();
          _ribController.text = tenant['bank_rib']?.toString() ?? '';
          
          final fee = tenant['default_monthly_fee'];
          _feeController.text = fee != null ? fee.toString() : '200';

          final dueDay = tenant['payment_due_day'];
          _dueDayController.text = dueDay != null ? dueDay.toString() : '5';

          _isTransparencyEnabled = tenant['is_transparency_enabled'] == true;
          _invitationCode = tenant['invitation_code']?.toString();
          _totalApartments = (tenant['total_apartments'] as num?)?.toInt() ?? 0;
          _hasStamp = tenant['has_stamp'] == true;

          _initialTreasuryBalance = (tenant['initial_treasury_balance'] as num?)?.toDouble() ?? 0.0;
          _initialBankBalance = (tenant['initial_bank_balance'] as num?)?.toDouble() ?? 0.0;
          _initialBalanceLocked = tenant['initial_balance_locked'] == true;

          final contact = tenant['contact_info'] as Map<String, dynamic>?;
          _phoneController.text = contact?['phone']?.toString() ?? '';
          _emailController.text = contact?['email']?.toString() ?? '';
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تعذر تحميل إعدادات الإقامة: $e', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveSettings() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    try {
      final cleanRib = _ribController.text.trim().replaceAll(RegExp(r'\s+'), '');
      final fee = double.tryParse(_feeController.text.trim()) ?? 200.0;

      final payload = <String, dynamic>{
        'name': _nameController.text.trim(),
        'city': _cityController.text.trim().isNotEmpty ? _cityController.text.trim() : null,
        'default_monthly_fee': fee,
        'payment_due_day': int.tryParse(_dueDayController.text.trim()) ?? 5,
        'apply_to_existing_apartments': _applyToExistingApartments,
        'bank_rib': cleanRib.isNotEmpty ? cleanRib : null,
        'is_transparency_enabled': _isTransparencyEnabled,
        'contact_phone': _phoneController.text.trim().isNotEmpty ? _phoneController.text.trim() : null,
        'contact_email': _emailController.text.trim().isNotEmpty ? _emailController.text.trim() : null,
      };

      final res = await _api.put(ApiConstants.syndicSettings, payload);
      final updatedCount = res['updated_apartments_count'] ?? 0;

      if (mounted) {
        // Refresh SyndicProvider apartments & KPIs if apartments updated
        final syndic = Provider.of<SyndicProvider>(context, listen: false);
        syndic.fetchDashboardKpis();
        syndic.fetchApartments();

        String successMsg = 'تم حفظ إعدادات الإقامة بنجاح ✓';
        if (_applyToExistingApartments && updatedCount > 0) {
          successMsg = 'تم حفظ الإعدادات وتحديث الواجب الشهري لـ $updatedCount شقة ✓';
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(successMsg, style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
            backgroundColor: AppColors.success,
            duration: const Duration(seconds: 4),
          ),
        );

        // Reset the apply switch after successful apply
        setState(() {
          _applyToExistingApartments = false;
        });

        // Refresh settings state
        await _loadSettings();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('فشل حفظ الإعدادات: $e', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(
          'إعدادات الإقامة والسانديك',
          style: GoogleFonts.cairo(fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'تحديث',
            onPressed: _isLoading || _isSaving ? null : _loadSettings,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : RefreshIndicator(
              onRefresh: _loadSettings,
              color: AppColors.primary,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildHeaderCard(),
                      const SizedBox(height: 16),
                      _buildMonthlyFeeCard(),
                      const SizedBox(height: 16),
                      _buildRibCard(),
                      const SizedBox(height: 16),
                      _buildTransparencyCard(),
                      const SizedBox(height: 16),
                      _buildContactInfoCard(),
                      const SizedBox(height: 16),
                      _buildQuickToolsCard(),
                      const SizedBox(height: 24),
                      _buildSaveButton(),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ),
    );
  }

  Widget _buildHeaderCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(5),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(25),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.apartment_rounded, color: AppColors.primary, size: 26),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'بيانات وهوية الإقامة',
                      style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    Text(
                      _propertyType == 'MULTI_BUILDING_COMPLEX' ? 'مجمع سكني متعدد العمارات' : 'عمارة سكنية فردية',
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.border),
                ),
                child: Text(
                  '$_totalApartments شقة',
                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _nameController,
            decoration: const InputDecoration(
              labelText: 'اسم الإقامة / المجمع السكني',
              hintText: 'مثلاً: إقامة جنان الأندلس',
              prefixIcon: Icon(Icons.business_outlined),
            ),
            validator: (v) => (v == null || v.trim().isEmpty) ? 'الرجاء إدخال اسم الإقامة' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _cityController,
            decoration: const InputDecoration(
              labelText: 'مدينة الإقامة أو المجمع',
              hintText: 'مثال: الدار البيضاء، الرباط، طنجة...',
              prefixIcon: Icon(Icons.location_city_outlined),
            ),
          ),
          if (_invitationCode != null && _invitationCode!.isNotEmpty) ...[
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  const Icon(Icons.key_rounded, color: AppColors.primary, size: 20),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('رمز دعوة السكان:', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary)),
                        Text(
                          _invitationCode!,
                          style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, letterSpacing: 1.2),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.copy, size: 18, color: AppColors.primary),
                    tooltip: 'نسخ الرمز',
                    onPressed: () {
                      Clipboard.setData(ClipboardData(text: _invitationCode!));
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('تم نسخ رمز دعوة السكان ✓', style: GoogleFonts.cairo()),
                          duration: const Duration(seconds: 2),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildMonthlyFeeCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.primary.withAlpha(50)),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withAlpha(8),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(20),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.payments_rounded, color: AppColors.primary, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'الواجب الشهري للشقق (Cotisation)',
                      style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    Text(
                      'المبلغ المعتمد لاحتساب الاستخلاصات والمتأخرات',
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: AppColors.primary.withAlpha(12),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.primary.withAlpha(30)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.info_outline_rounded, color: AppColors.primary, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'على أساس هذا الواجب الشهري يتم احتساب المدفوعات الكاملة والجزئية، وتحديد أشهر المتأخرات تلقائياً في صفحة الشفافية المالية وسجل السكان.',
                    style: GoogleFonts.cairo(fontSize: 12, height: 1.4, color: AppColors.primary),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _feeController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            style: GoogleFonts.cairo(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primary),
            decoration: InputDecoration(
              labelText: 'مبلغ الواجب الشهري الافتراضي',
              hintText: 'مثلاً: 200',
              prefixIcon: const Icon(Icons.monetization_on_outlined, color: AppColors.primary),
              suffixText: 'د.م / شهرياً',
              suffixStyle: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primary),
            ),
            validator: (v) {
              if (v == null || v.trim().isEmpty) return 'الرجاء إدخال الواجب الشهري';
              final fee = double.tryParse(v.trim());
              if (fee == null || fee <= 0) return 'الرجاء إدخال مبلغ صحيح أكبر من 0';
              return null;
            },
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _dueDayController,
            keyboardType: TextInputType.number,
            style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            decoration: InputDecoration(
              labelText: 'يوم الاستحقاق الشهري للأداء',
              hintText: 'يوم بين 1 و 28 (الافتراضي: 5)',
              helperText: 'تُعتبر الدفعة متأخرة وتُطلق تنبيهات الأداء بعد حلول هذا اليوم من كل شهر',
              helperMaxLines: 2,
              prefixIcon: const Icon(Icons.event_available_outlined, color: AppColors.primary),
              suffixText: 'من كل شهر',
              suffixStyle: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.textSecondary),
            ),
            validator: (v) {
              if (v == null || v.trim().isEmpty) return 'الرجاء إدخال يوم الاستحقاق';
              final day = int.tryParse(v.trim());
              if (day == null || day < 1 || day > 28) return 'يجب أن يكون اليوم بين 1 و 28';
              return null;
            },
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: _applyToExistingApartments ? AppColors.warning.withAlpha(20) : AppColors.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: _applyToExistingApartments ? AppColors.warning : AppColors.border,
              ),
            ),
            child: Row(
              children: [
                Checkbox(
                  value: _applyToExistingApartments,
                  activeColor: AppColors.warning,
                  onChanged: (val) {
                    setState(() => _applyToExistingApartments = val ?? false);
                  },
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () {
                      setState(() => _applyToExistingApartments = !_applyToExistingApartments);
                    },
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'تطبيق هذا الواجب على جميع الشقق الحالية',
                          style: GoogleFonts.cairo(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: _applyToExistingApartments ? Colors.orange.shade900 : AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          'تحديث الواجب لـ $_totalApartments شقة خاضعة للواجب لإعادة احتساب المتأخرات بناءً عليه فور الحفظ.',
                          style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRibCard() {
    return Container(
      padding: const EdgeInsets.all(18),
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
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.blue.withAlpha(25),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.account_balance_rounded, color: Colors.blue, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'الحساب البنكي للإقامة (RIB)',
                      style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    Text(
                      '24 رقماً للتحويلات البنكية المباشرة',
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              if (_ribController.text.isNotEmpty)
                IconButton(
                  icon: const Icon(Icons.copy, size: 18, color: Colors.blue),
                  tooltip: 'نسخ الـ RIB',
                  onPressed: () {
                    Clipboard.setData(ClipboardData(text: _ribController.text.trim()));
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('تم نسخ رقم الـ RIB بنجاح ✓', style: GoogleFonts.cairo()),
                        duration: const Duration(seconds: 2),
                      ),
                    );
                  },
                ),
            ],
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _ribController,
            keyboardType: TextInputType.number,
            maxLength: 24,
            style: GoogleFonts.cairo(letterSpacing: 1.5, fontWeight: FontWeight.w600),
            decoration: const InputDecoration(
              labelText: 'رقم الحساب البنكي للإقامة (RIB)',
              hintText: '007780000123456789012345',
              prefixIcon: Icon(Icons.credit_card_outlined),
              helperText: 'يظهر هذا الرقم لسكان الإقامة في كشف الحساب لتسهيل التحويل البنكي (Virement)',
              helperMaxLines: 2,
            ),
            validator: (v) {
              if (v != null && v.trim().isNotEmpty) {
                final clean = v.trim().replaceAll(RegExp(r'\s+'), '');
                if (clean.length != 24 || !RegExp(r'^\d+$').hasMatch(clean)) {
                  return 'يجب أن يتكون رقم الـ RIB من 24 رقماً بالضبط';
                }
              }
              return null;
            },
          ),
        ],
      ),
    );
  }

  Widget _buildTransparencyCard() {
    return Container(
      padding: const EdgeInsets.all(18),
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
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.teal.withAlpha(25),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.visibility_rounded, color: Colors.teal, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'الشفافية المالية لسكان الإقامة',
                      style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    Text(
                      'إتاحة كشف أداءات الشقق والمصاريف للسكان',
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _isTransparencyEnabled,
            activeThumbColor: AppColors.primary,
            activeTrackColor: AppColors.primary.withAlpha(150),
            title: Text(
              _isTransparencyEnabled ? 'صفحة الشفافية المالية مفعّلة للسكان' : 'صفحة الشفافية معطلة حالياً',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
            ),
            subtitle: Text(
              _isTransparencyEnabled
                  ? 'يمكن لجميع السكان المسجلين الاطلاع على وضعية أداء الشقق والأشهر المتأخرة ومصاريف الإقامة بتفصيل.'
                  : 'لن يتمكن السكان من تصفح دفتر الشفافية المالية حتى تقوم بتفعيله.',
              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
            ),
            onChanged: (val) {
              setState(() => _isTransparencyEnabled = val);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildContactInfoCard() {
    return Container(
      padding: const EdgeInsets.all(18),
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
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.purple.withAlpha(25),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.contact_phone_rounded, color: Colors.purple, size: 24),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'بيانات التواصل مع السانديك',
                      style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    Text(
                      'تظهر للسكان في التطبيق للتواصل والاستفسارات',
                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'هاتف التواصل / واتساب السانديك',
              hintText: 'مثلاً: +212 600 000 000',
              prefixIcon: Icon(Icons.phone_outlined),
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(
              labelText: 'البريد الإلكتروني للإقامة / السانديك',
              hintText: 'مثلاً: syndic@residence.ma',
              prefixIcon: Icon(Icons.email_outlined),
            ),
            validator: (v) {
              if (v != null && v.trim().isNotEmpty) {
                if (!v.contains('@') || !v.contains('.')) {
                  return 'الرجاء إدخال بريد إلكتروني صحيح';
                }
              }
              return null;
            },
          ),
        ],
      ),
    );
  }

  Widget _buildQuickToolsCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'أدوات وإعدادات إضافية:',
            style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
          ),
          const SizedBox(height: 10),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppColors.primary.withAlpha(20),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.verified_outlined, color: AppColors.primary),
            ),
            title: Text('خاتم وطابع السانديك المعتمد', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
            subtitle: Text(
              _hasStamp ? 'تم رفع صورة الطابع المعتمد ✓' : 'لم يتم تحديد طابع الإقامة بعد',
              style: GoogleFonts.cairo(
                fontSize: 11,
                color: _hasStamp ? AppColors.success : AppColors.textSecondary,
              ),
            ),
            trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondary),
            onTap: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const StampSettingsScreen()),
              );
              if (mounted) _loadSettings();
            },
          ),
          const Divider(),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.amber.withAlpha(25),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.account_balance_wallet_outlined, color: Colors.amber),
            ),
            title: Text('الأرصدة الافتتاحية الأولية', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13)),
            subtitle: Text(
              _initialBalanceLocked
                  ? 'الصندوق: ${_initialTreasuryBalance.toStringAsFixed(2)} د.م | البنك: ${_initialBankBalance.toStringAsFixed(2)} د.م (مقفل)'
                  : 'الصندوق: ${_initialTreasuryBalance.toStringAsFixed(2)} د.م | البنك: ${_initialBankBalance.toStringAsFixed(2)} د.م',
              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
            ),
            trailing: _initialBalanceLocked
                ? const Icon(Icons.lock, size: 16, color: Colors.grey)
                : null,
          ),
        ],
      ),
    );
  }

  Widget _buildSaveButton() {
    return ElevatedButton.icon(
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        elevation: 2,
      ),
      onPressed: _isSaving ? null : _saveSettings,
      icon: _isSaving
          ? const SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
            )
          : const Icon(Icons.save_rounded, size: 22),
      label: Text(
        _isSaving ? 'جارٍ حفظ الإعدادات...' : 'حفظ إعدادات الإقامة',
        style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold),
      ),
    );
  }
}
