import 'package:dio/dio.dart';
import '../../core/constants/app_constants.dart';
import '../../core/errors/exceptions.dart';
import '../models/user_model.dart';

class LoginResponseData {
  final String token;
  final UserModel user;

  const LoginResponseData({required this.token, required this.user});
}

/// Remote data source — responsible ONLY for API communication.
abstract class AuthRemoteDataSource {
  Future<LoginResponseData> login({
    required String email,
    required String password,
  });
}

class AuthRemoteDataSourceImpl implements AuthRemoteDataSource {
  final Dio _dio;

  AuthRemoteDataSourceImpl(this._dio);

  @override
  Future<LoginResponseData> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/auth/login',
        data: {'email': email, 'password': password},
        options: Options(
          headers: {'Content-Type': 'application/json'},
          validateStatus: (status) => status != null && status < 500,
        ),
      );

      if (response.statusCode == 200) {
        final data = response.data as Map<String, dynamic>;
        final token = data['token'] as String;
        final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
        return LoginResponseData(token: token, user: user);
      } else if (response.statusCode == 401 || response.statusCode == 422) {
        final data = response.data as Map<String, dynamic>?;
        final message =
            data?['message'] as String? ?? 'Thông tin đăng nhập không hợp lệ.';
        throw AuthException(message: message, statusCode: response.statusCode);
      } else {
        throw ServerException(
          message: 'Lỗi máy chủ (${response.statusCode}). Vui lòng thử lại.',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError) {
        throw const NetworkException(
          message: 'Không thể kết nối đến máy chủ. Kiểm tra kết nối mạng.',
        );
      }
      throw AppException(message: e.message ?? 'Đã xảy ra lỗi không xác định.');
    }
  }
}
