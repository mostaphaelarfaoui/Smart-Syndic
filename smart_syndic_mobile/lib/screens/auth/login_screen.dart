import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';
import '../../core/services/saved_account_service.dart';
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
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _saveCredentials = true;
  List<SavedAccount> _savedAccounts = [];

  @override
  void initState() {
    super.initState();
    _initSavedAccounts();
  }

  Future<void> _initSavedAccounts() async {
    final remember = await SavedAccountService.isRememberEnabled();
    final accounts = await SavedAccountService.getSavedAccounts();
    if (mounted) {
      setState(() {
        _saveCredentials = remember;
        _savedAccounts = accounts;
      });
    }
  }

  Future<void> _loadSavedAccounts() async {
    final accounts = await SavedAccountService.getSavedAccounts();
    if (mounted) {
      setState(() {
        _savedAccounts = accounts;
      });
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    final success = await auth.login(email, password);

    if (success) {
      if (_saveCredentials) {
        final user = auth.user;
        final tenant = auth.tenant;
        await SavedAccountService.saveAccount(
          email: email,
          password: password,
          name: user?.fullName ?? email,
          role: user?.role ?? 'RESIDENT',
          residenceName: tenant?.name,
        );
        _loadSavedAccounts();
      }
    } else if (mounted) {
      if (auth.pendingResidentData != null) {
        _showPendingResidentDialog(auth.pendingResidentData!);
      } else if (auth.pendingContactInfo != null) {
        _showPendingApprovalDialog(auth.pendingContactInfo!);
      } else if (auth.suspendedAccountData != null) {
        _showSuspendedAccountDialog(auth.suspendedAccountData!);
      } else if (auth.loginErrorType == 'UNKNOWN_ERROR') {
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

  void _showSuspendedAccountDialog(Map<String, dynamic> data) {
    final role = data['role']?.toString();
    final message = data['message']?.toString() ?? 'تم إيقاف هذا الحساب مؤقتاً.';
    final syndic = data['syndic'] as Map<String, dynamic>?;
    final syndicName = syndic?['name'] as String? ?? 'سانديك الإقامة';
    final syndicPhone = syndic?['phone'] as String?;

    final contact = data['contact_info'] as Map<String, dynamic>?;
    final adminPhone = contact?['phone']?.toString();
    final adminWhatsapp = contact?['whatsapp']?.toString() ?? adminPhone;

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
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.block_rounded, color: Colors.red, size: 24),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'الحساب موقوف أو معطل',
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
                message,
                style: GoogleFonts.cairo(fontSize: 13, height: 1.5, color: AppColors.textPrimary),
              ),
              if (role == 'RESIDENT' && syndicPhone != null && syndicPhone.isNotEmpty) ...[
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
                        'للاستفسار أو إعادة التفعيل، يرجى التواصل مع السانديك:',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primaryDark),
                      ),
                      const SizedBox(height: 8),
                      Text('السانديك: $syndicName', style: GoogleFonts.cairo(fontSize: 12)),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              style: OutlinedButton.styleFrom(
                                foregroundColor: Colors.green.shade700,
                                side: BorderSide(color: Colors.green.shade600),
                                padding: const EdgeInsets.symmetric(vertical: 8),
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
                              ),
                              icon: const Icon(Icons.chat, size: 16),
                              label: Text('واتساب', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                              onPressed: () async {
                                final clean = syndicPhone.replaceAll(RegExp(r'[^0-9]'), '');
                                final msg = Uri.encodeComponent('السلام عليكم، حسابي في تطبيق Smart Syndic معطل، أرجو التحقق من الأمر وشكراً.');
                                final uri = Uri.parse('https://wa.me/$clean?text=$msg');
                                if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
                              },
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ] else if (adminWhatsapp != null && adminWhatsapp.isNotEmpty) ...[
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
                        'التواصل مع إدارة المنصة للاستفسار:',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primaryDark),
                      ),
                      const SizedBox(height: 8),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF25D366),
                          foregroundColor: Colors.white,
                          minimumSize: const Size(double.infinity, 38),
                        ),
                        icon: const Icon(Icons.chat, size: 16),
                        label: Text('مراسلة إدارة المنصة عبر واتساب', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                        onPressed: () async {
                          final clean = adminWhatsapp.replaceAll(RegExp(r'[^0-9]'), '');
                          final msg = Uri.encodeComponent('السلام عليكم، حسابي في Smart Syndic موقوف وأرغب في الاستفسار عن سبب الإيقاف.');
                          final uri = Uri.parse('https://wa.me/$clean?text=$msg');
                          if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('إغلاق', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
          ),
        ],
      ),
    );
  }

  Widget _buildSmartFeedbackCard(AuthProvider auth) {
    final errType = auth.loginErrorType;
    if (errType == null) return const SizedBox.shrink();

    Color bgColor;
    Color borderColor;
    Color iconColor;
    IconData icon;
    String title;
    String description;
    List<Widget> actions = [];

    switch (errType) {
      case 'EMAIL_NOT_FOUND':
        bgColor = Colors.orange.shade50;
        borderColor = Colors.orange.shade300;
        iconColor = Colors.orange.shade900;
        icon = Icons.person_search_outlined;
        title = 'البريد الإلكتروني غير مسجل';
        description = auth.fieldErrorEmail ?? 'لم نجد أي حساب مسجل بهذا البريد. هل ترغب في إنشاء حساب جديد؟';
        actions = [
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: [
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.primary,
                  side: const BorderSide(color: AppColors.primary),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  visualDensity: VisualDensity.compact,
                ),
                icon: const Icon(Icons.person_add_outlined, size: 14),
                label: Text('تسجيل ساكن جديد', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterResidentScreen()));
                },
              ),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.secondary,
                  side: const BorderSide(color: AppColors.secondary),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  visualDensity: VisualDensity.compact,
                ),
                icon: const Icon(Icons.business_center_outlined, size: 14),
                label: Text('تسجيل سانديك جديد', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterSyndicScreen()));
                },
              ),
            ],
          ),
        ];
        break;

      case 'INVALID_PASSWORD':
        bgColor = Colors.red.shade50;
        borderColor = Colors.red.shade200;
        iconColor = Colors.red.shade800;
        icon = Icons.lock_reset_outlined;
        title = 'كلمة المرور غير صحيحة';
        description = 'كلمة المرور التي أدخلتها غير مطابقة. تأكد من لغة لوحة المفاتيح وحالة الأحرف (كبيرة/صغيرة).';
        actions = [
          Row(
            children: [
              TextButton.icon(
                icon: Icon(_obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 16),
                label: Text(
                  _obscurePassword ? 'إظهار كلمة المرور للتأكد' : 'إخفاء كلمة المرور',
                  style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold),
                ),
                onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
              ),
              const Spacer(),
              TextButton(
                child: Text('مسح وإعادة الكتابة', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.danger)),
                onPressed: () {
                  _passwordController.clear();
                  auth.clearLoginErrors();
                },
              ),
            ],
          ),
        ];
        break;

      case 'NETWORK_ERROR':
        bgColor = Colors.blue.shade50;
        borderColor = Colors.blue.shade200;
        iconColor = Colors.blue.shade800;
        icon = Icons.wifi_off_rounded;
        title = 'تعذر الاتصال بالخادم';
        description = 'لم نتمكن من الوصول إلى السيرفر (${ApiService().baseUrl}). تأكد من تشغيل السيرفر وعنوان الـ IP.';
        actions = [
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              visualDensity: VisualDensity.compact,
            ),
            icon: const Icon(Icons.settings, size: 14),
            label: Text('تعديل عنوان السيرفر (IP)', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
            onPressed: () {
              showDialog(context: context, builder: (_) => const ServerSettingsDialog());
            },
          ),
        ];
        break;

      case 'RESIDENT_PENDING_APPROVAL':
        bgColor = Colors.amber.shade50;
        borderColor = Colors.amber.shade300;
        iconColor = Colors.amber.shade900;
        icon = Icons.hourglass_top_rounded;
        title = 'طلب الانضمام قيد المراجعة';
        description = 'حسابك مسجل وبانتظار موافقة سانديك الإقامة لتأكيد شقتك وتفعيل حسابك.';
        actions = [
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.amber.shade800,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              visualDensity: VisualDensity.compact,
            ),
            icon: const Icon(Icons.info_outline, size: 14),
            label: Text('عرض تفاصيل الطلب والتواصل مع السانديك', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
            onPressed: () {
              if (auth.pendingResidentData != null) {
                _showPendingResidentDialog(auth.pendingResidentData!);
              }
            },
          ),
        ];
        break;

      case 'SYNDIC_PENDING_APPROVAL':
        bgColor = Colors.amber.shade50;
        borderColor = Colors.amber.shade300;
        iconColor = Colors.amber.shade900;
        icon = Icons.verified_user_outlined;
        title = 'حساب السانديك قيد المراجعة';
        description = 'طلبك قيد المراجعة والتحقق من طرف إدارة المنصة.';
        actions = [
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.amber.shade800,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              visualDensity: VisualDensity.compact,
            ),
            icon: const Icon(Icons.contact_phone_outlined, size: 14),
            label: Text('معلومات التواصل مع إدارة المنصة', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
            onPressed: () {
              if (auth.pendingContactInfo != null) {
                _showPendingApprovalDialog(auth.pendingContactInfo!);
              }
            },
          ),
        ];
        break;

      case 'RESIDENT_SUSPENDED':
      case 'SYNDIC_SUSPENDED':
      case 'ACCOUNT_SUSPENDED':
        bgColor = Colors.red.shade50;
        borderColor = Colors.red.shade300;
        iconColor = Colors.red.shade900;
        icon = Icons.block_rounded;
        title = 'الحساب موقوف أو معطل';
        description = auth.errorMessage ?? 'تم إيقاف حسابك مؤقتاً. يرجى التواصل للإيضاح وإعادة التفعيل.';
        actions = [
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade800,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              visualDensity: VisualDensity.compact,
            ),
            icon: const Icon(Icons.help_outline, size: 14),
            label: Text('عرض تفاصيل التوقيف وطرق التواصل', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
            onPressed: () {
              if (auth.suspendedAccountData != null) {
                _showSuspendedAccountDialog(auth.suspendedAccountData!);
              }
            },
          ),
        ];
        break;

      default:
        bgColor = Colors.red.shade50;
        borderColor = Colors.red.shade200;
        iconColor = Colors.red.shade800;
        icon = Icons.error_outline_rounded;
        title = 'فشل تسجيل الدخول';
        description = auth.errorMessage ?? 'حدث خطأ أثناء محاولة تسجيل الدخول.';
        break;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: borderColor, width: 1.2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: iconColor, size: 22),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13, color: iconColor),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      description,
                      style: GoogleFonts.cairo(fontSize: 12, height: 1.4, color: AppColors.textPrimary),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close, size: 18),
                color: iconColor.withAlpha(160),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: () => auth.clearLoginErrors(),
              ),
            ],
          ),
          if (actions.isNotEmpty) ...[
            const SizedBox(height: 10),
            const Divider(height: 1),
            const SizedBox(height: 8),
            ...actions,
          ],
        ],
      ),
    );
  }

  void _selectAccount(SavedAccount acc) {
    context.read<AuthProvider>().clearLoginErrors();
    setState(() {
      _emailController.text = acc.email;
      _passwordController.text = acc.password;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تم تحديد الحساب: ${acc.name}', style: GoogleFonts.cairo()),
        duration: const Duration(seconds: 1),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showSavedAccountsSheet() {
    if (_savedAccounts.isEmpty) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          return Container(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.75,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withAlpha(20),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.account_circle, color: AppColors.primary, size: 24),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'الحسابات المسجلة على هذا الجهاز',
                            style: GoogleFonts.cairo(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          Text(
                            'اضغط على أي حساب لتسجيل الدخول السريع',
                            style: GoogleFonts.cairo(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const Divider(height: 1),
                const SizedBox(height: 8),
                Flexible(
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: _savedAccounts.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final acc = _savedAccounts[index];
                      Color badgeColor = AppColors.primary;
                      IconData roleIcon = Icons.apartment;
                      if (acc.role == 'ADMIN_SYNDIC') {
                        badgeColor = AppColors.primary;
                        roleIcon = Icons.admin_panel_settings;
                      } else if (acc.role == 'RESIDENT') {
                        badgeColor = Colors.teal;
                        roleIcon = Icons.person;
                      } else if (acc.role == 'SUPER_ADMIN') {
                        badgeColor = Colors.purple;
                        roleIcon = Icons.security;
                      }

                      return ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        leading: CircleAvatar(
                          backgroundColor: badgeColor.withAlpha(25),
                          child: Icon(roleIcon, color: badgeColor, size: 20),
                        ),
                        title: Text(
                          acc.name,
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              acc.email,
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                              textDirection: TextDirection.ltr,
                            ),
                            if (acc.residenceName != null && acc.residenceName!.isNotEmpty)
                              Text(
                                acc.residenceName!,
                                style: GoogleFonts.cairo(fontSize: 11, color: badgeColor, fontWeight: FontWeight.w600),
                              ),
                          ],
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: badgeColor.withAlpha(20),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                acc.roleDisplay,
                                style: GoogleFonts.cairo(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: badgeColor,
                                ),
                              ),
                            ),
                            const SizedBox(width: 4),
                            IconButton(
                              icon: const Icon(Icons.delete_outline, size: 18, color: Colors.grey),
                              tooltip: 'حذف من المحفوظات',
                              onPressed: () async {
                                await SavedAccountService.removeAccount(acc.email);
                                await _loadSavedAccounts();
                                setModalState(() {});
                                if (_savedAccounts.isEmpty && ctx.mounted) {
                                  Navigator.pop(ctx);
                                }
                              },
                            ),
                          ],
                        ),
                        onTap: () {
                          _selectAccount(acc);
                          Navigator.pop(ctx);
                        },
                      );
                    },
                  ),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.edit_outlined, size: 16),
                  label: Text('كتابة بريد إلكتروني جديد', style: GoogleFonts.cairo(fontSize: 12)),
                  onPressed: () {
                    Navigator.pop(ctx);
                    setState(() {
                      _emailController.clear();
                      _passwordController.clear();
                    });
                  },
                ),
              ],
            ),
          );
        },
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
                      color: AppColors.primary.withAlpha(25),
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
                  const SizedBox(height: 24),

                  // Smart Feedback Card for structured login feedback
                  _buildSmartFeedbackCard(auth),

                  // Email Input
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'البريد الإلكتروني',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.w600, fontSize: 13),
                      ),
                      if (_savedAccounts.isNotEmpty)
                        GestureDetector(
                          onTap: _showSavedAccountsSheet,
                          child: Text(
                            'اختيار من المحفوظات (${_savedAccounts.length}) ▾',
                            style: GoogleFonts.cairo(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    textDirection: TextDirection.ltr,
                    onTap: () {
                      if (_savedAccounts.isNotEmpty) {
                        _showSavedAccountsSheet();
                      }
                    },
                    onChanged: (_) {
                      if (auth.fieldErrorEmail != null) {
                        auth.clearLoginErrors();
                      }
                    },
                    decoration: InputDecoration(
                      hintText: 'name@example.com',
                      prefixIcon: const Icon(Icons.email_outlined),
                      suffixIcon: _savedAccounts.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.arrow_drop_down_circle_outlined, color: AppColors.primary),
                              tooltip: 'الحسابات المحفوظة (${_savedAccounts.length})',
                              onPressed: _showSavedAccountsSheet,
                            )
                          : null,
                      errorText: auth.fieldErrorEmail,
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
                    onChanged: (_) {
                      if (auth.fieldErrorPassword != null) {
                        auth.clearLoginErrors();
                      }
                    },
                    decoration: InputDecoration(
                      hintText: '••••••••',
                      prefixIcon: const Icon(Icons.lock_outline),
                      errorText: auth.fieldErrorPassword,
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
                  const SizedBox(height: 10),

                  // Remember / Save Login Information Checkbox
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    child: Row(
                      children: [
                        Checkbox(
                          value: _saveCredentials,
                          activeColor: AppColors.primary,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                          onChanged: (val) {
                            setState(() => _saveCredentials = val ?? true);
                            SavedAccountService.setRememberEnabled(_saveCredentials);
                          },
                        ),
                        Expanded(
                          child: GestureDetector(
                            onTap: () {
                              setState(() => _saveCredentials = !_saveCredentials);
                              SavedAccountService.setRememberEnabled(_saveCredentials);
                            },
                            child: Text(
                              'حفظ معلومات تسجيل الدخول على هذا الجهاز',
                              style: GoogleFonts.cairo(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          ),
                        ),
                        if (_savedAccounts.isNotEmpty)
                          TextButton.icon(
                            style: TextButton.styleFrom(
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                              visualDensity: VisualDensity.compact,
                            ),
                            icon: const Icon(Icons.history, size: 16, color: AppColors.primary),
                            label: Text(
                              '(${_savedAccounts.length})',
                              style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                            ),
                            onPressed: _showSavedAccountsSheet,
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

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
                    onPressed: () async {
                      final email = await Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const RegisterResidentScreen()),
                      );
                      if (email != null && email is String && mounted) {
                        setState(() {
                          _emailController.text = email;
                          _passwordController.clear();
                        });
                      }
                    },
                  ),
                  const SizedBox(height: 10),

                  // Register Syndic button
                  TextButton.icon(
                    icon: const Icon(Icons.business_center_outlined, color: AppColors.secondary, size: 18),
                    label: Text(
                      'تسجيل سانديك جديد (عمارة أو مجمع سكني)',
                      style: GoogleFonts.cairo(color: AppColors.secondary, fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    onPressed: () async {
                      final email = await Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const RegisterSyndicScreen()),
                      );
                      if (email != null && email is String && mounted) {
                        setState(() {
                          _emailController.text = email;
                          _passwordController.clear();
                        });
                      }
                    },
                  ),
                  const SizedBox(height: 24),

                  // Saved Accounts Section
                  if (_savedAccounts.isNotEmpty) ...[
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
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
                          Row(
                            children: [
                              const Icon(Icons.history_rounded, color: AppColors.primary, size: 18),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'الحسابات المسجلة على هذا الجهاز (${_savedAccounts.length})',
                                  style: GoogleFonts.cairo(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                              ),
                              TextButton(
                                onPressed: _showSavedAccountsSheet,
                                style: TextButton.styleFrom(
                                  padding: EdgeInsets.zero,
                                  visualDensity: VisualDensity.compact,
                                ),
                                child: Text('عرض الكل', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.primary)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: _savedAccounts.map((acc) {
                              Color chipColor = AppColors.primary;
                              IconData chipIcon = Icons.apartment;
                              if (acc.role == 'RESIDENT') {
                                chipColor = Colors.teal;
                                chipIcon = Icons.person;
                              } else if (acc.role == 'SUPER_ADMIN') {
                                chipColor = Colors.purple;
                                chipIcon = Icons.security;
                              }

                              return ActionChip(
                                backgroundColor: chipColor.withAlpha(15),
                                side: BorderSide(color: chipColor.withAlpha(60)),
                                avatar: Icon(chipIcon, size: 15, color: chipColor),
                                label: Text(
                                  '${acc.name} (${acc.roleDisplay})',
                                  style: GoogleFonts.cairo(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: chipColor,
                                  ),
                                ),
                                onPressed: () => _selectAccount(acc),
                              );
                            }).toList(),
                          ),
                        ],
                      ),
                    ),
                  ] else ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50.withAlpha(80),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue.shade100),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.info_outline, size: 20, color: AppColors.primary),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'التطبيق فارغ وجاهز لتجربة تسجيل حسابات جديدة. كل حساب تسجله وتدخل به سيتم حفظه هنا تلقائياً لسهولة الدخول بضغطة زر لاحقاً.',
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary, height: 1.5),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
