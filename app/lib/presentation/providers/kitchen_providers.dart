import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/order_entity.dart';
import 'dependency_providers.dart';

// ─── Kitchen Orders ──────────────────────────────────────────────────────────

/// Danh sách đơn hàng cần bếp xử lý (status = open hoặc cooking).
/// Dùng [AsyncNotifier] để có thể refresh thủ công sau mỗi action.
final kitchenOrdersProvider =
    AsyncNotifierProvider<KitchenOrdersNotifier, List<OrderEntity>>(
  KitchenOrdersNotifier.new,
);

class KitchenOrdersNotifier extends AsyncNotifier<List<OrderEntity>> {
  @override
  Future<List<OrderEntity>> build() async {
    return ref.watch(getKitchenOrdersUseCaseProvider).call();
  }

  /// Gọi lại API để cập nhật danh sách âm thầm (background refresh)
  Future<void> refresh() async {
    final nextState = await AsyncValue.guard(
      () => ref.read(getKitchenOrdersUseCaseProvider).call(),
    );
    state = nextState;
  }

  /// Cập nhật trạng thái từng món và làm mới danh sách
  Future<void> updateItemStatus({
    required String orderId,
    required String itemId,
    required String status,
  }) async {
    try {
      final updatedOrder = await ref
          .read(updateOrderItemStatusUseCaseProvider)
          .call(orderId: orderId, itemId: itemId, status: status);

      // Cập nhật optimistic: thay đơn hàng cũ bằng đơn hàng mới trong list
      final current = state.value ?? [];
      final updated = current.map((o) {
        return o.id == updatedOrder.id ? updatedOrder : o;
      }).toList();
      state = AsyncData(updated);
    } catch (e, st) {
      // Không cập nhật state lỗi, chỉ rethrow để UI xử lý
      Error.throwWithStackTrace(e, st);
    }
  }

  /// Chuyển trạng thái cả đơn hàng (ví dụ: sang "served")
  Future<void> updateOrderStatus({
    required String orderId,
    required String status,
  }) async {
    try {
      final updatedOrder = await ref
          .read(updateOrderStatusUseCaseProvider)
          .call(orderId: orderId, status: status);

      final current = state.value ?? [];
      final updated = current
          .map((o) => o.id == updatedOrder.id ? updatedOrder : o)
          .where((o) => o.status.name == 'cooking')
          .toList();
      state = AsyncData(updated);
    } catch (e, st) {
      Error.throwWithStackTrace(e, st);
    }
  }
}

// ─── UI Filter ───────────────────────────────────────────────────────────────

/// Bộ lọc trạng thái đơn trên màn hình bếp: 'all' | 'open' | 'cooking'
final kitchenStatusFilterProvider = StateProvider<String>((ref) => 'all');
