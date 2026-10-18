import '../entities/order_entity.dart';
import '../entities/payment_entity.dart';
import '../entities/payment_method_setting_entity.dart';
import '../repositories/order_repository.dart';

// Re-export so callers don't need to import order_repository.dart separately.
export '../repositories/order_repository.dart' show OrderItemInput;

// ─── Order Use Cases ─────────────────────────────────────────────────────────

/// Use case: Create a dine-in order for a specific table.
class CreateDineInOrderUseCase {
  final OrderRepository _repository;
  const CreateDineInOrderUseCase(this._repository);

  Future<OrderEntity> call({
    required String tableId,
    required List<OrderItemInput> items,
  }) => _repository.createDineInOrder(tableId: tableId, items: items);
}

/// Use case: Create a takeaway order.
class CreateTakeawayOrderUseCase {
  final OrderRepository _repository;
  const CreateTakeawayOrderUseCase(this._repository);

  Future<OrderEntity> call({
    String? customerName,
    String? customerPhone,
    String? note,
    required List<OrderItemInput> items,
  }) => _repository.createTakeawayOrder(
    customerName: customerName,
    customerPhone: customerPhone,
    note: note,
    items: items,
  );
}

/// Use case: Fetch all currently open orders.
class GetOpenOrdersUseCase {
  final OrderRepository _repository;
  const GetOpenOrdersUseCase(this._repository);

  Future<List<OrderEntity>> call() => _repository.getOpenOrders();
}

/// Use case: Add more items to an existing open order.
class AddItemsToOrderUseCase {
  final OrderRepository _repository;
  const AddItemsToOrderUseCase(this._repository);

  Future<OrderEntity> call({
    required String orderId,
    required List<OrderItemInput> items,
  }) => _repository.addItemsToOrder(orderId: orderId, items: items);
}

/// Use case: Update quantity / note of an already-ordered item.
class UpdateOrderItemUseCase {
  final OrderRepository _repository;
  const UpdateOrderItemUseCase(this._repository);

  Future<void> call({
    required String orderId,
    required String itemId,
    int? quantity,
    String? note,
  }) => _repository.updateOrderItem(
    orderId: orderId,
    itemId: itemId,
    quantity: quantity,
    note: note,
  );
}

/// Use case: Remove an item from an order.
class RemoveOrderItemUseCase {
  final OrderRepository _repository;
  const RemoveOrderItemUseCase(this._repository);

  Future<void> call({
    required String orderId,
    required String itemId,
  }) => _repository.removeOrderItem(orderId: orderId, itemId: itemId);
}

// ─── Payment Use Cases ────────────────────────────────────────────────────────

/// Use case: Fetch all active payment method settings.
class GetPaymentMethodSettingsUseCase {
  final OrderRepository _repository;
  const GetPaymentMethodSettingsUseCase(this._repository);

  Future<List<PaymentMethodSettingEntity>> call() =>
      _repository.getPaymentMethodSettings();
}

/// Use case: Record a payment transaction for an order.
class RecordPaymentUseCase {
  final OrderRepository _repository;
  const RecordPaymentUseCase(this._repository);

  Future<PaymentEntity> call({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? referenceNo,
  }) => _repository.recordPayment(
    orderId: orderId,
    amount: amount,
    paymentMethod: paymentMethod,
    referenceNo: referenceNo,
  );
}

/// Use case: Transition an order status (e.g. to "paid").
class UpdateOrderStatusUseCase {
  final OrderRepository _repository;
  const UpdateOrderStatusUseCase(this._repository);

  Future<OrderEntity> call({
    required String orderId,
    required String status,
  }) => _repository.updateOrderStatus(orderId: orderId, status: status);
}

/// Use case: Fetch orders visible to kitchen staff (open + cooking).
class GetKitchenOrdersUseCase {
  final OrderRepository _repository;
  const GetKitchenOrdersUseCase(this._repository);

  Future<List<OrderEntity>> call() => _repository.getKitchenOrders();
}

/// Use case: Update the cooking status of a single order item (KDS).
class UpdateOrderItemStatusUseCase {
  final OrderRepository _repository;
  const UpdateOrderItemStatusUseCase(this._repository);

  Future<OrderEntity> call({
    required String orderId,
    required String itemId,
    required String status,
  }) => _repository.updateOrderItemStatus(
    orderId: orderId,
    itemId: itemId,
    status: status,
  );
}
