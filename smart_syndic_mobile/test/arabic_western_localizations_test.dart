import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:smart_syndic_mobile/core/localization/arabic_western_localizations.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

void main() {
  testWidgets('SmartSyndicApp localizations displays Latin numerals in DatePicker with Arabic labels', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: const [
          Locale('ar'),
          Locale('fr'),
        ],
        localizationsDelegates: const [
          ArabicWesternMaterialLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: Scaffold(
          body: Builder(
            builder: (context) => ElevatedButton(
              onPressed: () {
                showDatePicker(
                  context: context,
                  initialDate: DateTime(2026, 10, 25),
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2030),
                );
              },
              child: const Text('Open'),
            ),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();
    await tester.tap(find.text('Open'));
    await tester.pumpAndSettle();

    // Verify Arabic month & Latin year/day in header
    expect(find.text('أكتوبر 2026'), findsOneWidget);
    expect(find.text('الأحد, أكتوبر 25'), findsOneWidget);

    // Switch to Year picker
    await tester.tap(find.text('أكتوبر 2026'));
    await tester.pumpAndSettle();

    // Verify Latin years in year selector
    expect(find.text('2026'), findsWidgets);
    expect(find.text('2025'), findsOneWidget);
    expect(find.text('2024'), findsOneWidget);
  });
}
