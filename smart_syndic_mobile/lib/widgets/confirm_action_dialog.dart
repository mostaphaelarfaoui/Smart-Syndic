import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';

enum ConfirmDialogType { danger, warning, info, success }

class ConfirmActionDialog extends StatefulWidget {
  final String title;
  final String message;
  final String confirmText;
  final String cancelText;
  final ConfirmDialogType type;
  final bool requiresPassword;
  final String? passwordHint;

  const ConfirmActionDialog({
    super.key,
    required this.title,
    required this.message,
    this.confirmText = 'تأكيد',
    this.cancelText = 'إلغاء',
    this.type = ConfirmDialogType.danger,
    this.requiresPassword = false,
    this.passwordHint,
  });

  static Future<bool?> show(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'تأكيد',
    String cancelText = 'إلغاء',
    ConfirmDialogType type = ConfirmDialogType.danger,
    bool requiresPassword = false,
    String? passwordHint,
  }) {
    return showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => ConfirmActionDialog(
        title: title,
        message: message,
        confirmText: confirmText,
        cancelText: cancelText,
        type: type,
        requiresPassword: requiresPassword,
        passwordHint: passwordHint,
      ),
    );
  }

  @override
  State<ConfirmActionDialog> createState() => _ConfirmActionDialogState();
}

class _ConfirmActionDialogState extends State<ConfirmActionDialog> {
  final TextEditingController _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isVerifying = false;
  String? _passwordError;

  @override
  void dispose() {
    _passwordController.dispose();
    super.dispose();
  }

  Color get _themeColor {
    switch (widget.type) {
      case ConfirmDialogType.danger:
        return AppColors.danger;
      case ConfirmDialogType.warning:
        return AppColors.warning;
      case ConfirmDialogType.success:
        return AppColors.success;
      case ConfirmDialogType.info:
        return AppColors.primary;
    }
  }

  IconData get _iconData {
    switch (widget.type) {
      case ConfirmDialogType.danger:
        return Icons.warning_amber_rounded;
      case ConfirmDialogType.warning:
        return Icons.error_outline_rounded;
      case ConfirmDialogType.success:
        return Icons.check_circle_outline_rounded;
      case ConfirmDialogType.info:
        return Icons.info_outline_rounded;
    }
  }

  Future<void> _handleConfirm() async {
    if (!widget.requiresPassword) {
      Navigator.of(context).pop(true);
      return;
    }

    final password = _passwordController.text.trim();
    if (password.isEmpty) {
      setState(() {
        _passwordError = 'يرجى إدخال كلمة المرور للمتابعة';
      });
      return;
    }

    setState(() {
      _isVerifying = true;
      _passwordError = null;
    });

    try {
      final response = await ApiService().post(ApiConstants.verifyPassword, {
        'password': password,
      });

      if (mounted) {
        if (response is Map && response['valid'] == true) {
          Navigator.of(context).pop(true);
        } else {
          setState(() {
            _passwordError = 'كلمة المرور غير صحيحة. يرجى التأكد وإعادة المحاولة.';
            _isVerifying = false;
          });
        }
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          _passwordError = e.message.isNotEmpty
              ? e.message
              : 'كلمة المرور غير صحيحة. يرجى التأكد وإعادة المحاولة.';
          _isVerifying = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _passwordError = 'تعذر التحقق من كلمة المرور. يرجى المحاولة ثانية.';
          _isVerifying = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: Theme.of(context).cardColor,
      elevation: 10,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 420),
        padding: const EdgeInsets.all(22),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Icon Header
            Container(
              width: 58,
              height: 58,
              decoration: BoxDecoration(
                color: _themeColor.withAlpha(25),
                shape: BoxShape.circle,
                border: Border.all(color: _themeColor.withAlpha(50), width: 1.5),
              ),
              child: Icon(_iconData, color: _themeColor, size: 30),
            ),
            const SizedBox(height: 16),

            // Title
            Text(
              widget.title,
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontSize: 17,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),

            // Message
            Text(
              widget.message,
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontSize: 13,
                color: AppColors.textSecondary,
                height: 1.5,
              ),
            ),

            // Sensitive Password Section
            if (widget.requiresPassword) ...[
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.danger.withAlpha(15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.danger.withAlpha(35)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.lock_outline, size: 16, color: AppColors.danger),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            widget.passwordHint ?? 'تأكيد الهوية: أدخل كلمة مرور حسابك للمتابعة',
                            style: GoogleFonts.cairo(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: AppColors.danger,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      autofocus: true,
                      enabled: !_isVerifying,
                      textDirection: TextDirection.ltr,
                      decoration: InputDecoration(
                        hintText: 'كلمة المرور الحالية للحساب',
                        hintStyle: GoogleFonts.cairo(fontSize: 12, color: Colors.grey),
                        hintTextDirection: TextDirection.rtl,
                        filled: true,
                        fillColor: Theme.of(context).scaffoldBackgroundColor,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Colors.grey.withAlpha(40)),
                        ),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                            size: 18,
                            color: Colors.grey,
                          ),
                          onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                        ),
                      ),
                    ),

                    // Inline Error Message (Keeps modal open!)
                    if (_passwordError != null) ...[
                      const SizedBox(height: 8),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.error_outline, size: 14, color: AppColors.danger),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              _passwordError!,
                              style: GoogleFonts.cairo(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppColors.danger,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],

            const SizedBox(height: 22),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: TextButton(
                    onPressed: _isVerifying ? null : () => Navigator.of(context).pop(false),
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: Text(
                      widget.cancelText,
                      style: GoogleFonts.cairo(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton(
                    onPressed: _isVerifying ? null : _handleConfirm,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _themeColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      elevation: 0,
                    ),
                    child: _isVerifying
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            widget.confirmText,
                            style: GoogleFonts.cairo(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
