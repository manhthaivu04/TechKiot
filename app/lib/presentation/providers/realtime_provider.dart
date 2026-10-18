import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/services/realtime_service.dart';
import 'dependency_providers.dart';

/// Provider khởi tạo WebSocket connection và expose stream NewQrOrder.
final realtimeConnectionProvider = AsyncNotifierProvider<RealtimeNotifier, void>(
  RealtimeNotifier.new,
);

class RealtimeNotifier extends AsyncNotifier<void> {
  @override
  Future<void> build() async {
    final service = ref.watch(realtimeServiceProvider);
    final restaurantId = ref.watch(currentRestaurantIdProvider);

    if (restaurantId != null && restaurantId.isNotEmpty) {
      await service.connect(restaurantId);
    }

    // Cleanup khi provider bị dispose
    ref.onDispose(() => service.disconnect());
  }
}

/// Stream provider — expose events NewQrOrder cho UI lắng nghe.
final newQrOrderStreamProvider = StreamProvider<NewQrOrderEvent>((ref) {
  final service = ref.watch(realtimeServiceProvider);
  return service.onNewQrOrder;
});

final orderStatusTransitionStreamProvider = StreamProvider<OrderStatusTransitionedEvent>((ref) {
  final service = ref.watch(realtimeServiceProvider);
  return service.onOrderStatusTransitioned;
});

final orderItemUpdatedStreamProvider = StreamProvider<OrderItemUpdatedEvent>((ref) {
  final service = ref.watch(realtimeServiceProvider);
  return service.onOrderItemUpdated;
});
