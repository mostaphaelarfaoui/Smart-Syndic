import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/resident_provider.dart';
import '../settings/server_settings_dialog.dart';
import 'tabs/resident_payments_tab.dart';

class ResidentProfileScreen extends StatefulWidget {
  const ResidentProfileScreen({super.key});

  @override
  State<ResidentProfileScreen> createState() => _ResidentProfileScreenState();
}

class _ResidentProfileScreenState extends State<ResidentProfileScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ResidentProvider>().fetchProfile();
    });
  }

  Future<void> _makePhoneCall(String phone) async {
    final cleanPhone = phone.replaceAll(RegExp(r'\s+'), '');
    final uri = Uri.parse('tel:$cleanPhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _openWhatsApp(String phone, String name) async {
    var cleanPhone = phone.replaceAll(RegExp(r'\s+'), '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '212${cleanPhone.substring(1)}';
    } else if (cleanPhone.startsWith('+')) {
      cleanPhone = cleanPhone.substring(1);
    }
    final message = Uri.encodeComponent('السلام عليكم أخي $name، بخصوص الإقامة.');
    final uri = Uri.parse('https://wa.me/$cleanPhone?text=$message');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _copyToClipboard(BuildContext context, String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تم نسخ $label بنجاح ✓', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.success,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  Future<void> _showEditProfileDialog(BuildContext context, Map<String, dynamic>? user) async {
    final firstNameController = TextEditingController(text: user?['first_name'] ?? '');
    final lastNameController = TextEditingController(text: user?['last_name'] ?? '');
    final phoneController = TextEditingController(text: user?['phone'] ?? '');
    final formKey = GlobalKey<FormState>();
    bool isSaving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Text(
            'تعديل المعلومات الشخصية',
            style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 18),
          ),
          content: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextFormField(
                    controller: firstNameController,
                    decoration: const InputDecoration(labelText: 'الاسم الشخصي', prefixIcon: Icon(Icons.person_outline)),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'الاسم مطلوب' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: lastNameController,
                    decoration: const InputDecoration(labelText: 'الاسم العائلي', prefixIcon: Icon(Icons.person_outline)),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'النسب مطلوب' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: phoneController,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(labelText: 'رقم الهاتف', prefixIcon: Icon(Icons.phone_outlined)),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'رقم الهاتف مطلوب' : null,
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: isSaving
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;
                      setDialogState(() => isSaving = true);

                      final auth = Provider.of<AuthProvider>(context, listen: false);
                      final success = await auth.updateProfile({
                        'first_name': firstNameController.text.trim(),
                        'last_name': lastNameController.text.trim(),
                        'phone': phoneController.text.trim(),
                      });

                      if (context.mounted) {
                        Navigator.pop(ctx);
                        if (success) {
                          context.read<ResidentProvider>().fetchProfile();
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('تم تحديث المعلومات الشخصية بنجاح ✓'),
                              backgroundColor: AppColors.success,
                            ),
                          );
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(auth.errorMessage ?? 'حدث خطأ أثناء التحديث'),
                              backgroundColor: AppColors.danger,
                            ),
                          );
                        }
                      }
                    },
              child: isSaving
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : Text('حفظ التعديلات', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showChangePasswordDialog(BuildContext context) async {
    final passwordController = TextEditingController();
    final confirmController = TextEditingController();
    final formKey = GlobalKey<FormState>();
    bool isSaving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Text(
            'تغيير كلمة المرور',
            style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 18),
          ),
          content: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextFormField(
                    controller: passwordController,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: 'كلمة المرور الجديدة', prefixIcon: Icon(Icons.lock_outline)),
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'كلمة المرور مطلوبة';
                      if (v.length < 8) return 'يجب أن لا تقل عن 8 أحرف';
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: confirmController,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور', prefixIcon: Icon(Icons.lock_outline)),
                    validator: (v) {
                      if (v != passwordController.text) return 'كلمات المرور غير متطابقة';
                      return null;
                    },
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: isSaving
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;
                      setDialogState(() => isSaving = true);

                      final auth = Provider.of<AuthProvider>(context, listen: false);
                      final success = await auth.updateProfile({
                        'password': passwordController.text,
                        'password_confirmation': confirmController.text,
                      });

                      if (context.mounted) {
                        Navigator.pop(ctx);
                        if (success) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('تم تغيير كلمة المرور بنجاح ✓'),
                              backgroundColor: AppColors.success,
                            ),
                          );
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(auth.errorMessage ?? 'حدث خطأ في تغيير كلمة المرور'),
                              backgroundColor: AppColors.danger,
                            ),
                          );
                        }
                      }
                    },
              child: isSaving
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : Text('تحديث كلمة المرور', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('تسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        content: Text('هل أنت متأكد من رغبتك في تسجيل الخروج من حساب الساكن؟', style: GoogleFonts.cairo()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo()),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('تسجيل الخروج', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      Provider.of<ResidentProvider>(context, listen: false).clear();
      await Provider.of<AuthProvider>(context, listen: false).logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final res = context.watch<ResidentProvider>();
    final profile = res.profile;
    final user = profile?['user'];
    final apartment = profile?['apartment'];
    final residence = profile?['residence'];
    final syndic = profile?['syndic'];
    final financial = profile?['financial_summary'];

    final fullName = user?['full_name'] ?? 'ساكن الإقامة';
    final email = user?['email'] ?? '-';
    final phone = user?['phone'] ?? '-';
    final unitNumber = apartment?['unit_number'] ?? '-';
    final floor = apartment?['floor']?.toString() ?? '-';
    final buildingName = apartment?['building_name'] ?? '-';
    final monthlyFee = (apartment?['monthly_fee'] as num?)?.toDouble() ?? 0.0;
    final occupancyType = apartment?['occupancy_type'] == 'TENANT' ? 'مكترٍ' : 'مالك الشقة';
    final residenceName = residence?['name'] ?? 'الإقامة السكنية';
    final bankRib = residence?['bank_rib'] ?? '';

    final totalDue = (financial?['total_due'] as num?)?.toDouble() ?? 0.0;
    final totalPaid = (financial?['total_paid'] as num?)?.toDouble() ?? 0.0;
    final paymentsCount = financial?['payments_count'] ?? 0;
    final isUpToDate = financial?['is_up_to_date'] ?? (totalDue <= 0);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('الملف الشخصي للساكن', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'تحديث البيانات',
            onPressed: () => res.fetchProfile(),
          ),
          IconButton(
            icon: const Icon(Icons.dns_outlined, color: AppColors.textSecondary),
            tooltip: 'إعدادات الخادم',
            onPressed: () => showDialog(
              context: context,
              builder: (_) => const ServerSettingsDialog(),
            ),
          ),
        ],
      ),
      body: res.isLoading && profile == null
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : RefreshIndicator(
              onRefresh: () => res.fetchProfile(),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    // Profile Header Card
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF0F766E), Color(0xFF14B8A6)],
                          begin: Alignment.topRight,
                          end: Alignment.bottomLeft,
                        ),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF0F766E).withAlpha(50),
                            blurRadius: 15,
                            offset: const Offset(0, 8),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 32,
                                backgroundColor: Colors.white.withAlpha(220),
                                child: Text(
                                  fullName.isNotEmpty ? fullName.substring(0, 1) : 'S',
                                  style: GoogleFonts.cairo(
                                    fontSize: 26,
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF0F766E),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Flexible(
                                          child: Text(
                                            fullName,
                                            style: GoogleFonts.cairo(
                                              fontSize: 18,
                                              fontWeight: FontWeight.bold,
                                              color: Colors.white,
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.white.withAlpha(50),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Text(
                                            occupancyType,
                                            style: GoogleFonts.cairo(
                                              fontSize: 11,
                                              color: Colors.white,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      phone,
                                      style: GoogleFonts.cairo(fontSize: 13, color: Colors.white.withAlpha(220)),
                                    ),
                                    Text(
                                      email,
                                      style: GoogleFonts.cairo(fontSize: 12, color: Colors.white.withAlpha(190)),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.black.withAlpha(40),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.apartment, color: Colors.white, size: 18),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    '$residenceName • $unitNumber • $buildingName',
                                    style: GoogleFonts.cairo(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.white,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Financial Summary Card
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.border),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withAlpha(8),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'الوضعية المالية للمستحقات',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: (isUpToDate ? AppColors.success : AppColors.danger).withAlpha(20),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      isUpToDate ? Icons.check_circle : Icons.warning_amber_rounded,
                                      color: isUpToDate ? AppColors.success : AppColors.danger,
                                      size: 14,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      isUpToDate ? 'الواجبات مسددة' : 'متأخرات: ${totalDue.toStringAsFixed(0)} د.م',
                                      style: GoogleFonts.cairo(
                                        color: isUpToDate ? AppColors.success : AppColors.danger,
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const Divider(height: 24),
                          Row(
                            children: [
                              Expanded(
                                child: _buildStatItem(
                                  title: 'الواجب الشهري',
                                  value: '${monthlyFee.toStringAsFixed(0)} درهم',
                                  color: AppColors.primary,
                                  icon: Icons.calendar_month,
                                ),
                              ),
                              Container(width: 1, height: 40, color: AppColors.border),
                              Expanded(
                                child: _buildStatItem(
                                  title: 'إجمالي المؤدى',
                                  value: '${totalPaid.toStringAsFixed(0)} درهم',
                                  color: AppColors.success,
                                  icon: Icons.account_balance_wallet,
                                ),
                              ),
                              Container(width: 1, height: 40, color: AppColors.border),
                              Expanded(
                                child: _buildStatItem(
                                  title: 'عدد الإيصالات',
                                  value: '$paymentsCount وصل',
                                  color: AppColors.secondary,
                                  icon: Icons.receipt_long,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Dedicated Receipts Card for the Resident's Apartment
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.border),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withAlpha(8),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: AppColors.primary.withAlpha(20),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: const Icon(Icons.receipt_long, color: AppColors.primary, size: 20),
                                  ),
                                  const SizedBox(width: 10),
                                  Text(
                                    'إيصالات الدفع الخاصة بالشقة',
                                    style: GoogleFonts.cairo(
                                      fontSize: 15,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withAlpha(15),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.lock, size: 12, color: AppColors.primary),
                                    const SizedBox(width: 4),
                                    Text(
                                      'خاصة بك فقط',
                                      style: GoogleFonts.cairo(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.primary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'جميع وصولات أداء واجب السانديك محتفظ بها في حسابك الخاص ومتاحة للتحميل في أي وقت بصيغة PDF. لا تظهر هذه الوصولات لأي شقة أخرى ضماناً لخصوصيتك التامة.',
                            style: GoogleFonts.cairo(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                              height: 1.5,
                            ),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                              minimumSize: const Size(double.infinity, 44),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              elevation: 0,
                            ),
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => Scaffold(
                                    appBar: AppBar(
                                      title: Text('إيصالات الدفع الرسمية', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                                    ),
                                    body: const ResidentPaymentsTab(),
                                  ),
                                ),
                              );
                            },
                            icon: const Icon(Icons.download_rounded, size: 18),
                            label: Text(
                              'عرض وتحميل جميع إيصالاتي ($paymentsCount وصل)',
                              style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Apartment Details Card
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.home_work_outlined, color: AppColors.primary, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'بيانات الشقة والعمارة',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          _buildDetailRow(label: 'رقم الشقة:', value: unitNumber, icon: Icons.meeting_room_outlined),
                          _buildDetailRow(label: 'الطابق:', value: 'الطابق $floor', icon: Icons.layers_outlined),
                          _buildDetailRow(label: 'العمارة / المجمع:', value: buildingName, icon: Icons.business_outlined),
                          _buildDetailRow(label: 'الصفة القانونية:', value: occupancyType, icon: Icons.badge_outlined),
                          _buildDetailRow(
                            label: 'جهة الاتصال:',
                            value: (apartment?['is_primary_contact'] ?? true) ? 'جهة اتصال رئيسية ✓' : 'ساكن إضافي',
                            icon: Icons.verified_user_outlined,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Bank RIB Card
                    if (bankRib.isNotEmpty) ...[
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    const Icon(Icons.account_balance, color: AppColors.primary, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      'الحساب البنكي للإقامة (RIB)',
                                      style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                                    ),
                                  ],
                                ),
                                IconButton(
                                  icon: const Icon(Icons.copy, color: AppColors.primary, size: 20),
                                  tooltip: 'نسخ الـ RIB',
                                  onPressed: () => _copyToClipboard(context, bankRib, 'رقم الحساب البنكي (RIB)'),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            InkWell(
                              onTap: () => _copyToClipboard(context, bankRib, 'رقم الحساب البنكي (RIB)'),
                              child: Container(
                                width: double.infinity,
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: AppColors.border),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        bankRib,
                                        style: GoogleFonts.robotoMono(
                                          fontSize: 14,
                                          fontWeight: FontWeight.bold,
                                          letterSpacing: 1.2,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                    ),
                                    const Icon(Icons.copy, size: 16, color: AppColors.textSecondary),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'استخدم هذا الرقم لتحويل واجبات السانديك مباشرة عبر تطبيقك البنكي.',
                              style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Syndic Contact Card
                    if (syndic != null) ...[
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.admin_panel_settings_outlined, color: AppColors.primary, size: 20),
                                const SizedBox(width: 8),
                                Text(
                                  'السانديك المسؤول عن الإقامة',
                                  style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                CircleAvatar(
                                  radius: 20,
                                  backgroundColor: AppColors.primary.withAlpha(20),
                                  child: const Icon(Icons.person, color: AppColors.primary, size: 22),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        syndic['name'] ?? 'السانديك',
                                        style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                                      ),
                                      if (syndic['phone'] != null)
                                        Text(
                                          syndic['phone'],
                                          style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                        ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            if (syndic['phone'] != null) ...[
                              const SizedBox(height: 14),
                              Row(
                                children: [
                                  Expanded(
                                    child: ElevatedButton.icon(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppColors.primary,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 8),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => _makePhoneCall(syndic['phone']),
                                      icon: const Icon(Icons.phone, size: 16),
                                      label: Text('اتصال بالسانديك', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: const Color(0xFF25D366),
                                        side: const BorderSide(color: Color(0xFF25D366)),
                                        padding: const EdgeInsets.symmetric(vertical: 8),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => _openWhatsApp(syndic['phone'], syndic['name'] ?? ''),
                                      icon: const Icon(Icons.chat, size: 16),
                                      label: Text('واتساب', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Account Settings & Security Actions
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.security, color: AppColors.primary, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'إعدادات الحساب والأمان',
                                style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.primary.withAlpha(20),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(Icons.edit_outlined, color: AppColors.primary, size: 20),
                            ),
                            title: Text('تعديل المعلومات الشخصية', style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold)),
                            subtitle: Text('تحديث الاسم ورقم الهاتف', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight)),
                            trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondary),
                            onTap: () => _showEditProfileDialog(context, user),
                          ),
                          const Divider(height: 16),
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.secondary.withAlpha(20),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(Icons.lock_reset, color: AppColors.secondary, size: 20),
                            ),
                            title: Text('تغيير كلمة المرور', style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold)),
                            subtitle: Text('تحديث كلمة المرور لحماية الحساب', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight)),
                            trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textSecondary),
                            onTap: () => _showChangePasswordDialog(context),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Logout Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.danger,
                          side: const BorderSide(color: AppColors.danger),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        onPressed: () => _handleLogout(context),
                        icon: const Icon(Icons.logout, size: 18),
                        label: Text(
                          'تسجيل الخروج من الحساب',
                          style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.bold),
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

  Widget _buildStatItem({
    required String title,
    required String value,
    required Color color,
    required IconData icon,
  }) {
    return Column(
      children: [
        Icon(icon, color: color, size: 18),
        const SizedBox(height: 4),
        Text(
          value,
          style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.bold, color: color),
          textAlign: TextAlign.center,
        ),
        Text(
          title,
          style: GoogleFonts.cairo(fontSize: 10, color: AppColors.textSecondary),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildDetailRow({
    required String label,
    required String value,
    required IconData icon,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Text(label, style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
          const Spacer(),
          Text(value, style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
        ],
      ),
    );
  }
}
