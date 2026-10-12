/// Utility function to safely parse numeric values from API responses
/// whether they arrive as num, double, int, or String representation.
double parseDouble(dynamic val, [double defaultValue = 0.0]) {
  if (val == null) return defaultValue;
  if (val is num) return val.toDouble();
  return double.tryParse(val.toString()) ?? defaultValue;
}
