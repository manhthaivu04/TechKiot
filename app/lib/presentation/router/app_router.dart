import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_constants.dart';
import '../providers/auth_controller.dart';
import '../providers/auth_state.dart';
import '../screens/kitchen/kitchen_screen.dart';
import '../screens/login/login_screen.dart';
import '../screens/ordering/ordering_screen.dart';
import '../screens/splash/splash_screen.dart';

/// A [NavigatorObserver] bridge that lets go_router react to Riverpod state.
final _rootNavigatorKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  // Create a [ValueNotifier] that go_router's refreshListenable can listen to.
  final authNotifier = _AuthStateNotifier(ref);

  return GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: AppRoutes.login,
    refreshListenable: authNotifier,
    redirect: (context, state) {
      final authState = ref.read(authControllerProvider);
      final location = state.matchedLocation;

      // While initializing, show splash
      if (authState is AuthInitial) {
        return '/splash';
      }

      // If loading, stay on current screen
      if (authState is AuthLoading) {
        return null;
      }

      // If authenticated, redirect away from login/splash
      if (authState is AuthAuthenticated) {
        if (location == AppRoutes.login || location == '/splash') {
          final role = authState.user.role;
          if (role.isKitchenRole) return AppRoutes.kitchen;
          return AppRoutes.ordering;
        }
        return null; // Already on a protected route
      }

      // If unauthenticated or error, redirect to login
      if (authState is AuthUnauthenticated || authState is AuthError) {
        if (location != AppRoutes.login) return AppRoutes.login;
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, __) => const SplashScreen(),
      ),
      GoRoute(
        path: AppRoutes.login,
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: AppRoutes.ordering,
        builder: (_, __) => const OrderingScreen(),
      ),
      GoRoute(
        path: AppRoutes.kitchen,
        builder: (_, __) => const KitchenScreen(),
      ),
    ],
  );
});

/// A simple [ChangeNotifier] that triggers go_router refresh
/// whenever the Riverpod auth state changes.
class _AuthStateNotifier extends ChangeNotifier {
  _AuthStateNotifier(Ref ref) {
    ref.listen<AuthState>(authControllerProvider, (_, __) {
      notifyListeners();
    });
  }
}
