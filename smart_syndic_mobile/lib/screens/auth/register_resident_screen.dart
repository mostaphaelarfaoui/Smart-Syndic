import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';

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
  List<dynamic> _availableApartments = [];
  String? _selectedApartmentId;
  String _occupancyType = 'OWNER';

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
        _tenantName = data['tenant_name'] ?? data['tenant']?['name'] ?? 'الإقامة';
        _availableApartments = data['apartments'] ?? [];
        if (_availableApartments.isNotEmpty) {
          _selectedApartmentId = _availableApartments[0]['id'];
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

  void _submitRegistration() async {
    if (_selectedApartmentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('يرجى اختيار الشقة', style: GoogleFonts.cairo())),
      );
      return;
    }

    final auth = context.read<AuthProvider>();
    final success = await auth.registerResident(
      inviteCode: _inviteCodeController.text.trim(),
      apartmentId: _selectedApartmentId!,
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      phone: _phoneController.text.trim(),
      password: _passwordController.text,
      occupancyType: _occupancyType,
    );

    if (success && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          title: Text('تم إرسال طلبك بنجاح', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          content: Text(
            'تم تسجيل طلبك لدى إدارة الإقامة، وسيقوم السانديك بتفعيل حسابك قريباً.',
            style: GoogleFonts.cairo(),
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context);
              },
              child: Text('العودة لتسجيل الدخول', style: GoogleFonts.cairo()),
            ),
          ],
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(auth.errorMessage ?? 'فشل التسجيل', style: GoogleFonts.cairo()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text('تسجيل ساكن جديد', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!_isVerified) ...[
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      const Icon(Icons.vpn_key_outlined, size: 48, color: AppColors.primary),
                      const SizedBox(height: 12),
                      Text(
                        'أدخل رمز دعوة الإقامة',
                        style: GoogleFonts.cairo(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        'يتم تزويدك بهذا الرمز من طرف سانديك الإقامة للانضمام إلى العمارة.',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: _inviteCodeController,
                        textDirection: TextDirection.ltr,
                        textAlign: TextAlign.center,
                        style: const TextStyle(letterSpacing: 4, fontWeight: FontWeight.bold, fontSize: 18),
                        decoration: const InputDecoration(
                          hintText: 'ABCD-1234',
                        ),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: auth.isLoading ? null : _verifyCode,
                        child: auth.isLoading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Text('تحقق من الرمز', style: GoogleFonts.cairo()),
                      ),
                    ],
                  ),
                ),
              ),
            ] else ...[
              // Residence Verified Banner
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.success),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle, color: AppColors.success),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'الإقامة: $_tenantName',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // Apartment Selection
              Text('اختر الشقة:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              DropdownButtonFormField<String>(
                value: _selectedApartmentId,
                items: _availableApartments.map<DropdownMenuItem<String>>((apt) {
                  return DropdownMenuItem<String>(
                    value: apt['id'],
                    child: Text(
                      '${apt['building_name'] ?? ''} - ${apt['unit_number']} (طابق ${apt['floor']})',
                      style: GoogleFonts.cairo(fontSize: 13),
                    ),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedApartmentId = val),
              ),
              const SizedBox(height: 14),

              // Occupancy Type (Owner / Tenant)
              Text('الصفة:', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              SegmentedButton<String>(
                segments: [
                  ButtonSegment(value: 'OWNER', label: Text('مالك (Propriétaire)', style: GoogleFonts.cairo(fontSize: 12))),
                  ButtonSegment(value: 'TENANT', label: Text('مكتري (Locataire)', style: GoogleFonts.cairo(fontSize: 12))),
                ],
                selected: {_occupancyType},
                onSelectionChanged: (set) => setState(() => _occupancyType = set.first),
              ),
              const SizedBox(height: 14),

              // Personal info
              TextField(
                controller: _firstNameController,
                decoration: const InputDecoration(labelText: 'الاسم الشخصي', prefixIcon: Icon(Icons.person_outline)),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _lastNameController,
                decoration: const InputDecoration(labelText: 'الاسم العائلي', prefixIcon: Icon(Icons.person_outline)),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(labelText: 'رقم الهاتف', prefixIcon: Icon(Icons.phone_outlined)),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(labelText: 'البريد الإلكتروني', prefixIcon: Icon(Icons.email_outlined)),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _passwordController,
                obscureText: true,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(labelText: 'كلمة المرور', prefixIcon: Icon(Icons.lock_outline)),
              ),
              const SizedBox(height: 24),

              ElevatedButton(
                onPressed: auth.isLoading ? null : _submitRegistration,
                child: auth.isLoading
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : Text('إرسال طلب الانضمام', style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16)),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
