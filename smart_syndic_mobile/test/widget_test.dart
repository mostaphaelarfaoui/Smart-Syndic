import 'package:flutter_test/flutter_test.dart';
import 'package:smart_syndic_mobile/main.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const SmartSyndicApp());
    expect(find.byType(SmartSyndicApp), findsOneWidget);
  });
}
