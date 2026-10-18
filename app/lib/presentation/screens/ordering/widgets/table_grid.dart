import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/enums/TableStatus.dart';
import '../../../../domain/entities/order_entity.dart';
import '../../../../domain/entities/restaurant_table_entity.dart';
import '../../../providers/ordering_providers.dart';
import 'menu_selection_sheet.dart';
import 'ordering_design_tokens.dart';
import 'table_card.dart';
import '../order_detail_screen.dart';

class TableGrid extends ConsumerWidget {
  const TableGrid({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tablesAsync = ref.watch(restaurantTablesProvider);
    final openOrdersAsync = ref.watch(openOrdersProvider);
    final statusFilter = ref.watch(tableStatusFilterProvider);
    final selectedAreaId = ref.watch(selectedAreaIdProvider);
    final notifTableIds = ref.watch(notificationTableIdsProvider);

    return tablesAsync.when(
      loading: () => const Center(
        child: CircularProgressIndicator(color: OrderingDesignTokens.accent),
      ),
      error: (e, _) => Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.wifi_off,
                color: OrderingDesignTokens.textLight,
                size: 48,
              ),
              const SizedBox(height: 12),
              Text(
                'Không tải được sơ đồ bàn\n$e',
                textAlign: TextAlign.center,
                style: const TextStyle(color: OrderingDesignTokens.textLight),
              ),
              const SizedBox(height: 12),
              ElevatedButton.icon(
                onPressed: () => ref.invalidate(restaurantTablesProvider),
                icon: const Icon(Icons.refresh),
                label: const Text('Thử lại'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: OrderingDesignTokens.accent,
                ),
              ),
            ],
          ),
        ),
      ),
      data: (tables) {
        // Build a map tableId → order for occupied tables
        final Map<String, OrderEntity> tableOrderMap = {};
        final openOrders = openOrdersAsync.valueOrNull ?? [];
        
        for (final order in openOrders) {
          if (order.tableId != null) {
            tableOrderMap[order.tableId!] = order;
          }
        }

        debugPrint("========== OPEN ORDERS ==========");

        for (final order in openOrders) {
          debugPrint(
            "orderId=${order.id}"
            " tableId=${order.tableId}"
            " status=${order.status}"
            " amount=${order.finalAmount}",
          );
        }

        // Prepend takeaway card
        final takeawayCard = TableCard(
          table: null,
          order: null,
          onTap: () => _openMenuSheet(context, ref, null, null),
        );

        // Filter tables
        var filtered = tables.where((t) {
          if (selectedAreaId != null && t.areaId != selectedAreaId) {
            return false;
          }
          if (statusFilter == 'occupied') {
            return t.status == TableStatus.occupied;
          }
          if (statusFilter == 'available') {
            return t.status == TableStatus.available;
          }
          return true;
        }).toList();

        final all = <Widget>[
          if (statusFilter == 'all' || statusFilter == 'available')
            takeawayCard,
          ...filtered.map(
            (t) => TableCard(
              table: t,
              order: tableOrderMap[t.id],
              hasNotification: notifTableIds.contains(t.id),
              onTap: () {
                // Clear notification badge for this table
                if (notifTableIds.contains(t.id)) {
                  final notifier = ref.read(notificationTableIdsProvider.notifier);
                  notifier.state = Set.from(notifier.state)..remove(t.id);
                }
                _openMenuSheet(context, ref, t, tableOrderMap[t.id]);
              },
            ),
          ),
        ];

        if (all.isEmpty) {
          return const Center(
            child: Text(
              'Không có bàn nào',
              style: TextStyle(
                color: OrderingDesignTokens.textLight,
                fontSize: 15,
              ),
            ),
          );
        }

        return RefreshIndicator(
          color: OrderingDesignTokens.accent,
          onRefresh: () async {
            ref.invalidate(restaurantTablesProvider);
            ref.invalidate(openOrdersProvider);
            // Wait for both providers to complete
            await ref
                .read(restaurantTablesProvider.future)
                .catchError((_) => <RestaurantTableEntity>[]);
          },
          child: GridView.extent(
            padding: const EdgeInsets.all(12),
            maxCrossAxisExtent: 160,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            childAspectRatio: 1.15,
            children: all,
          ),
        );
      },
    );
  }

  void _openMenuSheet(
    BuildContext context,
    WidgetRef ref,
    RestaurantTableEntity? table,
    OrderEntity? existingOrder,
  ) {
    if (existingOrder != null && table != null) {
      // Bàn đã có khách -> Mở màn hình chi tiết đơn
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) =>
              OrderDetailScreen(order: existingOrder, table: table),
        ),
      ).then((_) {
        // Refresh khi quay lại màn hình chính
        ref.invalidate(restaurantTablesProvider);
        ref.invalidate(openOrdersProvider);
      });
    } else {
      // Reset cart before opening menu
      ref.read(cartProvider.notifier).reset();
      showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (_) => ProviderScope(
          overrides: const [],
          child: MenuSelectionSheet(table: table, existingOrder: existingOrder),
        ),
      ).then((_) {
        // Refresh sau khi đóng menu
        ref.invalidate(restaurantTablesProvider);
        ref.invalidate(openOrdersProvider);
      });
    }
  }
}
