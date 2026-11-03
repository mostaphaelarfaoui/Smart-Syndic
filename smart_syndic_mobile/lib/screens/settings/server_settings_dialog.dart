import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';
import '../../core/services/server_discovery_service.dart';
import '../../widgets/app_alert.dart';

class ServerSettingsDialog extends StatefulWidget {
  const ServerSettingsDialog({super.key});

  @override
  State<ServerSettingsDialog> createState() => _ServerSettingsDialogState();
}

class _ServerSettingsDialogState extends State<ServerSettingsDialog> {
  late TextEditingController _controller;
  final ApiService _api = ApiService();
  bool _isSearching = false;
  String? _searchStatus;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: _api.baseUrl);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _startAutoDiscovery() async {
    setState(() {
      _isSearching = true;
      _searchStatus = 'بدء البحث في الشبكة...';
    });

    final discovered = await ServerDiscoveryService().discoverServer(
      onProgress: (status) {
        if (mounted) setState(() => _searchStatus = status);
      },
    );

    if (!mounted) return;

    setState(() {
      _isSearching = false;
      _searchStatus = null;
    });

    if (discovered != null) {
      setState(() => _controller.text = discovered);
      AppAlert.success(context, 'تم العثور على السيرفر: $discovered');
    } else {
      AppAlert.error(
        context,
        'لم يتم العثور على سيرفر نشط في الشبكة.',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Row(
        children: [
          const Icon(Icons.settings_ethernet, color: AppColors.primary),
          const SizedBox(width: 8),
          Text(
            'عنوان الخادم (Server IP)',
            style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
          ),
        ],
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'أدخل رابط الـ API للاتصال بالسيرفر:',
              style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _controller,
              textDirection: TextDirection.ltr,
              decoration: const InputDecoration(
                hintText: 'http://sosta-04.local:8000/api/v1',
                prefixIcon: Icon(Icons.link),
              ),
            ),
            const SizedBox(height: 14),

            // Auto-Discovery Button
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _isSearching ? null : _startAutoDiscovery,
                icon: _isSearching
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.radar, color: AppColors.primary),
                label: Text(
                  _isSearching ? (_searchStatus ?? 'جارٍ البحث...') : 'كشف السيرفر تلقائياً في الشبكة',
                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                ),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppColors.primary),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                ),
              ),
            ),

            const SizedBox(height: 16),
            Text(
              'خيارات سريعة:',
              style: GoogleFonts.cairo(fontWeight: FontWeight.w600, fontSize: 12),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _buildQuickChip('اسم الجهاز (sosta-04.local)', 'http://sosta-04.local:8000/api/v1'),
                _buildQuickChip('محاكي أندرويد (10.0.2.2)', 'http://10.0.2.2:8000/api/v1'),
                _buildQuickChip('حاسوب محلي (127.0.0.1)', 'http://127.0.0.1:8000/api/v1'),
              ],
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('إلغاء', style: GoogleFonts.cairo(color: AppColors.textSecondary)),
        ),
        ElevatedButton(
          onPressed: () async {
            var url = _controller.text.trim();
            if (url.isNotEmpty) {
              if (!url.startsWith('http://') && !url.startsWith('https://')) {
                url = 'http://$url';
              }
              if (!url.contains(':') || (url.startsWith('http://') && url.indexOf(':', 7) == -1)) {
                if (!url.contains('/')) {
                  url = '$url:8000';
                }
              }
              if (!url.endsWith('/api/v1')) {
                url = url.endsWith('/') ? '${url}api/v1' : '$url/api/v1';
              }
              await _api.setBaseUrl(url);
              if (!context.mounted) return;
              await AppAlert.success(context, 'تم حفظ عنوان السيرفر ($url)');
              if (context.mounted) Navigator.pop(context);
            }
          },
          child: Text('حفظ', style: GoogleFonts.cairo()),
        ),
      ],
    );
  }

  Widget _buildQuickChip(String label, String url) {
    return ActionChip(
      label: Text(label, style: GoogleFonts.cairo(fontSize: 11)),
      onPressed: () => setState(() => _controller.text = url),
    );
  }
}
