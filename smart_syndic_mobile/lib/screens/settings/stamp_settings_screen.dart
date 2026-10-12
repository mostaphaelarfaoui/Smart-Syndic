import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../core/constants/api_constants.dart';

/// Feature 1 — Screen for uploading/managing the syndic building stamp.
/// The stamp image appears on all generated PDF receipts.
class StampSettingsScreen extends StatefulWidget {
  const StampSettingsScreen({super.key});

  @override
  State<StampSettingsScreen> createState() => _StampSettingsScreenState();
}

class _StampSettingsScreenState extends State<StampSettingsScreen> {
  final _api = ApiService();

  bool _isLoading = false;
  bool _hasStamp = false;
  String? _stampUrl;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() => _isLoading = true);
    try {
      final res = await _api.get(ApiConstants.syndicSettings);
      final tenant = res['tenant'] as Map<String, dynamic>?;
      if (mounted) {
        setState(() {
          _hasStamp = tenant?['has_stamp'] == true;
          _stampUrl = tenant?['stamp_url'] as String?;
          if (_stampUrl != null && _stampUrl!.isNotEmpty) {
            _stampUrl = _api.resolveMediaUrl(_stampUrl);
          }
        });
      }
    } catch (e) {
      if (mounted) _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _pickAndUpload() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 800,
      maxHeight: 800,
      imageQuality: 85,
    );
    if (picked == null) return;

    setState(() => _isLoading = true);
    try {
      await _api.postMultipart(
        ApiConstants.syndicStampUpload,
        {},
        fileField: 'stamp',
        filePath: picked.path,
      );
      if (mounted) {
        _showSuccess('تم رفع صورة الطابع بنجاح ✅');
        await _loadSettings();
      }
    } catch (e) {
      if (mounted) _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _deleteStamp() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('حذف الطابع', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        content: Text('هل تريد حذف صورة الطابع؟ سيؤثر ذلك على الوصولات المستقبلية.', style: GoogleFonts.cairo()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء', style: GoogleFonts.cairo()),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.danger),
            child: Text('حذف', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isLoading = true);
    try {
      await _api.delete(ApiConstants.syndicStampUpload);
      if (mounted) {
        _showSuccess('تم حذف الطابع.');
        await _loadSettings();
      }
    } catch (e) {
      if (mounted) _showError(e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showSuccess(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, style: GoogleFonts.cairo()),
      backgroundColor: AppColors.success,
    ));
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, style: GoogleFonts.cairo()),
      backgroundColor: AppColors.danger,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('إعدادات طابع الإقامة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Info card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.primary.withOpacity(0.2)),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline, color: AppColors.primary, size: 22),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'صورة الطابع ستظهر على كل وصولات الأداء المُصدَرة من هذه الإقامة.',
                            style: GoogleFonts.cairo(color: AppColors.primary, fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 28),

                  // Stamp preview
                  Center(
                    child: Container(
                      width: 180,
                      height: 180,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: _hasStamp ? AppColors.success : AppColors.border,
                          width: 2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.07),
                            blurRadius: 12,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: _hasStamp && _stampUrl != null && _stampUrl!.isNotEmpty
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(14),
                              child: Image.network(
                                _stampUrl!,
                                fit: BoxFit.contain,
                                errorBuilder: (context, error, stackTrace) => _noStampPlaceholder(),
                              ),
                            )
                          : _noStampPlaceholder(),
                    ),
                  ),

                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      _hasStamp ? '✅ الطابع مُفعَّل' : 'لا يوجد طابع مُحمَّل بعد',
                      style: GoogleFonts.cairo(
                        color: _hasStamp ? AppColors.success : AppColors.textSecondary,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),

                  const SizedBox(height: 32),

                  // Upload button
                  ElevatedButton.icon(
                    onPressed: _pickAndUpload,
                    icon: const Icon(Icons.upload_rounded),
                    label: Text(
                      _hasStamp ? 'استبدال صورة الطابع' : 'رفع صورة الطابع',
                      style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 15),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Delete button (only if stamp exists)
                  if (_hasStamp)
                    OutlinedButton.icon(
                      onPressed: _deleteStamp,
                      icon: const Icon(Icons.delete_outline),
                      label: Text(
                        'حذف الطابع',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 15),
                      ),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.danger,
                        side: BorderSide(color: AppColors.danger),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),

                  const SizedBox(height: 20),
                  Text(
                    'الصيغ المدعومة: PNG, JPG, JPEG, WEBP — الحجم الأقصى: 2 MB',
                    style: GoogleFonts.cairo(color: AppColors.textSecondary, fontSize: 11),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
    );
  }

  Widget _noStampPlaceholder() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.image_outlined, size: 48, color: AppColors.textSecondary.withOpacity(0.5)),
        const SizedBox(height: 8),
        Text(
          'لا يوجد طابع',
          style: GoogleFonts.cairo(color: AppColors.textSecondary, fontSize: 13),
        ),
      ],
    );
  }
}
