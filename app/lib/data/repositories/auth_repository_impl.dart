import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../core/constants/app_constants.dart';
import '../../core/errors/exceptions.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_remote_datasource.dart';
import '../models/user_model.dart';

/// Concrete implementation of [AuthRepository].
/// Orchestrates remote API calls + local secure storage.
class AuthRepositoryImpl implements AuthRepository {
  final AuthRemoteDataSource _remoteDataSource;
  final FlutterSecureStorage _secureStorage;

  AuthRepositoryImpl({
    required AuthRemoteDataSource remoteDataSource,
    required FlutterSecureStorage secureStorage,
  })  : _remoteDataSource = remoteDataSource,
        _secureStorage = secureStorage;

  @override
  Future<AuthResult> login({
    required String email,
    required String password,
  }) async {
    // 1. Call remote API (may throw AppException)
    final response = await _remoteDataSource.login(
      email: email,
      password: password,
    );

    // 2. Persist token and user data securely
    await Future.wait([
      _secureStorage.write(key: AppConstants.tokenKey, value: response.token),
      _secureStorage.write(
        key: AppConstants.userKey,
        value: response.user.toJsonString(),
      ),
    ]);

    return AuthResult(token: response.token, user: response.user);
  }

  @override
  Future<void> logout() async {
    await Future.wait([
      _secureStorage.delete(key: AppConstants.tokenKey),
      _secureStorage.delete(key: AppConstants.userKey),
    ]);
  }

  @override
  Future<String?> getToken() async {
    return _secureStorage.read(key: AppConstants.tokenKey);
  }

  @override
  Future<User?> getUser() async {
    final jsonString = await _secureStorage.read(key: AppConstants.userKey);
    if (jsonString == null) return null;
    try {
      return UserModel.fromJsonString(jsonString);
    } catch (_) {
      // Corrupted data — clear storage
      await _secureStorage.deleteAll();
      return null;
    }
  }
}
