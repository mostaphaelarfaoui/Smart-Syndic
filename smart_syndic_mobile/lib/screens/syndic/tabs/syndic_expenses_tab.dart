import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/services/api_service.dart';
import '../../../providers/syndic_provider.dart';
import '../../../widgets/empty_state.dart';
import '../dialogs/record_expense_dialog.dart';

class SyndicExpensesTab extends StatefulWidget {
  const SyndicExpensesTab({super.key});

  @override
  State<SyndicExpensesTab> createState() => _SyndicExpensesTabState();
}

class _SyndicExpensesTabState extends State<SyndicExpensesTab> {
  String _selectedCategoryFilter = 'ALL';

  final List<Map<String, String>> _categories = [
    {'value': 'ALL', 'label': 'الكل'},
    {'value': 'CLEANING', 'label': '🧹 النظافة'},
    {'value': 'ELEVATOR', 'label': '🛗 المصعد'},
    {'value': 'WATER_ELECTRICITY', 'label': '💡 الماء والكهرباء'},
    {'value': 'SECURITY', 'label': '🔒 الحراسة'},
    {'value': 'MAINTENANCE', 'label': '🔧 الصيانة'},
    {'value': 'OTHER', 'label': '📋 أخرى'},
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SyndicProvider>().fetchExpenses();
    });
  }

  @override
  Widget build(BuildContext context) {
    final syndic = context.watch<SyndicProvider>();
    final allExpenses = syndic.expenses;

    final filteredExpenses = allExpenses.where((e) {
      if (_selectedCategoryFilter == 'ALL') return true;
      return e.category == _selectedCategoryFilter;
    }).toList();

    final totalExpense = filteredExpenses.fold<double>(0, (sum, e) => sum + e.amount);

    return Scaffold(
      backgroundColor: AppColors.background,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppColors.danger,
        foregroundColor: Colors.white,
        onPressed: () {
          showDialog(
            context: context,
            builder: (_) => const RecordExpenseDialog(),
          );
        },
        icon: const Icon(Icons.add),
        label: Text('تسجيل مصروف', style: GoogleFonts.cairo(fontWeight: FontWeight.bold)),
      ),
      body: Column(
        children: [
          // Category filter horizontal chips
          SizedBox(
            height: 56,
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              scrollDirection: Axis.horizontal,
              itemCount: _categories.length,
              itemBuilder: (context, index) {
                final cat = _categories[index];
                final isSelected = _selectedCategoryFilter == cat['value'];

                return Padding(
                  padding: const EdgeInsets.only(left: 8),
                  child: FilterChip(
                    selected: isSelected,
                    label: Text(cat['label']!),
                    labelStyle: GoogleFonts.cairo(
                      fontSize: 12,
                      color: isSelected ? Colors.white : AppColors.textPrimary,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                    ),
                    selectedColor: AppColors.primary,
                    checkmarkColor: Colors.white,
                    onSelected: (_) {
                      setState(() => _selectedCategoryFilter = cat['value']!);
                    },
                  ),
                );
              },
            ),
          ),

          // Total expense summary banner
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'عدد الفواتير: ${filteredExpenses.length}',
                  style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                ),
                Text(
                  'إجمالي المصاريف: ${totalExpense.toStringAsFixed(0)} درهم',
                  style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.danger),
                ),
              ],
            ),
          ),

          // Expense list
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                await syndic.fetchExpenses();
              },
              child: filteredExpenses.isEmpty
                  ? LayoutBuilder(
                      builder: (context, constraints) => SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        child: ConstrainedBox(
                          constraints: BoxConstraints(minHeight: constraints.maxHeight),
                          child: EmptyStateWidget(
                            icon: Icons.receipt_long,
                            title: 'لا توجد مصاريف مسجلة في هذا الصنف',
                            description: 'اضغط على زر تسجيل مصروف بالأسفل لتوثيق فاتورة جديدة أو اسحب للأسفل للتحديث',
                            buttonText: 'تسجيل مصروف جديد',
                            onButtonPressed: () {
                              showDialog(
                                context: context,
                                builder: (_) => const RecordExpenseDialog(),
                              );
                            },
                          ),
                        ),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 80),
                      itemCount: filteredExpenses.length,
                      itemBuilder: (context, index) {
                        final expense = filteredExpenses[index];

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
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      expense.title,
                                      style: GoogleFonts.cairo(
                                        fontSize: 15,
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                  Text(
                                    '-${expense.amount.toStringAsFixed(0)} درهم',
                                    style: GoogleFonts.cairo(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),

                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: AppColors.background,
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(color: AppColors.border),
                                    ),
                                    child: Text(
                                      expense.categoryArabic,
                                      style: GoogleFonts.cairo(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  const Icon(Icons.calendar_today, size: 13, color: AppColors.textLight),
                                  const SizedBox(width: 4),
                                  Text(
                                    expense.expenseDate,
                                    style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                  const Spacer(),
                                  if (expense.invoiceProofUrl != null)
                                    IconButton(
                                      icon: const Icon(Icons.receipt, color: AppColors.primary, size: 20),
                                      tooltip: 'معاينة الفاتورة',
                                      onPressed: () async {
                                        final resolved = ApiService().resolveMediaUrl(expense.invoiceProofUrl!);
                                        final uri = Uri.parse(resolved);
                                        if (await canLaunchUrl(uri)) {
                                          await launchUrl(uri, mode: LaunchMode.externalApplication);
                                        }
                                      },
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
