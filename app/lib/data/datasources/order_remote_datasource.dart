import 'package:dio/dio.dart';
import '../../core/constants/app_constants.dart';
import '../../core/errors/exceptions.dart';
import '../models/order_model.dart';
import '../models/payment_method_setting_model.dart';
import '../models/payment_model.dart';

// ─── Request DTOs ──────────────────────────────────────────────────────────────

class OrderItemRequestDto {
  final String itemId;
  final int quantity;
  final String? note;

  const OrderItemRequestDto({
    required this.itemId,
    required this.quantity,
    this.note,
  });

  Map<String, dynamic> toJson() => {
    'item_id': itemId,
    'quantity': quantity,
    if (note != null) 'note': note,
  };
}

class CreateDineInOrderDto {
  final String tableId;
  final List<OrderItemRequestDto> items;

  const CreateDineInOrderDto({required this.tableId, required this.items});

  Map<String, dynamic> toJson() => {
    'source_channel': 'cashier',
    'service_type': 'dine_in',
    'table_id': tableId,
    'items': items.map((i) => i.toJson()).toList(),
  };
}

class CreateTakeawayOrderDto {
  final String? customerName;
  final String? customerPhone;
  final String? note;
  final List<OrderItemRequestDto> items;

  const CreateTakeawayOrderDto({
    this.customerName,
    this.customerPhone,
    this.note,
    required this.items,
  });

  Map<String, dynamic> toJson() => {
    'source_channel': 'cashier',
    'service_type': 'takeaway',
    if (customerName != null) 'customer_name': customerName,
    if (customerPhone != null) 'customer_phone': customerPhone,
    if (note != null) 'note': note,
    'items': items.map((i) => i.toJson()).toList(),
  };
}

// ─── Abstract + Impl ──────────────────────────────────────────────────────────

/// Remote data source for all Order and Payment API calls.
abstract class OrderRemoteDataSource {
  /// POST /orders (dine_in)
  Future<OrderModel> createDineInOrder(CreateDineInOrderDto dto);

  /// POST /orders (takeaway)
  Future<OrderModel> createTakeawayOrder(CreateTakeawayOrderDto dto);

  /// GET /orders?status=open (paginated)
  Future<List<OrderModel>> getOpenOrders();

  /// POST /orders/{id}/items
  Future<OrderModel> addItemsToOrder({
    required String orderId,
    required List<OrderItemRequestDto> items,
  });

  /// PATCH /orders/{id}/items/{itemId}
  Future<void> updateOrderItem({
    required String orderId,
    required String itemId,
    int? quantity,
    String? note,
  });

  /// DELETE /orders/{id}/items/{itemId}
  Future<void> removeOrderItem({
    required String orderId,
    required String itemId,
  });

  /// GET /payment-method-settings
  Future<List<PaymentMethodSettingModel>> getPaymentMethodSettings();

  /// POST /orders/{id}/payments
  Future<PaymentModel> recordPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? referenceNo,
  });

  /// PATCH /orders/{id}/status  (e.g. status = "paid")
  Future<OrderModel> updateOrderStatus({
    required String orderId,
    required String status,
  });

  /// GET /orders?status=open,cooking  — dành cho bếp (KDS)
  Future<List<OrderModel>> getKitchenOrders();

  /// PATCH /orders/{id}/items/{itemId} with {"status": "cooking"|"ready"|...}
  Future<OrderModel> updateOrderItemStatus({
    required String orderId,
    required String itemId,
    required String status,
  });
}

class OrderRemoteDataSourceImpl implements OrderRemoteDataSource {
  final Dio _dio;

  OrderRemoteDataSourceImpl(this._dio);

  // ─── Helpers ────────────────────────────────────────────────────────────────

  Options get _opts => Options(validateStatus: (s) => s != null && s < 500);

  OrderModel _parseOrder(dynamic responseData) {
    final data = responseData['data'] as Map<String, dynamic>;
    return OrderModel.fromJson(data);
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

  // ─── Order CRUD ─────────────────────────────────────────────────────────────

  @override
  Future<OrderModel> createDineInOrder(CreateDineInOrderDto dto) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/tenant/orders',
        data: dto.toJson(),
        options: _opts,
      );
      if (response.statusCode == 201) return _parseOrder(response.data);
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<OrderModel> createTakeawayOrder(CreateTakeawayOrderDto dto) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/tenant/orders',
        data: dto.toJson(),
        options: _opts,
      );
      if (response.statusCode == 201) return _parseOrder(response.data);
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<List<OrderModel>> getOpenOrders() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/orders',
        queryParameters: {'status': 'open,cooking,served'},
        options: _opts,
      );
      if (response.statusCode == 200) {
        final responseData = response.data['data'];
        List<dynamic> items;
        if (responseData is List) {
          // Direct array: { "data": [ {...}, {...} ] }
          items = responseData;
        } else if (responseData is Map && responseData.containsKey('data')) {
          // Paginated: { "data": { "data": [...], "current_page": 1 } }
          items = responseData['data'] as List<dynamic>;
        } else if (responseData is Map && responseData.containsKey('items')) {
          // Custom: { "data": { "items": [...] } }
          items = responseData['items'] as List<dynamic>;
        } else {
          items = [];
        }
        return items
            .map((e) => OrderModel.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<OrderModel> addItemsToOrder({
    required String orderId,
    required List<OrderItemRequestDto> items,
  }) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/items',
        data: {'items': items.map((i) => i.toJson()).toList()},
        options: _opts,
      );
      if (response.statusCode == 200) return _parseOrder(response.data);
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<void> updateOrderItem({
    required String orderId,
    required String itemId,
    int? quantity,
    String? note,
  }) async {
    try {
      final response = await _dio.patch(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/items/$itemId',
        data: {
          if (quantity != null) 'quantity': quantity,
          if (note != null) 'note': note,
        },
        options: _opts,
      );
      if (response.statusCode != 200) _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<List<OrderModel>> getKitchenOrders() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/orders',
        queryParameters: {'status': 'cooking'},
        options: _opts,
      );
      if (response.statusCode == 200) {
        final responseData = response.data['data'];
        List<dynamic> items;
        if (responseData is List) {
          items = responseData;
        } else if (responseData is Map && responseData.containsKey('data')) {
          items = responseData['data'] as List<dynamic>;
        } else if (responseData is Map && responseData.containsKey('items')) {
          items = responseData['items'] as List<dynamic>;
        } else {
          items = [];
        }
        return items
            .map((e) => OrderModel.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<OrderModel> updateOrderItemStatus({
    required String orderId,
    required String itemId,
    required String status,
  }) async {
    try {
      final response = await _dio.patch(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/items/$itemId',
        data: {'status': status},
        options: _opts,
      );
      if (response.statusCode == 200) return _parseOrder(response.data);
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<void> removeOrderItem({
    required String orderId,
    required String itemId,
  }) async {
    try {
      final response = await _dio.delete(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/items/$itemId',
        options: _opts,
      );
      if (response.statusCode != 200 && response.statusCode != 204) {
        _throwFromResponse(response);
      }
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  // ─── Payment ────────────────────────────────────────────────────────────────

  @override
  Future<List<PaymentMethodSettingModel>> getPaymentMethodSettings() async {
    try {
      final response = await _dio.get(
        '${AppConstants.baseUrl}/tenant/payment-method-settings',
        options: _opts,
      );
      if (response.statusCode == 200) {
        final data = response.data['data'] as List<dynamic>;
        return data
            .map(
              (e) => PaymentMethodSettingModel.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList();
      }
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<PaymentModel> recordPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? referenceNo,
  }) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/payments',
        data: {
          'amount': amount,
          'payment_method': paymentMethod,
          if (referenceNo != null) 'reference_no': referenceNo,
        },
        options: _opts,
      );
      if (response.statusCode == 201) {
        final data = response.data['data'] as Map<String, dynamic>;
        return PaymentModel.fromJson(data);
      }
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  @override
  Future<OrderModel> updateOrderStatus({
    required String orderId,
    required String status,
  }) async {
    try {
      final response = await _dio.patch(
        '${AppConstants.baseUrl}/tenant/orders/$orderId/status',
        data: {'status': status},
        options: _opts,
      );
      if (response.statusCode == 200) return _parseOrder(response.data);
      _throwFromResponse(response);
    } on DioException catch (e) {
      _handleDioError(e);
    }
  }

  // ─── Internal error helper ──────────────────────────────────────────────────

  Never _throwFromResponse(Response response) {
    final data = response.data as Map<String, dynamic>?;
    final message =
        data?['message'] as String? ??
        'Lỗi máy chủ (${response.statusCode}).';
    throw ServerException(message: message, statusCode: response.statusCode);
  }
}
