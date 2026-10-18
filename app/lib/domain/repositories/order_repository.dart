import '../entities/order_entity.dart';
import '../entities/payment_entity.dart';
import '../entities/payment_method_setting_entity.dart';

/// Describes a single item in an order request.
class OrderItemInput {
  final String itemId;
  final int quantity;
  final String? note;

  const OrderItemInput({
    required this.itemId,
    required this.quantity,
    this.note,
  });
}

/// Abstract contract for all Order and Payment operations.
abstract class OrderRepository {
  // ─── Order ────────────────────────────────────────────────────────────────

  /// Creates a new dine-in order for a specific table.
  Future<OrderEntity> createDineInOrder({
    required String tableId,
    required List<OrderItemInput> items,
  });

  /// Creates a new takeaway order.
  Future<OrderEntity> createTakeawayOrder({
    String? customerName,
    String? customerPhone,
    String? note,
    required List<OrderItemInput> items,
  });

  /// Returns currently open orders (status = open).
  Future<List<OrderEntity>> getOpenOrders();

  /// Appends items to an existing open order.
  Future<OrderEntity> addItemsToOrder({
    required String orderId,
    required List<OrderItemInput> items,
  });

  /// Updates quantity and/or note for a specific order item.
  Future<void> updateOrderItem({
    required String orderId,
    required String itemId,
    int? quantity,
    String? note,
  });

  /// Removes an item from an order.
  Future<void> removeOrderItem({required String orderId, required String itemId});

  // ─── Payment ──────────────────────────────────────────────────────────────

  /// Returns all active payment methods configured for the restaurant.
  Future<List<PaymentMethodSettingEntity>> getPaymentMethodSettings();

  /// Records a payment against an order.
  Future<PaymentEntity> recordPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? referenceNo,
  });

  /// Transitions an order to a new status (e.g. "paid").
  Future<OrderEntity> updateOrderStatus({
    required String orderId,
    required String status,
  });

  /// Returns orders relevant to kitchen (status = open or cooking).
  Future<List<OrderEntity>> getKitchenOrders();

  /// Updates the status of a single order item (KDS action).
  Future<OrderEntity> updateOrderItemStatus({
    required String orderId,
    required String itemId,
    required String status,
  });
}
