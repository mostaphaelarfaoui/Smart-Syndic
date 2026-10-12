import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../settings/server_settings_dialog.dart';
import 'register_resident_screen.dart';
import 'register_syndic_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController(text: 'syndic@alfirdaws.ma');
  final _passwordController = TextEditingController(text: 'password');
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();
    final success = await auth.login(
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (!success && mounted) {
      if (auth.pendingResidentData != null) {
        _showPendingResidentDialog(auth.pendingResidentData!);
      } else if (auth.pendingContactInfo != null) {
        _showPendingApprovalDialog(auth.pendingContactInfo!);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(auth.errorMessage ?? 'فشل تسجيل الدخول', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  void _showPendingResidentDialog(Map<String, dynamic> data) {
    final residentName = data['user']?['name'] as String? ?? 'الجار العزيز';
    final residenceName = data['residence']?['name'] as String? ?? 'الإقامة';
    final unitNumber = data['apartment']?['unit_number'] as String? ?? '';
    final buildingName = data['apartment']?['building'] as String?;
    final syndic = data['syndic'] as Map<String, dynamic>?;
    final syndicName = syndic?['name'] as String? ?? 'سانديك الإقامة';
    final syndicPhone = syndic?['phone'] as String?;

    final aptLabel = buildingName != null && buildingName.isNotEmpty
        ? 'شقة $unitNumber ($buildingName)'
        : 'شقة $unitNumber';

    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.hourglass_top_rounded, color: Colors.amber, size: 24),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'طلب الانضمام قيد المراجعة',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'مرحباً بك $residentName! 👋\nلقد تم تسجيل حسابك وإرسال طلب الانضمام إلى $residenceName بنجاح.',
                style: GoogleFonts.cairo(fontSize: 13, height: 1.6, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 12),

              // Apartment badge
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.background,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.home_outlined, size: 20, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        aptLabel,
                        style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade100,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        'في الانتظار',
                        style: GoogleFonts.cairo(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.brown.shade800),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),

              Text(
                'حسابك الآن بانتظار موافقة السانديك وتأكيد الشقة. بمجرد قبوله للطلب ستتمكن من تسجيل الدخول مباشرة للاطلاع على وضعية شقتك وإيصالاتك والمشاركة في القرارات.',
                style: GoogleFonts.cairo(fontSize: 12, height: 1.5, color: AppColors.textSecondary),
              ),

              if (syndicPhone != null && syndicPhone.isNotEmpty) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(12),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.primary.withAlpha(35)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'يمكنك التواصل مع السانديك لتسريع التفعيل:',
                        style: GoogleFonts.cairo(
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                          color: AppColors.primaryDark,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.person_outline, size: 16, color: AppColors.textSecondary),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              syndicName,
                              style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.w600),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              style: OutlinedButton.styleFrom(
                                foregroundColor: Colors.green.shade700,
                                side: BorderSide(color: Colors.green.shade600),
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              icon: const Icon(Icons.call, size: 16),
                              label: Text('اتصال', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                              onPressed: () async {
                                final clean = syndicPhone.replaceAll(RegExp(r'\s+'), '');
                                final uri = Uri.parse('tel:$clean');
                                if (await canLaunchUrl(uri)) await launchUrl(uri);
                              },
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF25D366),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                elevation: 0,
                              ),
                              icon: const Icon(Icons.chat, size: 16),
                              label: Text('واتساب', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                              onPressed: () async {
                                final clean = syndicPhone.replaceAll(RegExp(r'[^0-9]'), '');
                                final msg = Uri.encodeComponent(
                                  'السلام عليكم، أنا الساكن $residentName قمت بإنشاء حساب في تطبيق Smart Syndic لشقة $unitNumber. أرجو قبول طلب الانضمام لتفعيل الحساب، وشكراً.',
                                );
                                final uri = Uri.parse('https://wa.me/$clean?text=$msg');
                                if (await canLaunchUrl(uri)) {
                                  await launchUrl(uri, mode: LaunchMode.externalApplication);
                                }
                              },
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        actions: [
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            ),
            onPressed: () => Navigator.pop(ctx),
            child: Text(
              'حسناً، فهمت',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  void _showPendingApprovalDialog(Map<String, dynamic> contact) {
    final adminPhone = contact['phone']?.toString() ?? '+212 600 000 000';
    final adminEmail = contact['email']?.toString() ?? 'support@smartsyndic.com';
    final adminWhatsapp = contact['whatsapp']?.toString() ?? adminPhone;

    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.hourglass_top_rounded, color: Colors.amber, size: 28),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'الحساب في انتظار الموافقة',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'حساب السانديك الخاص بك قيد المراجعة حالياً من قبل إدارة المنصة (SuperAdmin). لن تتمكن من الدخول حتى يتم التفعيل.',
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
                        Text('واتساب الإدارة', style: GoogleFonts.cairo(fontSize: 12)),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.open_in_new, size: 18, color: Color(0xFF25D366)),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          onPressed: () async {
                            final clean = adminWhatsapp.replaceAll(RegExp(r'[^0-9]'), '');
                            final uri = Uri.parse('https://wa.me/$clean?text=${Uri.encodeComponent("السلام عليكم، لقد سجلت حساب سانديك جديد في Smart Syndic وأنتظر تفعيل الحساب.")}');
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
                        Expanded(
                          child: Text(adminEmail, style: GoogleFonts.cairo(fontSize: 12), overflow: TextOverflow.ellipsis),
                        ),
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
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx),
            child: Text('حسناً', style: GoogleFonts.cairo(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _quickFill(String email, String roleName) {
    setState(() {
      _emailController.text = email;
      _passwordController.text = 'password';
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تم ملء بيانات: $roleName', style: GoogleFonts.cairo()),
        duration: const Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text(''),
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
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // App Logo & Header
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.apartment_rounded,
                      size: 40,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Smart Syndic',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.cairo(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                  Text(
                    'تطبيق تدبير الملكية المشتركة وخدمات السكان',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.cairo(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 32),

                  // Email Input
                  Text(
                    'البريد الإلكتروني',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.w600, fontSize: 13),
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    textDirection: TextDirection.ltr,
                    decoration: const InputDecoration(
                      hintText: 'name@example.com',
                      prefixIcon: Icon(Icons.email_outlined),
                    ),
                    validator: (val) {
                      if (val == null || val.isEmpty) return 'يرجى إدخال البريد الإلكتروني';
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),

                  // Password Input
                  Text(
                    'كلمة المرور',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.w600, fontSize: 13),
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    textDirection: TextDirection.ltr,
                    decoration: InputDecoration(
                      hintText: '••••••••',
                      prefixIcon: const Icon(Icons.lock_outline),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_off : Icons.visibility,
                          color: AppColors.textSecondary,
                        ),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                    ),
                    validator: (val) {
                      if (val == null || val.isEmpty) return 'يرجى إدخال كلمة المرور';
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),

                  // Submit Button
                  ElevatedButton(
                    onPressed: auth.isLoading ? null : _submit,
                    child: auth.isLoading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : Text('تسجيل الدخول', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(height: 16),

                  // Register resident button
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      side: const BorderSide(color: AppColors.primary),
                    ),
                    icon: const Icon(Icons.person_add_outlined, color: AppColors.primary),
                    label: Text('تسجيل ساكن جديد (برمز الدعوة)', style: GoogleFonts.cairo(color: AppColors.primary, fontWeight: FontWeight.bold)),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const RegisterResidentScreen()),
                      );
                    },
                  ),
                  const SizedBox(height: 10),

                  // Register Syndic button (Requirement 1)
                  TextButton.icon(
                    icon: const Icon(Icons.business_center_outlined, color: AppColors.secondary, size: 18),
                    label: Text(
                      'تسجيل سانديك جديد (عمارة أو مجمع سكني)',
                      style: GoogleFonts.cairo(color: AppColors.secondary, fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const RegisterSyndicScreen()),
                      );
                    },
                  ),
                  const SizedBox(height: 24),

                  // Quick Demo Logins
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      children: [
                        Text(
                          '⚡ تسجيل دخول سريع للتجربة:',
                          style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textSecondary),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          alignment: WrapAlignment.center,
                          children: [
                            ActionChip(
                              avatar: const Icon(Icons.admin_panel_settings, size: 16, color: AppColors.primary),
                              label: Text('سانديك الإقامة', style: GoogleFonts.cairo(fontSize: 11)),
                              onPressed: () => _quickFill('syndic@alfirdaws.ma', 'سانديك الإقامة'),
                            ),
                            ActionChip(
                              avatar: const Icon(Icons.person, size: 16, color: AppColors.secondary),
                              label: Text('الساكن: يوسف', style: GoogleFonts.cairo(fontSize: 11)),
                              onPressed: () => _quickFill('youssef@example.com', 'الساكن يوسف بناني'),
                            ),
                            ActionChip(
                              avatar: const Icon(Icons.person, size: 16, color: AppColors.warning),
                              label: Text('الساكنة: فاطمة', style: GoogleFonts.cairo(fontSize: 11)),
                              onPressed: () => _quickFill('fatima@example.com', 'الساكنة فاطمة المنصوري'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
