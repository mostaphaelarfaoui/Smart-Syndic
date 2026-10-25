import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';
import '../../core/utils/number_utils.dart';

class ReceiptViewerScreen extends StatefulWidget {
  final String receiptUrl;
  final String receiptNumber;
  final String? residentName;
  final String? residentPhone;
  final String? unitNumber;
  final double? amountPaid;

  const ReceiptViewerScreen({
    super.key,
    required this.receiptUrl,
    required this.receiptNumber,
    this.residentName,
    this.residentPhone,
    this.unitNumber,
    this.amountPaid,
  });

  static void open(
    BuildContext context, {
    required String receiptUrl,
    required String receiptNumber,
    String? residentName,
    String? residentPhone,
    String? unitNumber,
    double? amountPaid,
  }) {
    if (receiptUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ReceiptViewerScreen(
          receiptUrl: receiptUrl,
          receiptNumber: receiptNumber,
          residentName: residentName,
          residentPhone: residentPhone,
          unitNumber: unitNumber,
          amountPaid: amountPaid,
        ),
      ),
    );
  }

  static Future<void> downloadDirectly(
    BuildContext context, {
    required String receiptUrl,
    required String receiptNumber,
  }) async {
    if (receiptUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رابط الوصل غير متوفر حالياً')),
      );
      return;
    }

    try {
      final resolved = ApiService().resolveMediaUrl(receiptUrl);
      final downloadUrl = resolved.contains('?') ? '$resolved&download=1' : '$resolved?download=1';
      final uri = Uri.parse(downloadUrl);

      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) {
        await launchUrl(uri, mode: LaunchMode.platformDefault);
      }

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.download_done_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'جاري تنزيل الوصل ($receiptNumber.pdf)... تفقد قائمة التنزيلات',
                    style: GoogleFonts.cairo(fontSize: 12),
                  ),
                ),
              ],
            ),
            backgroundColor: AppColors.success,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تعذر تحميل الوصل: $e', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  @override
  State<ReceiptViewerScreen> createState() => _ReceiptViewerScreenState();
}

class _ReceiptViewerScreenState extends State<ReceiptViewerScreen> {
  late PdfViewerController _pdfViewerController;
  bool _isLoading = true;
  String? _errorMessage;
  bool _isDownloading = false;

  @override
  void initState() {
    super.initState();
    _pdfViewerController = PdfViewerController();
  }

  @override
  void dispose() {
    _pdfViewerController.dispose();
    super.dispose();
  }

  Future<void> _downloadDirect(BuildContext context) async {
    if (_isDownloading) return;
    setState(() => _isDownloading = true);

    try {
      final resolved = ApiService().resolveMediaUrl(widget.receiptUrl);
      final downloadUrl = resolved.contains('?') ? '$resolved&download=1' : '$resolved?download=1';
      final uri = Uri.parse(downloadUrl);

      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) {
        await launchUrl(uri, mode: LaunchMode.platformDefault);
      }

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.download_done_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'جاري تنزيل الوصل (${widget.receiptNumber}.pdf)... تفقد قائمة التنزيلات',
                    style: GoogleFonts.cairo(fontSize: 12),
                  ),
                ),
              ],
            ),
            backgroundColor: AppColors.success,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تعذر تحميل الوصل: $e', style: GoogleFonts.cairo()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
  }

  Future<void> _shareWhatsApp(BuildContext context) async {
    final phone = widget.residentPhone;
    final cleanPhone = (phone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    final resolvedUrl = ApiService().resolveMediaUrl(widget.receiptUrl);
    final formattedUnit = formatUnitLabel(widget.unitNumber);
    final amountText = widget.amountPaid != null ? '${widget.amountPaid!.toStringAsFixed(0)} درهم' : '';

    final message = Uri.encodeComponent(
      'السلام عليكم ورحمة الله وبركاته،\n'
      'نرسل لكم وصل أداء واجب السانديك لـ $formattedUnit${amountText.isNotEmpty ? " بمبلغ $amountText" : ""}.\n'
      'رقم الوصل: ${widget.receiptNumber}\n'
      'رابط تحميل الوصل:\n$resolvedUrl\n\n'
      'شكراً لالتزامكم وحسن تعاونكم 🙏\n'
      'إدارة السانديك',
    );

    final uri = cleanPhone.isNotEmpty
        ? Uri.parse('https://wa.me/${cleanPhone.startsWith("0") ? "212${cleanPhone.substring(1)}" : cleanPhone}?text=$message')
        : Uri.parse('https://wa.me/?text=$message');

    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final resolvedUrl = ApiService().resolveMediaUrl(widget.receiptUrl);

    return Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'معاينة الوصل الرسمي',
              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            Text(
              widget.receiptNumber,
              style: GoogleFonts.cairo(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          // Direct Download button in AppBar
          IconButton(
            tooltip: 'تحميل مباشر للوصل',
            onPressed: () => _downloadDirect(context),
            icon: const Icon(Icons.download_rounded),
          ),
          // WhatsApp button
          IconButton(
            tooltip: 'مشاركة عبر واتساب',
            onPressed: () => _shareWhatsApp(context),
            icon: const Icon(Icons.chat),
          ),
          // Zoom In
          IconButton(
            tooltip: 'تكبير',
            onPressed: () => _pdfViewerController.zoomLevel = (_pdfViewerController.zoomLevel + 0.25).clamp(1.0, 3.0),
            icon: const Icon(Icons.zoom_in),
          ),
        ],
      ),
      body: resolvedUrl.isEmpty
          ? Center(
              child: Text(
                'رابط الوصل غير متوفر',
                style: GoogleFonts.cairo(fontSize: 14, color: AppColors.danger),
              ),
            )
          : Stack(
              children: [
                SfPdfViewer.network(
                  resolvedUrl,
                  controller: _pdfViewerController,
                  canShowScrollHead: true,
                  canShowScrollStatus: true,
                  enableDoubleTapZooming: true,
                  onDocumentLoaded: (details) {
                    if (mounted) setState(() => _isLoading = false);
                  },
                  onDocumentLoadFailed: (details) {
                    if (mounted) {
                      setState(() {
                        _isLoading = false;
                        _errorMessage = details.description;
                      });
                    }
                  },
                ),
                if (_isLoading)
                  Container(
                    color: Colors.white70,
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const CircularProgressIndicator(color: AppColors.primary),
                          const SizedBox(height: 16),
                          Text(
                            'جاري تحميل ومعاينة الوصل الرسمي...',
                            style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textPrimary),
                          ),
                        ],
                      ),
                    ),
                  ),
                if (_errorMessage != null)
                  Container(
                    color: Colors.white,
                    padding: const EdgeInsets.all(24),
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 48),
                          const SizedBox(height: 12),
                          Text(
                            'تعذر تحميل المعاينة المباشرة',
                            style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            _errorMessage!,
                            textAlign: TextAlign.center,
                            style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                          ),
                          const SizedBox(height: 20),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            onPressed: () => _downloadDirect(context),
                            icon: const Icon(Icons.download_rounded, size: 18),
                            label: Text('تحميل مباشر للوصل', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(8),
              blurRadius: 8,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              // Direct Download Button
              Expanded(
                flex: 3,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 1,
                  ),
                  onPressed: () => _downloadDirect(context),
                  icon: _isDownloading
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.download_rounded, size: 18),
                  label: Text(
                    'تحميل مباشر (PDF)',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                ),
              ),
              const SizedBox(width: 10),

              // WhatsApp Share Button
              Expanded(
                flex: 2,
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF25D366),
                    side: const BorderSide(color: Color(0xFF25D366), width: 1.2),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => _shareWhatsApp(context),
                  icon: const Icon(Icons.chat, size: 18, color: Color(0xFF25D366)),
                  label: Text(
                    'مشاركة',
                    style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
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
