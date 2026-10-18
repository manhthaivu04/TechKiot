import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/errors/exceptions.dart';
import 'auth_state.dart';
import 'dependency_providers.dart';

/// Manages authentication state across the app.
/// Responsible for: session restoration, login, and logout.
class AuthController extends Notifier<AuthState> {
  @override
  AuthState build() {
    // Kick off session restoration as a side-effect
    _restoreSession();
    return const AuthInitial();
  }

  // ─── Session restoration ──────────────────────────────────────────────────

  Future<void> _restoreSession() async {
    final repository = ref.read(authRepositoryProvider);
    try {
      final token = await repository.getToken();
      final user = await repository.getUser();

      if (token != null && user != null) {
        state = AuthAuthenticated(user: user, token: token);
      } else {
        state = const AuthUnauthenticated();
      }
    } catch (_) {
      state = const AuthUnauthenticated();
    }
  }

  // ─── Login ────────────────────────────────────────────────────────────────

  Future<void> login({
    required String email,
    required String password,
  }) async {
    state = const AuthLoading();

    try {
      final useCase = ref.read(loginUseCaseProvider);
      final result = await useCase(email: email, password: password);
      state = AuthAuthenticated(user: result.user, token: result.token);
    } on AuthException catch (e) {
      state = AuthError(e.message);
    } on NetworkException catch (e) {
      state = AuthError(e.message);
    } on AppException catch (e) {
      state = AuthError(e.message);
    } catch (_) {
      state = const AuthError('Đã xảy ra lỗi không mong muốn. Vui lòng thử lại.');
    }
  }

  // ─── Logout ───────────────────────────────────────────────────────────────

  Future<void> logout() async {
    final repository = ref.read(authRepositoryProvider);
    await repository.logout();
    state = const AuthUnauthenticated();
  }
}

/// Global provider for [AuthController]
final authControllerProvider =
    NotifierProvider<AuthController, AuthState>(AuthController.new);
