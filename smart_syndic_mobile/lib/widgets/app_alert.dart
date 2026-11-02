import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../core/constants/app_colors.dart';

enum AppAlertType { success, error, warning, info }

/// Reusable popup modal alert dialog for consistent, high-visibility user feedback across the app.
class AppAlert {
  static Future<void> show(
    BuildContext context, {
    required String message,
    String? title,
    AppAlertType type = AppAlertType.info,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) async {
    if (!context.mounted) return;
    return showDialog<void>(
      context: context,
      barrierDismissible: barrierDismissible,
      builder: (ctx) => AppAlertDialog(
        message: message,
        title: title,
        type: type,
        buttonText: buttonText,
      ),
    );
  }

  static Future<void> success(
    BuildContext context,
    String message, {
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      show(
        context,
        message: message,
        title: title ?? 'تمت العملية بنجاح',
        type: AppAlertType.success,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> error(
    BuildContext context,
    String message, {
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      show(
        context,
        message: message,
        title: title ?? 'تنبيه خطأ',
        type: AppAlertType.error,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> warning(
    BuildContext context,
    String message, {
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      show(
        context,
        message: message,
        title: title ?? 'تنبيه',
        type: AppAlertType.warning,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> info(
    BuildContext context,
    String message, {
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      show(
        context,
        message: message,
        title: title ?? 'إشعار',
        type: AppAlertType.info,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  // Named parameter aliases for flexibility
  static Future<void> showSuccess(
    BuildContext context, {
    required String message,
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      success(
        context,
        message,
        title: title,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> showError(
    BuildContext context, {
    required String message,
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      error(
        context,
        message,
        title: title,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> showWarning(
    BuildContext context, {
    required String message,
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      warning(
        context,
        message,
        title: title,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );

  static Future<void> showInfo(
    BuildContext context, {
    required String message,
    String? title,
    String buttonText = 'حسناً',
    bool barrierDismissible = true,
  }) =>
      info(
        context,
        message,
        title: title,
        buttonText: buttonText,
        barrierDismissible: barrierDismissible,
      );
}

class AppAlertDialog extends StatelessWidget {
  final String message;
  final String? title;
  final AppAlertType type;
  final String buttonText;

  const AppAlertDialog({
    super.key,
    required this.message,
    this.title,
    this.type = AppAlertType.info,
    this.buttonText = 'حسناً',
  });

  Color get _themeColor {
    switch (type) {
      case AppAlertType.success:
        return AppColors.success;
      case AppAlertType.error:
        return AppColors.danger;
      case AppAlertType.warning:
        return AppColors.warning;
      case AppAlertType.info:
        return AppColors.primary;
    }
  }

  IconData get _iconData {
    switch (type) {
      case AppAlertType.success:
        return Icons.check_circle_rounded;
      case AppAlertType.error:
        return Icons.cancel_rounded;
      case AppAlertType.warning:
        return Icons.warning_amber_rounded;
      case AppAlertType.info:
        return Icons.info_outline_rounded;
    }
  }

  String get _defaultTitle {
    switch (type) {
      case AppAlertType.success:
        return 'تمت العملية بنجاح';
      case AppAlertType.error:
        return 'تنبيه خطأ';
      case AppAlertType.warning:
        return 'تنبيه';
      case AppAlertType.info:
        return 'إشعار';
    }
  }

  @override
  Widget build(BuildContext context) {
    final effectiveTitle = title ?? _defaultTitle;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        backgroundColor: isDark ? AppColors.darkSurface : AppColors.surface,
        elevation: 12,
        insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 380),
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icon Header
                Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(
                    color: _themeColor.withAlpha(24),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: _themeColor.withAlpha(48),
                      width: 1.5,
                    ),
                  ),
                  child: Icon(_iconData, color: _themeColor, size: 30),
                ),
                const SizedBox(height: 16),

                // Title
                Text(
                  effectiveTitle,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.cairo(
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                    color: isDark ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 10),

                // Message
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.cairo(
                    fontSize: 13.5,
                    color: isDark ? AppColors.textLight : AppColors.textSecondary,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 22),

                // Action Button
                SizedBox(
                  width: double.infinity,
                  height: 44,
                  child: ElevatedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _themeColor,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      buttonText,
                      style: GoogleFonts.cairo(
                        fontSize: 14.5,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
