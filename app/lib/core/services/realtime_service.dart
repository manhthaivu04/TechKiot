import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../constants/app_constants.dart';

/// Sự kiện đơn QR mới nhận từ Reverb
class NewQrOrderEvent {
  final String orderCode;
  final String? tableId;
  final String restaurantId;
  final Map<String, dynamic> rawData;

  const NewQrOrderEvent({
    required this.orderCode,
    required this.restaurantId,
    this.tableId,
    required this.rawData,
  });

  factory NewQrOrderEvent.fromJson(Map<String, dynamic> json) {
    // Laravel might serialize public properties like 'order' nested, or at the root.
    final orderData = json['order'] is Map<String, dynamic>
        ? json['order'] as Map<String, dynamic>
        : json;

    return NewQrOrderEvent(
      orderCode: orderData['order_code'] as String? ?? '',
      tableId: orderData['table_id'] as String?,
      restaurantId: orderData['restaurant_id'] as String? ?? '',
      rawData: json,
    );
  }
}

/// Sự kiện thay đổi trạng thái đơn
class OrderStatusTransitionedEvent {
  final String orderId;
  final String orderCode;
  final String? tableId;
  final String? toStatus;
  final Map<String, dynamic> rawData;

  const OrderStatusTransitionedEvent({
    required this.orderId,
    required this.orderCode,
    this.tableId,
    this.toStatus,
    required this.rawData,
  });

  factory OrderStatusTransitionedEvent.fromJson(Map<String, dynamic> json) {
    final orderData = json['order'] is Map<String, dynamic> ? json['order'] : json;
    final toVal = json['to'];
    String? toStatusStr;
    if (toVal is String) {
      toStatusStr = toVal;
    } else if (toVal is Map) {
      toStatusStr = toVal['value'] as String? ?? toVal['name'] as String?;
    }
    return OrderStatusTransitionedEvent(
      orderId: orderData['id'] as String? ?? orderData['order_id'] as String? ?? '',
      orderCode: orderData['order_code'] as String? ?? 'Chưa rõ',
      tableId: orderData['table_id'] as String?,
      toStatus: toStatusStr ?? orderData['status'] as String?,
      rawData: json,
    );
  }
}

/// Sự kiện món trong đơn được cập nhật (ví dụ: nhà bếp thay đổi món)
class OrderItemUpdatedEvent {
  final String orderId;
  final String orderCode;
  final String? tableId;
  final bool isAdded;
  final String? itemStatus;
  final Map<String, dynamic> rawData;

  const OrderItemUpdatedEvent({
    required this.orderId,
    required this.orderCode,
    this.tableId,
    this.isAdded = false,
    this.itemStatus,
    required this.rawData,
  });

  factory OrderItemUpdatedEvent.fromJson(Map<String, dynamic> json, {bool isAdded = false}) {
    final orderData = json['order'] is Map<String, dynamic> ? json['order'] : json;
    final orderItemData = json['orderItem'] is Map<String, dynamic> ? json['orderItem'] : null;
    
    String? status;
    if (orderItemData != null && orderItemData['status'] != null) {
      status = orderItemData['status'] is Map ? orderItemData['status']['value'] ?? orderItemData['status']['name'] : orderItemData['status'].toString();
    }

    return OrderItemUpdatedEvent(
      orderId: orderData['id'] as String? ?? orderData['order_id'] as String? ?? '',
      orderCode: orderData['order_code'] as String? ?? 'Chưa rõ',
      tableId: orderData['table_id'] as String?,
      isAdded: isAdded,
      itemStatus: status,
      rawData: json,
    );
  }
}

/// Service quản lý WebSocket connection đến Laravel Reverb sử dụng WebSocketChannel (Pure Dart).
class RealtimeService {
  WebSocketChannel? _channel;
  StreamSubscription? _subscription;
  final _newQrOrderController = StreamController<NewQrOrderEvent>.broadcast();
  final _orderStatusController = StreamController<OrderStatusTransitionedEvent>.broadcast();
  final _orderItemController = StreamController<OrderItemUpdatedEvent>.broadcast();
  bool _isConnected = false;
  String? _subscribedRestaurantId;
  Timer? _reconnectTimer;
  Timer? _pingTimer;

  Stream<NewQrOrderEvent> get onNewQrOrder => _newQrOrderController.stream;
  Stream<OrderStatusTransitionedEvent> get onOrderStatusTransitioned => _orderStatusController.stream;
  Stream<OrderItemUpdatedEvent> get onOrderItemUpdated => _orderItemController.stream;

  /// Khởi tạo và kết nối Reverb WebSocket.
  Future<void> connect(String restaurantId) async {
    if (_isConnected && _subscribedRestaurantId == restaurantId) return;

    _subscribedRestaurantId = restaurantId;
    _disconnectSocket();
    _connectSocket();
  }

  void _connectSocket() {
    if (_subscribedRestaurantId == null) return;

    final host = AppConstants.reverbHost;
    final port = AppConstants.reverbPort;
    final appKey = AppConstants.reverbAppKey;
    final scheme = AppConstants.reverbScheme == 'https' ? 'wss' : 'ws';
    final portStr = (port == 443 || port == 80) ? '' : ':$port';
    final wsUrl = '$scheme://$host$portStr/app/$appKey?protocol=7&client=js&version=8.4.0-rc2&flash=false';

    debugPrint('[Reverb] Connecting to: $wsUrl');

    try {
      _channel = WebSocketChannel.connect(Uri.parse(wsUrl));
      _isConnected = true;

      _subscription = _channel!.stream.listen(
        (message) {
          _handleMessage(message.toString());
        },
        onError: (error) {
          debugPrint('[Reverb] Connection error: $error');
          _handleDisconnect();
        },
        onDone: () {
          debugPrint('[Reverb] Connection closed.');
          _handleDisconnect();
        },
      );

      // Start pinging to keep connection alive
      _startPingTimer();

    } catch (e) {
      debugPrint('[Reverb] Connect failed: $e');
      _handleDisconnect();
    }
  }

  void _handleMessage(String message) {
    debugPrint('[Reverb] Received: $message');
    try {
      final json = jsonDecode(message) as Map<String, dynamic>;
      final event = json['event'] as String?;

      if (event == 'pusher:connection_established') {
        debugPrint('[Reverb] Connection established. Subscribing to channels...');
        _subscribeToChannel();
      } else if (event == 'NewQrOrder' || 
                 event == 'OrderPlaced' || 
                 event == 'App\\Events\\Broadcasts\\CashierNewQrOrderBroadcast' ||
                 event == 'App\\Events\\Broadcasts\\KitchenOrderPlacedBroadcast' ||
                 event == 'KitchenOrderPlacedBroadcast') {
        final dataString = json['data'];
        final Map<String, dynamic> data = dataString is String
            ? Map<String, dynamic>.from(jsonDecode(dataString) as Map)
            : Map<String, dynamic>.from(dataString as Map);

        final qrEvent = NewQrOrderEvent.fromJson(data);
        _newQrOrderController.add(qrEvent);
      } else if (event == 'OrderStatusTransitioned' || 
                 event == 'App\\Events\\Broadcasts\\OrderStatusTransitioned' ||
                 event == 'App\\Events\\OrderStatusTransitioned') {
        final dataString = json['data'];
        final Map<String, dynamic> data = dataString is String
            ? Map<String, dynamic>.from(jsonDecode(dataString) as Map)
            : Map<String, dynamic>.from(dataString as Map);

        final transitionEvent = OrderStatusTransitionedEvent.fromJson(data);
        _orderStatusController.add(transitionEvent);
      } else if (event == 'OrderItemUpdated' || 
                 event == 'OrderItemsAdded' ||
                 event == 'App\\Events\\Broadcasts\\OrderItemUpdated' ||
                 event == 'App\\Events\\OrderItemUpdated' ||
                 event == 'App\\Events\\Broadcasts\\KitchenOrderItemsAddedBroadcast' ||
                 event == 'App\\Events\\Broadcasts\\CashierOrderItemsAddedBroadcast' ||
                 event == 'App\\Events\\Broadcasts\\UIOrderItemsAddedBroadcast') {
        final dataString = json['data'];
        final Map<String, dynamic> data = dataString is String
            ? Map<String, dynamic>.from(jsonDecode(dataString) as Map)
            : Map<String, dynamic>.from(dataString as Map);

        final isAdded = event == 'OrderItemsAdded' ||
            event == 'App\\Events\\Broadcasts\\KitchenOrderItemsAddedBroadcast' ||
            event == 'App\\Events\\Broadcasts\\CashierOrderItemsAddedBroadcast' ||
            event == 'App\\Events\\Broadcasts\\UIOrderItemsAddedBroadcast';
        final itemEvent = OrderItemUpdatedEvent.fromJson(data, isAdded: isAdded);
        _orderItemController.add(itemEvent);
      } else if (event == 'pusher:ping') {
        _sendFrame('pusher:pong', {});
      }
    } catch (e) {
      debugPrint('[Reverb] Parse message error: $e');
    }
  }

  void _subscribeToChannel() {
    if (_subscribedRestaurantId == null) return;
    
    // Subscribe to cashier channel
    _sendFrame('pusher:subscribe', {
      'channel': 'restaurant.$_subscribedRestaurantId.cashier',
    });
    debugPrint('[Reverb] Subscribed to restaurant.$_subscribedRestaurantId.cashier');

    // Subscribe to kitchen channel
    _sendFrame('pusher:subscribe', {
      'channel': 'restaurant.$_subscribedRestaurantId.kitchen',
    });
    debugPrint('[Reverb] Subscribed to restaurant.$_subscribedRestaurantId.kitchen');
  }

  void _sendFrame(String event, Map<String, dynamic> data) {
    if (_channel == null || !_isConnected) return;
    final frame = jsonEncode({
      'event': event,
      'data': data,
    });
    _channel!.sink.add(frame);
  }

  void _startPingTimer() {
    _pingTimer?.cancel();
    _pingTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      if (_isConnected) {
        _sendFrame('pusher:ping', {});
      }
    });
  }

  void _handleDisconnect() {
    _isConnected = false;
    _pingTimer?.cancel();
    _subscription?.cancel();
    _channel = null;

    // Retry connection after 5 seconds
    _reconnectTimer?.cancel();
    if (_subscribedRestaurantId != null) {
      _reconnectTimer = Timer(const Duration(seconds: 5), () {
        debugPrint('[Reverb] Retrying connection...');
        _connectSocket();
      });
    }
  }

  void _disconnectSocket() {
    _reconnectTimer?.cancel();
    _pingTimer?.cancel();
    _subscription?.cancel();
    if (_channel != null) {
      _channel!.sink.close();
      _channel = null;
    }
    _isConnected = false;
  }

  Future<void> disconnect() async {
    _subscribedRestaurantId = null;
    _disconnectSocket();
    debugPrint('[Reverb] Disconnected');
  }

  void dispose() {
    disconnect();
    _newQrOrderController.close();
    _orderStatusController.close();
    _orderItemController.close();
  }
}
