import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';

class GenerateReportDialog extends StatefulWidget {
  const GenerateReportDialog({super.key});

  @override
  State<GenerateReportDialog> createState() => _GenerateReportDialogState();
}

class _GenerateReportDialogState extends State<GenerateReportDialog> {
  DateTime _fromDate = DateTime(DateTime.now().year, 1, 1);
  DateTime _toDate = DateTime.now();
  String _selectedScope = 'both'; // 'both', 'revenues', 'expenses'
  bool _isGenerating = false;
  Map<String, dynamic>? _generatedResult;
  String? _errorMessage;

  void _setPeriod(String type) {
    final now = DateTime.now();
    setState(() {
      _generatedResult = null;
      _errorMessage = null;
      if (type == 'year') {
        _fromDate = DateTime(now.year, 1, 1);
        _toDate = DateTime(now.year, 12, 31);
      } else if (type == '6months') {
        _fromDate = DateTime(now.year, now.month - 6, now.day);
        _toDate = now;
      } else if (type == 'month') {
        _fromDate = DateTime(now.year, now.month, 1);
        _toDate = now;
      }
    });
  }

  Future<void> _selectDate(BuildContext context, bool isFrom) async {
    final initialDate = isFrom ? _fromDate : _toDate;
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(primary: AppColors.primary),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _generatedResult = null;
        _errorMessage = null;
        if (isFrom) {
          _fromDate = picked;
          if (_toDate.isBefore(_fromDate)) {
            _toDate = _fromDate;
          }
        } else {
          _toDate = picked;
        }
      });
    }
  }

  Future<void> _generate() async {
    setState(() {
      _isGenerating = true;
      _errorMessage = null;
      _generatedResult = null;
    });

    final syndic = Provider.of<SyndicProvider>(context, listen: false);
    final fromStr = DateFormat('yyyy-MM-dd').format(_fromDate);
    final toStr = DateFormat('yyyy-MM-dd').format(_toDate);

    final result = await syndic.generateFinancialReport(
      from: fromStr,
      to: toStr,
      scope: _selectedScope,
      format: 'pdf',
    );

    if (mounted) {
      setState(() {
        _isGenerating = false;
        if (result != null) {
          _generatedResult = result;
        } else {
          _errorMessage = syndic.errorMessage ?? 'فشل توليد التقرير المالي';
        }
      });
    }
  }

  Future<void> _openReport(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح رابط التقرير')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('yyyy-MM-dd');

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withAlpha(25),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.picture_as_pdf, color: AppColors.primary, size: 24),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'التقرير المالي للجمع العام',
                            style: GoogleFonts.cairo(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          Text(
                            'طبقاً للقانون 18-00 للملكية المشتركة',
                            style: GoogleFonts.cairo(
                              fontSize: 11,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: AppColors.textSecondary),
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                  ),
                ],
              ),
              const Divider(height: 24),

              // Scope selector (Both / Revenues / Expenses)
              Text(
                'نوع محتوى التقرير المالي:',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: ChoiceChip(
                      label: Text('⚖️ تقرير شامل (مداخيل ومصاريف)', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                      selected: _selectedScope == 'both',
                      selectedColor: AppColors.primary.withAlpha(40),
                      onSelected: (val) => setState(() => _selectedScope = 'both'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Expanded(
                    child: ChoiceChip(
                      label: Text('📈 المداخيل فقط', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                      selected: _selectedScope == 'revenues',
                      selectedColor: Colors.green.withAlpha(40),
                      onSelected: (val) => setState(() => _selectedScope = 'revenues'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: ChoiceChip(
                      label: Text('📉 المصاريف فقط', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.bold)),
                      selected: _selectedScope == 'expenses',
                      selectedColor: Colors.red.withAlpha(40),
                      onSelected: (val) => setState(() => _selectedScope = 'expenses'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Quick period buttons
              Text(
                'اختر الفترة الزمنية:',
                style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        side: const BorderSide(color: AppColors.border),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () => _setPeriod('year'),
                      child: Text('السنة الحالية', style: GoogleFonts.cairo(fontSize: 12)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        side: const BorderSide(color: AppColors.border),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () => _setPeriod('6months'),
                      child: Text('آخر 6 أشهر', style: GoogleFonts.cairo(fontSize: 12)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        side: const BorderSide(color: AppColors.border),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () => _setPeriod('month'),
                      child: Text('هذا الشهر', style: GoogleFonts.cairo(fontSize: 12)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Date pickers row
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () => _selectDate(context, true),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        decoration: BoxDecoration(
                          border: Border.all(color: AppColors.border),
                          borderRadius: BorderRadius.circular(10),
                          color: AppColors.background,
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('من تاريخ:', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight)),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Icon(Icons.calendar_today, size: 14, color: AppColors.primary),
                                const SizedBox(width: 6),
                                Text(
                                  dateFormat.format(_fromDate),
                                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: InkWell(
                      onTap: () => _selectDate(context, false),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        decoration: BoxDecoration(
                          border: Border.all(color: AppColors.border),
                          borderRadius: BorderRadius.circular(10),
                          color: AppColors.background,
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('إلى تاريخ:', style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textLight)),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Icon(Icons.event, size: 14, color: AppColors.primary),
                                const SizedBox(width: 6),
                                Text(
                                  dateFormat.format(_toDate),
                                  style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Description notes
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primary.withAlpha(12),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.primary.withAlpha(40)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.info_outline, size: 18, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'يتضمن التقرير: جدول استخلاص الواجبات لكل شقة، بيان المصاريف المفصلة حسب الصنف، ورصيد الخزينة النهائي لتوزيعه على السكان.',
                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textPrimary, height: 1.4),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Error display
              if (_errorMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.danger.withAlpha(20),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    _errorMessage!,
                    style: GoogleFonts.cairo(color: AppColors.danger, fontSize: 12),
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(height: 14),
              ],

              // Success Generated Card
              if (_generatedResult != null && _generatedResult!['url'] != null) ...[
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.success.withAlpha(20),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.success),
                  ),
                  child: Column(
                    children: [
                      const Icon(Icons.check_circle_outline, color: AppColors.success, size: 36),
                      const SizedBox(height: 8),
                      Text(
                        'تم توليد التقرير المالي بنجاح! ✓',
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.success),
                      ),
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.success,
                          foregroundColor: Colors.white,
                          minimumSize: const Size(double.infinity, 42),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () => _openReport(_generatedResult!['url']),
                        icon: const Icon(Icons.open_in_new, size: 18),
                        label: Text(
                          'معاينة وطباعة التقرير (PDF)',
                          style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
              ],

              // Generate Action Button
              if (_generatedResult == null)
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _isGenerating ? null : _generate,
                  child: _isGenerating
                      ? Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            ),
                            const SizedBox(width: 12),
                            Text('جارِ تجهيز التقرير والحسابات...', style: GoogleFonts.cairo(fontSize: 13)),
                          ],
                        )
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.download, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              'توليد التقرير المالي الرسمي',
                              style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                          ],
                        ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
