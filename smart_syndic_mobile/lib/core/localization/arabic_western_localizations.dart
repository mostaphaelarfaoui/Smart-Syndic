import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;
import 'package:intl/date_symbol_data_local.dart';

/// Helper function to convert Eastern Arabic numerals (٠-٩) to Western/Latin numerals (0-9).
String replaceEasternArabicDigits(String input) {
  const eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  const western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  var result = input;
  for (var i = 0; i < 10; i++) {
    result = result.replaceAll(eastern[i], western[i]);
  }
  return result;
}

/// Helper function to convert Western/Latin numerals (0-9) to Eastern Arabic numerals (٠-٩).
String replaceWesternDigitsWithEastern(String input) {
  const western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  const eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  var result = input;
  for (var i = 0; i < 10; i++) {
    result = result.replaceAll(western[i], eastern[i]);
  }
  return result;
}

/// Custom [MaterialLocalizationAr] that formats all dates and numbers with Latin/Western digits
/// (0, 1, 2, ... 2026) instead of Eastern Arabic numerals (٠, ١, ٢, ... ٢٠٢٦),
/// preserving Arabic month and day names.
class ArabicWesternMaterialLocalizations extends MaterialLocalizationAr {
  ArabicWesternMaterialLocalizations({
    super.localeName = 'ar',
    required super.fullYearFormat,
    required super.compactDateFormat,
    required super.shortDateFormat,
    required super.mediumDateFormat,
    required super.longDateFormat,
    required super.yearMonthFormat,
    required super.shortMonthDayFormat,
    required super.decimalFormat,
    required super.twoDigitZeroPaddedFormat,
  });

  @override
  String formatYear(DateTime date) => replaceEasternArabicDigits(super.formatYear(date));

  @override
  String formatMediumDate(DateTime date) => replaceEasternArabicDigits(super.formatMediumDate(date));

  @override
  String formatMonthYear(DateTime date) => replaceEasternArabicDigits(super.formatMonthYear(date));

  @override
  String formatFullDate(DateTime date) => replaceEasternArabicDigits(super.formatFullDate(date));

  @override
  String formatCompactDate(DateTime date) => replaceEasternArabicDigits(super.formatCompactDate(date));

  @override
  String formatShortDate(DateTime date) => replaceEasternArabicDigits(super.formatShortDate(date));

  @override
  String formatShortMonthDay(DateTime date) => replaceEasternArabicDigits(super.formatShortMonthDay(date));

  @override
  String formatDecimal(int number) => '$number';

  @override
  String formatHour(TimeOfDay timeOfDay, {bool alwaysUse24HourFormat = false}) =>
      replaceEasternArabicDigits(super.formatHour(timeOfDay, alwaysUse24HourFormat: alwaysUse24HourFormat));

  @override
  String formatMinute(TimeOfDay timeOfDay) =>
      replaceEasternArabicDigits(super.formatMinute(timeOfDay));

  @override
  String formatTimeOfDay(TimeOfDay timeOfDay, {bool alwaysUse24HourFormat = false}) =>
      replaceEasternArabicDigits(super.formatTimeOfDay(timeOfDay, alwaysUse24HourFormat: alwaysUse24HourFormat));

  @override
  DateTime? parseCompactDate(String? inputString) {
    if (inputString == null) return null;
    final direct = super.parseCompactDate(inputString);
    if (direct != null) return direct;
    return super.parseCompactDate(replaceWesternDigitsWithEastern(inputString));
  }

  /// Global delegate instance to be registered in MaterialApp.localizationsDelegates.
  static const LocalizationsDelegate<MaterialLocalizations> delegate =
      _ArabicWesternLocalizationsDelegate();
}

class _ArabicWesternLocalizationsDelegate extends LocalizationsDelegate<MaterialLocalizations> {
  const _ArabicWesternLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) => locale.languageCode == 'ar';

  @override
  Future<MaterialLocalizations> load(Locale locale) async {
    await initializeDateFormatting('ar', null);

    return ArabicWesternMaterialLocalizations(
      fullYearFormat: intl.DateFormat('y', 'ar'),
      compactDateFormat: intl.DateFormat('yMd', 'ar'),
      shortDateFormat: intl.DateFormat('yMMMd', 'ar'),
      mediumDateFormat: intl.DateFormat('EEE, MMM d', 'ar'),
      longDateFormat: intl.DateFormat('EEEE, MMMM d, y', 'ar'),
      yearMonthFormat: intl.DateFormat('MMMM y', 'ar'),
      shortMonthDayFormat: intl.DateFormat('MMM d', 'ar'),
      decimalFormat: intl.NumberFormat('#,##0.###', 'ar'),
      twoDigitZeroPaddedFormat: intl.NumberFormat('00', 'ar'),
    );
  }

  @override
  bool shouldReload(covariant LocalizationsDelegate<MaterialLocalizations> old) => false;
}
