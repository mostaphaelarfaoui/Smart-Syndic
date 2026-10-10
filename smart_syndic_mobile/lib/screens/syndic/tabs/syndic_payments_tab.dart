import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/record_payment_dialog.dart';

class SyndicPaymentsTab extends StatefulWidget {
  const SyndicPaymentsTab({super.key});

  @override
  State<SyndicPaymentsTab> createState() => _SyndicPaymentsTabState();
}

class _SyndicPaymentsTabState extends State<SyndicPaymentsTab> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _shareViaWhatsApp(String? phone, String receiptNumber, double amount, String unit) async {
    final cleanPhone = (phone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    final message = Uri.encodeComponent(
      'السلام عليكم ورحمة الله وبركاته،\n'
      'نؤكد لكم استلام واجب السانديك للشقة $unit بمبلغ ${amount.toStringAsFixed(0)} درهم.\n'
      'رقم الوصل: $receiptNumber\n'
      'شكراً لالتزامكم وحسن تعاونكم 🙏\n'
      'إدارة السانديك',
    );

    Uri uri;
    if (cleanPhone.isNotEmpty) {
      uri = Uri.parse('https://wa.me/$cleanPhone?text=$message');
    } else {
      uri = Uri.parse('https://wa.me/?text=$message');
    }

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر فتح تطبيق واتساب')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final allPayments = syndic.payments;

    final filteredPayments = allPayments.where((p) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      final unitMatch = (p.apartmentUnit ?? '').toLowerCase().contains(q);
      final nameMatch = (p.residentName ?? '').toLowerCase().contains(q);
      final rcptMatch = p.receiptNumber.toLowerCase().contains(q);
      return unitMatch || nameMatch || rcptMatch;
    }).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const RecordPaymentDialog(),
          );
        },
        icon: const Icon(Icons.add),
        label: Text('استخلاص واجب', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Search & Filter Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: (val) => setState(() => _searchQuery = val.trim()),
              decoration: InputDecoration(
                hintText: 'البحث بالشقة، الاسم أو رقم الوصل...',
                prefixIcon: const Icon(Icons.search, color: AppColors.textLight),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _searchQuery = '');
                        },
                      )
                    : null,
                filled: true,
                fillColor: Colors.white,
              ),
            ),
          ),

          // Total count summary
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'عدد الإيصالات: ${filteredPayments.length}',
                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                ),
                Text(
                  'المجموع: ${filteredPayments.fold<double>(0, (sum, p) => sum + p.amountPaid).toStringAsFixed(0)} درهم',
                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                ),
              ],
            ),
          ),

          // Payment List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                await syndic.fetchPayments();
                await syndic.fetchApartments();
              },
              child: filteredPayments.isEmpty
                  ? EmptyStateWidget(
                      icon: Icons.receipt_long_outlined,
                      title: 'لا توجد إيصالات مسجلة حالياً',
                      description: 'اضغط على زر استخلاص واجب بالأسفل لتسجيل دفعة جديدة',
                      buttonText: 'استخلاص واجب جديد',
                      onButtonPressed: () {
                        showDialog(
                          context: context,
                          builder: (_) => const RecordPaymentDialog(),
                        );
                      },
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                      itemCount: filteredPayments.length,
                      itemBuilder: (context, index) {
                        final payment = filteredPayments[index];

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.border),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withAlpha(5),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Top row: Unit & Amount
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: AppColors.primary.withAlpha(20),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          'شقة ${payment.apartmentUnit ?? "-"}',
                                          style: GoogleFonts.cairo(
                                            fontSize: 13,
                                            fontWeight: FontWeight.bold,
                                            color: AppColors.primary,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        payment.residentName ?? 'ساكن الإقامة',
                                        style: GoogleFonts.cairo(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                    ],
                                  ),
                                  Text(
                                    '${payment.amountPaid.toStringAsFixed(0)} درهم',
                                    style: GoogleFonts.cairo(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.success,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),

                              // Details: Receipt number, date, method
                              Row(
                                children: [
                                  const Icon(Icons.tag, size: 14, color: AppColors.textLight),
                                  const SizedBox(width: 4),
                                  Text(
                                    payment.receiptNumber,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                  const SizedBox(width: 16),
                                  const Icon(Icons.calendar_today, size: 14, color: AppColors.textLight),
                                  const SizedBox(width: 4),
                                  Text(
                                    payment.paymentDate,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                  const SizedBox(width: 16),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppColors.background,
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(color: AppColors.border),
                                    ),
                                    child: Text(
                                      payment.paymentMethodArabic,
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ),
                                ],
                              ),

                              // Covered months tags
                              if (payment.coveredMonths.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 4,
                                  children: payment.coveredMonths.map((m) {
                                    return Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryLight.withAlpha(25),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        m,
                                        style: GoogleFonts.cairo(fontSize: 11, color: AppColors.primaryDark),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ],

                              const Divider(height: 20),

                              // Actions row: WhatsApp share & PDF receipt
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  // WhatsApp Button
                                  OutlinedButton.icon(
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: const Color(0xFF25D366),
                                      side: const BorderSide(color: Color(0xFF25D366)),
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                    ),
                                    onPressed: () => _shareViaWhatsApp(
                                      payment.residentPhone,
                                      payment.receiptNumber,
                                      payment.amountPaid,
                                      payment.apartmentUnit ?? '',
                                    ),
                                    icon: const Icon(Icons.chat, size: 16, color: Color(0xFF25D366)),
                                    label: Text(
                                      'إرسال بالواتساب',
                                      style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold),
                                    ),
                                  ),

                                  // Receipt download/view
                                  if (payment.receiptUrl != null)
                                    TextButton.icon(
                                      onPressed: () async {
                                        final uri = Uri.parse(payment.receiptUrl!);
                                        if (await canLaunchUrl(uri)) {
                                          await launchUrl(uri);
                                        }
                                      },
                                      icon: const Icon(Icons.picture_as_pdf, size: 16, color: AppColors.danger),
                                      label: Text(
                                        'تحميل الوصل PDF',
                                        style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textPrimary),
                                      ),
                                    )
                                  else
                                    Text(
                                      'تم التحصيل ✓',
                                      style: GoogleFonts.cairo(fontSize: 12, color: AppColors.success),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
          ),
        ],
      ),
    );
  }
}
