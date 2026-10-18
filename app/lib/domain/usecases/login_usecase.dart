import '../repositories/auth_repository.dart';

/// Use case: Login
/// Follows the Single Responsibility Principle — only orchestrates the login call.
class LoginUseCase {
  final AuthRepository _repository;

  const LoginUseCase(this._repository);

  Future<AuthResult> call({
    required String email,
    required String password,
  }) {
    return _repository.login(email: email, password: password);
  }
}
