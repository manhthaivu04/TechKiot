import 'package:dio/dio.dart';
import '../../core/constants/app_constants.dart';
import '../../core/errors/exceptions.dart';
import '../models/item_group_model.dart';
import '../models/item_model.dart';
import '../models/restaurant_table_model.dart';
import '../models/table_area_model.dart';

/// Remote data source for menu-related API calls.
abstract class MenuRemoteDataSource {
  /// GET /item-groups
  Future<List<ItemGroupModel>> getItemGroups();

  /// GET /items
  Future<List<ItemModel>> getItems();
}

class MenuRemoteDataSourceImpl implements MenuRemoteDataSource {
  final Dio _dio;

  MenuRemoteDataSourceImpl(this._dio);

  @override
  Future<List<ItemGroupModel>> getItemGroups() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/item-groups',
        options: Options(validateStatus: (s) => s != null && s < 500),
      );
      if (response.statusCode == 200) {
        final data = (response.data['data'] as List<dynamic>);
        return data
            .map((e) => ItemGroupModel.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ServerException(
        message: 'Lỗi tải danh mục món (${response.statusCode}).',
        statusCode: response.statusCode,
      );
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<List<ItemModel>> getItems() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/items',
        queryParameters: {'item_type': 'MENU_ITEM'},
        options: Options(validateStatus: (s) => s != null && s < 500),
      );
      if (response.statusCode == 200) {
        final data = (response.data['data'] as List<dynamic>);
        return data
            .map((e) => ItemModel.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ServerException(
        message: 'Lỗi tải danh sách món (${response.statusCode}).',
        statusCode: response.statusCode,
      );
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  Never _handleDioError(DioException e) {
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

// ─── Table DataSource ──────────────────────────────────────────────────────────

/// Remote data source for table & area API calls.
abstract class TableRemoteDataSource {
  /// GET /table-areas
  Future<List<TableAreaModel>> getTableAreas();

  /// GET /restaurant-tables?area_id=...
  Future<List<RestaurantTableModel>> getRestaurantTables({String? areaId});
}

class TableRemoteDataSourceImpl implements TableRemoteDataSource {
  final Dio _dio;

  TableRemoteDataSourceImpl(this._dio);

  @override
  Future<List<TableAreaModel>> getTableAreas() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/table-areas',
        options: Options(validateStatus: (s) => s != null && s < 500),
      );
      if (response.statusCode == 200) {
        final data = response.data['data'] as List<dynamic>;
        return data
            .map((e) => TableAreaModel.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ServerException(
        message: 'Lỗi tải khu vực bàn (${response.statusCode}).',
        statusCode: response.statusCode,
      );
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<List<RestaurantTableModel>> getRestaurantTables({
    String? areaId,
  }) async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/restaurant-tables',
        queryParameters: {if (areaId != null) 'area_id': areaId},
        options: Options(validateStatus: (s) => s != null && s < 500),
      );
      if (response.statusCode == 200) {
        final data = response.data['data'] as List<dynamic>;
        return data
            .map(
              (e) => RestaurantTableModel.fromJson(e as Map<String, dynamic>),
            )
            .toList();
      }
      throw ServerException(
        message: 'Lỗi tải sơ đồ bàn (${response.statusCode}).',
        statusCode: response.statusCode,
      );
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  Never _handleDioError(DioException e) {
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
