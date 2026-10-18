import '../../domain/entities/order_entity.dart';
import '../../domain/entities/payment_entity.dart';
import '../../domain/entities/payment_method_setting_entity.dart';
import '../../domain/repositories/order_repository.dart';
import '../datasources/order_remote_datasource.dart';

/// Concrete implementation of [OrderRepository].
/// Maps domain [OrderItemInput] to data-layer [OrderItemRequestDto].
class OrderRepositoryImpl implements OrderRepository {
  final OrderRemoteDataSource _dataSource;

  const OrderRepositoryImpl(this._dataSource);

  // ─── Conversion helper ────────────────────────────────────────────────────

  List<OrderItemRequestDto> _toDtoList(List<OrderItemInput> inputs) =>
      inputs
          .map(
            (i) => OrderItemRequestDto(
              itemId: i.itemId,
              quantity: i.quantity,
              note: i.note,
            ),
          )
          .toList();

  // ─── Order ────────────────────────────────────────────────────────────────

  @override
  Future<OrderEntity> createDineInOrder({
    required String tableId,
    required List<OrderItemInput> items,
  }) => _dataSource.createDineInOrder(
    CreateDineInOrderDto(tableId: tableId, items: _toDtoList(items)),
  );

  @override
  Future<OrderEntity> createTakeawayOrder({
    String? customerName,
    String? customerPhone,
    String? note,
    required List<OrderItemInput> items,
  }) => _dataSource.createTakeawayOrder(
    CreateTakeawayOrderDto(
      customerName: customerName,
      customerPhone: customerPhone,
      note: note,
      items: _toDtoList(items),
    ),
  );

  @override
  Future<List<OrderEntity>> getOpenOrders() => _dataSource.getOpenOrders();

  @override
  Future<OrderEntity> addItemsToOrder({
    required String orderId,
    required List<OrderItemInput> items,
  }) => _dataSource.addItemsToOrder(
    orderId: orderId,
    items: _toDtoList(items),
  );

  @override
  Future<void> updateOrderItem({
    required String orderId,
    required String itemId,
    int? quantity,
    String? note,
  }) => _dataSource.updateOrderItem(
    orderId: orderId,
    itemId: itemId,
    quantity: quantity,
    note: note,
  );

  @override
  Future<void> removeOrderItem({
    required String orderId,
    required String itemId,
  }) => _dataSource.removeOrderItem(orderId: orderId, itemId: itemId);

  // ─── Payment ──────────────────────────────────────────────────────────────

  @override
  Future<List<PaymentMethodSettingEntity>> getPaymentMethodSettings() =>
      _dataSource.getPaymentMethodSettings();

  @override
  Future<PaymentEntity> recordPayment({
    required String orderId,
    required double amount,
    required String paymentMethod,
    String? referenceNo,
  }) => _dataSource.recordPayment(
    orderId: orderId,
    amount: amount,
    paymentMethod: paymentMethod,
    referenceNo: referenceNo,
  );

  @override
  Future<OrderEntity> updateOrderStatus({
    required String orderId,
    required String status,
  }) => _dataSource.updateOrderStatus(orderId: orderId, status: status);

  @override
  Future<List<OrderEntity>> getKitchenOrders() => _dataSource.getKitchenOrders();

  @override
  Future<OrderEntity> updateOrderItemStatus({
    required String orderId,
    required String itemId,
    required String status,
  }) => _dataSource.updateOrderItemStatus(
    orderId: orderId,
    itemId: itemId,
    status: status,
  );
}
