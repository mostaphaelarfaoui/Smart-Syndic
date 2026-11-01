/// Utility function to safely parse numeric values from API responses
/// whether they arrive as num, double, int, or String representation.
double parseDouble(dynamic val, [double defaultValue = 0.0]) {
  if (val == null) return defaultValue;
  if (val is num) return val.toDouble();
  return double.tryParse(val.toString()) ?? defaultValue;
}

/// Strips any redundant "شقة", "شقه", "الشقة", "Appartement" prefixes.
String cleanUnitNumber(String? unit) {
  if (unit == null) return '';
  var trimmed = unit.trim();
  final prefixRegex = RegExp(r'^(?:ال)?(?:شقة|شقه|Appartement|Apt\.?)\s*', caseSensitive: false);
  while (prefixRegex.hasMatch(trimmed)) {
    trimmed = trimmed.replaceFirst(prefixRegex, '').trim();
  }
  return trimmed;
}

/// Cleanly format apartment unit number with a single "شقة " prefix.
String formatUnitLabel(String? unit) {
  final clean = cleanUnitNumber(unit);
  if (clean.isEmpty) return 'شقة';
  return 'شقة $clean';
}

/// Cleanly format apartment unit number with "الشقة " prefix (e.g. "الشقة 1").
String formatDefiniteUnit(String? unit) {
  final clean = cleanUnitNumber(unit);
  if (clean.isEmpty) return 'الشقة';
  return 'الشقة $clean';
}

