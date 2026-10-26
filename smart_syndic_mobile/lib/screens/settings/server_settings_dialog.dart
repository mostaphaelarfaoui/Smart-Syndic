import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../core/services/api_service.dart';

class ServerSettingsDialog extends StatefulWidget {
  const ServerSettingsDialog({super.key});

  @override
  State<ServerSettingsDialog> createState() => _ServerSettingsDialogState();
}

class _ServerSettingsDialogState extends State<ServerSettingsDialog> {
  late TextEditingController _controller;
  final ApiService _api = ApiService();

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
                hintText: 'http://10.0.2.2:8000/api/v1',
                prefixIcon: Icon(Icons.link),
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
                _buildQuickChip('حاسوب محلي (127.0.0.1)', 'http://127.0.0.1:8000/api/v1'),
                _buildQuickChip('شبكة Wi-Fi الحالية (192.168.11.203)', 'http://192.168.11.203:8000/api/v1'),
                _buildQuickChip('محاكي أندرويد (10.0.2.2)', 'http://10.0.2.2:8000/api/v1'),
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
              // If port is not specified and no path given, add default dev port :8000
              if (!url.contains(':') || (url.startsWith('http://') && url.indexOf(':', 7) == -1)) {
                if (!url.contains('/')) {
                  url = '$url:8000';
                }
              }
              // Ensure path ends with /api/v1
              if (!url.endsWith('/api/v1')) {
                url = url.endsWith('/') ? '${url}api/v1' : '$url/api/v1';
              }
              await _api.setBaseUrl(url);
              if (!context.mounted) return;
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('تم حفظ عنوان السيرفر ($url)', style: GoogleFonts.cairo()),
                  backgroundColor: AppColors.success,
                ),
              );
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
