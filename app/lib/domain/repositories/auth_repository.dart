import '../entities/user.dart';

/// Result of a successful login
class AuthResult {
  final String token;
  final User user;

  const AuthResult({required this.token, required this.user});
}

/// Abstract repository interface (Domain Layer)
/// The data layer must implement this contract.
abstract class AuthRepository {
  /// Attempts login with [email] and [password].
  /// Returns [AuthResult] on success.
  /// Throws [AppException] subclasses on failure.
  Future<AuthResult> login({
    required String email,
    required String password,
  });

  /// Logs out the current user by clearing local storage.
  Future<void> logout();

  /// Retrieves the persisted token, if any.
  Future<String?> getToken();

  /// Retrieves the persisted user, if any.
  Future<User?> getUser();
}
