import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/theme/app_theme.dart';
import 'core/constants/app_colors.dart';
import 'providers/auth_provider.dart';
import 'providers/syndic_provider.dart';
import 'providers/resident_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/syndic/syndic_main_screen.dart';
import 'screens/resident/resident_main_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const SmartSyndicApp());
}

class SmartSyndicApp extends StatelessWidget {
  const SmartSyndicApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()..checkAuth()),
        ChangeNotifierProvider(create: (_) => SyndicProvider()),
        ChangeNotifierProvider(create: (_) => ResidentProvider()),
      ],
      child: MaterialApp(
        title: 'Smart Syndic - سمارت سانديك',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        locale: const Locale('ar'),
        supportedLocales: const [
          Locale('ar'),
          Locale('fr'),
        ],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        home: const AuthGate(),
      ),
    );
  }
}

class AuthGate extends StatelessWidget {
  const AuthGate({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    if (auth.isLoading && auth.user == null) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: AppColors.primary),
              SizedBox(height: 16),
              Text(
                'جاري التحقق من الحساب...',
                style: TextStyle(color: AppColors.textSecondary, fontFamily: 'Cairo'),
              ),
            ],
          ),
        ),
      );
    }

    if (!auth.isAuthenticated || auth.user == null) {
      return const LoginScreen();
    }

    if (auth.user!.isSyndic) {
      return const SyndicMainScreen();
    } else {
      return const ResidentMainScreen();
    }
  }
}
