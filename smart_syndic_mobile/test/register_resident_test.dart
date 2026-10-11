import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:smart_syndic_mobile/providers/auth_provider.dart';
import 'package:smart_syndic_mobile/screens/auth/register_resident_screen.dart';

class MockAuthProvider extends ChangeNotifier implements AuthProvider {
  @override
  bool get isLoading => false;
  @override
  String? get errorMessage => null;

  @override
  Future<Map<String, dynamic>?> verifyInviteCode(String code) async {
    return {
      'valid': true,
      'tenant_name': 'اقامة فدوى',
      'is_complex': false,
      'buildings': [
        {
          'id': 'b1',
          'block': null,
          'name': 'اقامة فدوى',
          'total_floors': 1,
          'apartments': [
            {
              'id': 'a1',
              'unit_number': 'شقة 1',
              'floor': 1,
              'monthly_fee': 500,
              'is_occupied': false,
            },
            {
              'id': 'a2',
              'unit_number': 'شقة 2',
              'floor': 1,
              'monthly_fee': 500,
              'is_occupied': true,
            },
          ],
        }
      ],
    };
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  testWidgets('Test RegisterResidentScreen render after verify code', (tester) async {
    final mockAuth = MockAuthProvider();

    await tester.pumpWidget(
      MaterialApp(
        home: ChangeNotifierProvider<AuthProvider>.value(
          value: mockAuth,
          child: const RegisterResidentScreen(),
        ),
      ),
    );

    // Enter code
    await tester.enterText(find.byType(TextField), 'C2G5HDR9');
    await tester.tap(find.text('التحقق من الرمز وعرض الشقق'));
    await tester.pumpAndSettle();

    // Verify whether exception occurred
    expect(tester.takeException(), isNull);
    expect(find.text('العقار: اقامة فدوى'), findsOneWidget);
    expect(find.text('اختر شقتك السكنية:'), findsOneWidget);
    expect(find.text('المعلومات الشخصية:'), findsOneWidget);
  });
}
