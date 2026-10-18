class AppConstants {
  AppConstants._();

  // 1. Cập nhật baseUrl thành domain của Render (Sử dụng https)
  static const String baseUrl = 'https://kiottaybackend.onrender.com/api';

  static const String tokenKey = 'auth_token';
  static const String userKey = 'auth_user';

  // Reverb WebSocket config
  // Hàm get này sẽ tự động trích xuất 'kiottaybackend.onrender.com' từ baseUrl
  static String get reverbHost => Uri.parse(baseUrl).host;

  // 2. Chuyển Port sang 443 (Port public của HTTPS/WSS trên Render)
  static const int reverbPort = 443;

  // 3. Sửa lại App Key cho khớp chính xác với biến REVERB_APP_KEY trên Render
  static const String reverbAppKey = 'yjlxiewbcpdasqsdjriq';

  static const String reverbCluster = 'mt1';

  // 4. (Tuỳ chọn thêm) Khai báo scheme để cấu hình Echo sử dụng WSS thay vì WS
  static const String reverbScheme = 'https';
}

class AppRoutes {
  AppRoutes._();

  static const String login = '/login';
  static const String ordering = '/ordering';
  static const String kitchen = '/kitchen';
}
