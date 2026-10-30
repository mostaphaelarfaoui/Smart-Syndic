import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/auth_provider.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({super.key});

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _currentPasswordController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();

  bool _obscureCurrentPassword = true;
  bool _obscureNewPassword = true;
  bool _obscureConfirmPassword = true;
  bool _isSubmitting = false;
  String _selectedLocale = 'ar';
  String _selectedTheme = 'light';
  bool _notifyPayments = true;
  bool _notifyResidents = true;
  bool _notifyPolls = true;
  bool _notifyAnnouncements = true;

  @override
  void initState() {
    super.initState();
    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (auth.user != null) {
      _firstNameController.text = auth.user!.firstName;
      _lastNameController.text = auth.user!.lastName;
      _phoneController.text = auth.user!.phone;
      _selectedLocale = auth.user!.locale;
      _selectedTheme = auth.user!.themePreference;
      final prefs = auth.user!.notificationPreferences;
      _notifyPayments = prefs['payments'] ?? true;
      _notifyResidents = prefs['residents'] ?? true;
      _notifyPolls = prefs['polls'] ?? true;
      _notifyAnnouncements = prefs['announcements'] ?? true;
    }
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _currentPasswordController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isSubmitting = true);

    final auth = Provider.of<AuthProvider>(context, listen: false);
    
    final Map<String, dynamic> data = {
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'phone': _phoneController.text.trim(),
      'locale': _selectedLocale,
      'theme_preference': _selectedTheme,
      'notification_preferences': {
        'payments': _notifyPayments,
        'residents': _notifyResidents,
        'polls': _notifyPolls,
        'announcements': _notifyAnnouncements,
      },
    };

    final hasNewPassword = _passwordController.text.isNotEmpty;
    final hasCurrentPassword = _currentPasswordController.text.isNotEmpty;

    if (hasNewPassword || hasCurrentPassword) {
      if (!hasCurrentPassword) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('يرجى إدخال كلمة المرور الحالية لتأكيد التغيير', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
        setState(() => _isSubmitting = false);
        return;
      }
      if (!hasNewPassword) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('يرجى إدخال كلمة المرور الجديدة', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
        setState(() => _isSubmitting = false);
        return;
      }
      data['current_password'] = _currentPasswordController.text;
      data['password'] = _passwordController.text;
      data['password_confirmation'] = _passwordConfirmController.text;
    }

    final success = await auth.updateProfile(data);

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تم تحديث معلومات الحساب بنجاح', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(auth.errorMessage ?? 'حدث خطأ', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('إعدادات الحساب'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'المعلومات الشخصية',
                style: GoogleFonts.cairo(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 16),
              
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _firstNameController,
                      decoration: const InputDecoration(
                        labelText: 'الاسم',
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (v) => v!.isEmpty ? 'مطلوب' : null,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: TextFormField(
                      controller: _lastNameController,
                      decoration: const InputDecoration(
                        labelText: 'النسب',
                      ),
                      validator: (v) => v!.isEmpty ? 'مطلوب' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'رقم الهاتف',
                  prefixIcon: Icon(Icons.phone_outlined),
                ),
                validator: (v) => v!.isEmpty ? 'رقم الهاتف مطلوب' : null,
              ),

              const SizedBox(height: 24),

              Text(
                'تفضيلات اللغة والمظهر',
                style: GoogleFonts.cairo(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 12),

              // Language Selector Card
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'لغة التطبيق',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedLocale = 'ar'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              decoration: BoxDecoration(
                                color: _selectedLocale == 'ar'
                                    ? AppColors.primary.withAlpha(20)
                                    : AppColors.background,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: _selectedLocale == 'ar'
                                      ? AppColors.primary
                                      : AppColors.border,
                                  width: _selectedLocale == 'ar' ? 2 : 1,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Text('🇲🇦 ', style: TextStyle(fontSize: 16)),
                                  Text(
                                    'العربية',
                                    style: GoogleFonts.cairo(
                                      fontWeight: FontWeight.bold,
                                      color: _selectedLocale == 'ar'
                                          ? AppColors.primary
                                          : AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedLocale = 'fr'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              decoration: BoxDecoration(
                                color: _selectedLocale == 'fr'
                                    ? AppColors.primary.withAlpha(20)
                                    : AppColors.background,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: _selectedLocale == 'fr'
                                      ? AppColors.primary
                                      : AppColors.border,
                                  width: _selectedLocale == 'fr' ? 2 : 1,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Text('🇫🇷 ', style: TextStyle(fontSize: 16)),
                                  Text(
                                    'Français',
                                    style: GoogleFonts.cairo(
                                      fontWeight: FontWeight.bold,
                                      color: _selectedLocale == 'fr'
                                          ? AppColors.primary
                                          : AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 12),

              // Theme Selector Card
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'المظهر العام',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedTheme = 'light'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              decoration: BoxDecoration(
                                color: _selectedTheme == 'light'
                                    ? AppColors.primary.withAlpha(20)
                                    : AppColors.background,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: _selectedTheme == 'light'
                                      ? AppColors.primary
                                      : AppColors.border,
                                  width: _selectedTheme == 'light' ? 2 : 1,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.wb_sunny_outlined, size: 18, color: Colors.orange),
                                  const SizedBox(width: 6),
                                  Text(
                                    'النهاري',
                                    style: GoogleFonts.cairo(
                                      fontWeight: FontWeight.bold,
                                      color: _selectedTheme == 'light'
                                          ? AppColors.primary
                                          : AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: InkWell(
                            onTap: () => setState(() => _selectedTheme = 'dark'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              decoration: BoxDecoration(
                                color: _selectedTheme == 'dark'
                                    ? AppColors.primary.withAlpha(20)
                                    : AppColors.background,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: _selectedTheme == 'dark'
                                      ? AppColors.primary
                                      : AppColors.border,
                                  width: _selectedTheme == 'dark' ? 2 : 1,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.nightlight_outlined, size: 18, color: Colors.indigo),
                                  const SizedBox(width: 6),
                                  Text(
                                    'الليلي',
                                    style: GoogleFonts.cairo(
                                      fontWeight: FontWeight.bold,
                                      color: _selectedTheme == 'dark'
                                          ? AppColors.primary
                                          : AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 12),

              // Notification Preferences Card
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'تفضيلات الإشعارات والتنبيهات',
                      style: GoogleFonts.cairo(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 8),
                    SwitchListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      activeTrackColor: AppColors.primary,
                      title: Text(
                        'إشعارات الواجبات والأداء المالي',
                        style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        'تنبيهات استلام وتأكيد وصولات الأداء',
                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                      ),
                      value: _notifyPayments,
                      onChanged: (val) => setState(() => _notifyPayments = val),
                    ),
                    const Divider(height: 1),
                    SwitchListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      activeTrackColor: AppColors.primary,
                      title: Text(
                        'طلبات انضمام السكان الجدد',
                        style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        'تنبيه عند تسجيل ساكن جديد بواسطة الرمز',
                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                      ),
                      value: _notifyResidents,
                      onChanged: (val) => setState(() => _notifyResidents = val),
                    ),
                    const Divider(height: 1),
                    SwitchListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      activeTrackColor: AppColors.primary,
                      title: Text(
                        'المقترحات والتصويتات التشاركية',
                        style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        'إشعار عند إضافة مقترح أو تصويت جديد',
                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                      ),
                      value: _notifyPolls,
                      onChanged: (val) => setState(() => _notifyPolls = val),
                    ),
                    const Divider(height: 1),
                    SwitchListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      activeTrackColor: AppColors.primary,
                      title: Text(
                        'الإعلانات الرسمية وتحديثات النظام',
                        style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        'إعلانات الصيانة وتنبيهات الإدارة العامة',
                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                      ),
                      value: _notifyAnnouncements,
                      onChanged: (val) => setState(() => _notifyAnnouncements = val),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 30),
              
              Text(
                'تغيير كلمة المرور',
                style: GoogleFonts.cairo(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'اترك الخانات فارغة إذا كنت لا ترغب في تغيير كلمة المرور.',
                style: GoogleFonts.cairo(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _currentPasswordController,
                obscureText: _obscureCurrentPassword,
                decoration: InputDecoration(
                  labelText: 'كلمة المرور الحالية',
                  hintText: 'مطلوبة فقط عند تغيير كلمة المرور',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(_obscureCurrentPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 20),
                    onPressed: () => setState(() => _obscureCurrentPassword = !_obscureCurrentPassword),
                  ),
                ),
                validator: (v) {
                  if (_passwordController.text.isNotEmpty && (v == null || v.isEmpty)) {
                    return 'يرجى إدخال كلمة المرور الحالية';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _passwordController,
                obscureText: _obscureNewPassword,
                decoration: InputDecoration(
                  labelText: 'كلمة المرور الجديدة',
                  hintText: '8 أحرف على الأقل',
                  prefixIcon: const Icon(Icons.lock_reset_outlined),
                  suffixIcon: IconButton(
                    icon: Icon(_obscureNewPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 20),
                    onPressed: () => setState(() => _obscureNewPassword = !_obscureNewPassword),
                  ),
                ),
                validator: (v) {
                  if (_currentPasswordController.text.isNotEmpty && (v == null || v.isEmpty)) {
                    return 'يرجى إدخال كلمة المرور الجديدة';
                  }
                  if (v != null && v.isNotEmpty && v.length < 8) {
                    return 'يجب أن تتكون من 8 أحرف على الأقل';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _passwordConfirmController,
                obscureText: _obscureConfirmPassword,
                decoration: InputDecoration(
                  labelText: 'تأكيد كلمة المرور الجديدة',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(_obscureConfirmPassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 20),
                    onPressed: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword),
                  ),
                ),
                validator: (v) {
                  if (_passwordController.text.isNotEmpty && v != _passwordController.text) {
                    return 'كلمات المرور غير متطابقة';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 40),
              
              SizedBox(
                height: 48,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                  ),
                  child: _isSubmitting
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(
                          'حفظ التغييرات',
                          style: GoogleFonts.cairo(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
