import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/add_apartment_dialog.dart';

class SyndicApartmentsTab extends StatelessWidget {
  const SyndicApartmentsTab({super.key});

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final apartments = syndic.apartments;

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const AddApartmentDialog(),
          );
        },
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add_home_work_outlined),
        label: Text('إضافة شقة', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: RefreshIndicator(
        onRefresh: () => syndic.fetchApartments(),
        child: apartments.isEmpty
            ? EmptyStateWidget(
                icon: Icons.apartment,
                title: 'لا توجد شقق مسجلة',
                description: 'قم بإضافة الشقق الخاصة بإقامتك للبدء في تتبع الدفعات وتسكين السكان.',
                buttonText: 'إضافة شقة الآن',
                onButtonPressed: () {
                  showDialog(
                    context: context,
                    builder: (_) => const AddApartmentDialog(),
                  );
                },
              )
            : ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
                itemCount: apartments.length,
                itemBuilder: (context, index) {
                  final apt = apartments[index];
                  
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      leading: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withAlpha(20),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.meeting_room_outlined, color: AppColors.primary),
                      ),
                      title: Text(
                        apt.unitNumber,
                        style: GoogleFonts.cairo(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 4),
                          Text(
                            'الطابق: ${apt.floor} | الواجب الشهري: ${apt.monthlyFee} درهم',
                            style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary),
                          ),
                          if (apt.resident != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.person, size: 14, color: AppColors.success),
                                const SizedBox(width: 4),
                                Text(
                                  apt.resident!['name'],
                                  style: GoogleFonts.cairo(fontSize: 13, color: AppColors.success, fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ] else ...[
                            const SizedBox(height: 4),
                            Text(
                              'شقة فارغة (لا يوجد ساكن)',
                              style: GoogleFonts.cairo(fontSize: 12, color: AppColors.danger),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
