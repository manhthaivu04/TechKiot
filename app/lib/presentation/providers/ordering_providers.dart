import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/item_entity.dart';
import '../../domain/entities/item_group_entity.dart';
import '../../domain/entities/order_entity.dart';
import '../../domain/entities/restaurant_table_entity.dart';
import '../../domain/entities/table_area_entity.dart';
import '../../domain/entities/payment_method_setting_entity.dart';
import 'dependency_providers.dart';

// ─── Remote data providers (AsyncNotifier) ────────────────────────────────────

/// Danh sách khu vực bàn
final tableAreasProvider =
    FutureProvider.autoDispose<List<TableAreaEntity>>((ref) async {
  return ref.watch(getTableAreasUseCaseProvider).call();
});

/// Danh sách bàn (có thể lọc theo area)
final restaurantTablesProvider =
    FutureProvider.autoDispose<List<RestaurantTableEntity>>((ref) async {
  return ref.watch(getRestaurantTablesUseCaseProvider).call();
});

/// Danh mục món
final itemGroupsProvider =
    FutureProvider.autoDispose<List<ItemGroupEntity>>((ref) async {
  return ref.watch(getItemGroupsUseCaseProvider).call();
});

/// Danh sách món
final menuItemsProvider =
    FutureProvider.autoDispose<List<ItemEntity>>((ref) async {
  return ref.watch(getItemsUseCaseProvider).call();
});

/// Đơn đang mở (open orders) — dùng để map tableId → orderId
final openOrdersProvider =
    FutureProvider.autoDispose<List<OrderEntity>>((ref) async {
  return ref.watch(getOpenOrdersUseCaseProvider).call();
});

/// Danh sách phương thức thanh toán
final paymentMethodsProvider =
    FutureProvider.autoDispose<List<PaymentMethodSettingEntity>>((ref) async {
  return ref.watch(getPaymentMethodSettingsUseCaseProvider).call();
});

// ─── UI filter state ──────────────────────────────────────────────────────────

/// Bộ tableId nào đang có thông báo chưa đọc
final notificationTableIdsProvider =
    StateProvider<Set<String>>((ref) => {});
final tableStatusFilterProvider = StateProvider.autoDispose<String>(
  (ref) => 'all',
);

/// Khu vực đang được chọn (null = tất cả)
final selectedAreaIdProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

/// Danh mục món đang chọn (null = tất cả)
final selectedItemGroupIdProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

// ─── Cart (local state for menu selection sheet) ──────────────────────────────

/// Giỏ hàng tạm: itemId → quantity
final cartProvider =
    StateNotifierProvider.autoDispose<CartNotifier, Map<String, int>>(
  (ref) => CartNotifier(),
);

class CartNotifier extends StateNotifier<Map<String, int>> {
  CartNotifier() : super({});

  void increment(String itemId) {
    state = {...state, itemId: (state[itemId] ?? 0) + 1};
  }

  void decrement(String itemId) {
    final current = state[itemId] ?? 0;
    if (current <= 1) {
      final next = Map<String, int>.from(state)..remove(itemId);
      state = next;
    } else {
      state = {...state, itemId: current - 1};
    }
  }

  void reset() => state = {};

  int get totalItems => state.values.fold(0, (sum, q) => sum + q);

  List<CartEntry> get entries => state.entries
      .map((e) => CartEntry(itemId: e.key, quantity: e.value))
      .toList();
}

class CartEntry {
  final String itemId;
  final int quantity;
  const CartEntry({required this.itemId, required this.quantity});
}
