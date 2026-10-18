import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../domain/entities/item_entity.dart';
import '../../../../domain/entities/item_group_entity.dart';
import '../../../../domain/entities/order_entity.dart';
import '../../../../domain/entities/restaurant_table_entity.dart';
import '../../../../domain/repositories/order_repository.dart';
import '../../../providers/dependency_providers.dart';
import '../../../providers/ordering_providers.dart';
import 'ordering_design_tokens.dart';

class MenuSelectionSheet extends ConsumerStatefulWidget {
  final RestaurantTableEntity? table; // null = takeaway
  final OrderEntity? existingOrder;

  const MenuSelectionSheet({
    super.key,
    required this.table,
    required this.existingOrder,
  });

  @override
  ConsumerState<MenuSelectionSheet> createState() => _MenuSelectionSheetState();
}

class _MenuSelectionSheetState extends ConsumerState<MenuSelectionSheet> {
  bool _isSubmitting = false;

  bool get _isTakeaway => widget.table == null;

  @override
  Widget build(BuildContext context) {
    final screenH = MediaQuery.of(context).size.height;
    final screenW = MediaQuery.of(context).size.width;
    final cart = ref.watch(cartProvider);
    final totalItems = cart.values.fold(0, (s, v) => s + v);

    Widget content = Container(
      height: screenH * 0.88,
      decoration: const BoxDecoration(
        color: OrderingDesignTokens.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          _buildHandle(),
          _buildSheetAppBar(context),
          const CategoryTabBar(),
          const Divider(height: 1),
          Expanded(child: ItemList(isTakeaway: _isTakeaway)),
          _buildBottomActions(context, totalItems),
        ],
      ),
    );

    if (screenW > 600) {
      content = Align(
        alignment: Alignment.bottomCenter,
        child: Container(
          width: 600,
          clipBehavior: Clip.antiAlias,
          decoration: const BoxDecoration(
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: content,
        ),
      );
    }

    return content;
  }

  Widget _buildHandle() {
    return Padding(
      padding: const EdgeInsets.only(top: 10, bottom: 4),
      child: Container(
        width: 36,
        height: 4,
        decoration: BoxDecoration(
          color: Colors.grey[300],
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );
  }

  Widget _buildSheetAppBar(BuildContext context) {
    final title = _isTakeaway
        ? 'Mang đi'
        : widget.table!.name;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => Navigator.of(context).pop(),
            child: const Icon(Icons.close, color: Colors.red, size: 22),
          ),
          const SizedBox(width: 12),
          Text(
            title,
            style: const TextStyle(
              color: OrderingDesignTokens.accent,
              fontWeight: FontWeight.w800,
              fontSize: 18,
            ),
          ),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.add, color: OrderingDesignTokens.accent),
            onPressed: () {},
            tooltip: 'Gọi thêm',
          ),
          IconButton(
            icon: const Icon(Icons.search, color: OrderingDesignTokens.textDark),
            onPressed: () {},
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActions(BuildContext context, int totalItems) {
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: OrderingDesignTokens.surface,
          border: Border(top: BorderSide(color: Colors.grey[200]!)),
        ),
        child: Row(
          children: [
            OutlinedButton(
              onPressed: () => ref.read(cartProvider.notifier).reset(),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: OrderingDesignTokens.textLight),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(24),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              ),
              child: const Text(
                'Chọn lại',
                style: TextStyle(color: OrderingDesignTokens.textDark, fontSize: 14),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton(
                onPressed: totalItems > 0 && !_isSubmitting
                    ? () => _submitOrder(context)
                    : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: OrderingDesignTokens.accent,
                  disabledBackgroundColor: OrderingDesignTokens.accent.withValues(alpha: 0.4),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          color: Colors.white,
                          strokeWidth: 2,
                        ),
                      )
                    : Text(
                        totalItems > 0
                            ? 'Thêm vào đơn  $totalItems'
                            : 'Thêm vào đơn',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                        ),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitOrder(BuildContext context) async {
    setState(() => _isSubmitting = true);

    try {
      final cartEntries = ref.read(cartProvider.notifier).entries;
      final items = cartEntries
          .map((e) => OrderItemInput(itemId: e.itemId, quantity: e.quantity))
          .toList();

      if (widget.existingOrder != null) {
        // Gọi thêm món vào đơn hiện có
        await ref.read(addItemsToOrderUseCaseProvider).call(
              orderId: widget.existingOrder!.id,
              items: items,
            );
      } else if (_isTakeaway) {
        // Tạo đơn mang đi
        await ref.read(createTakeawayOrderUseCaseProvider).call(items: items);
      } else {
        // Tạo đơn dine-in
        await ref.read(createDineInOrderUseCaseProvider).call(
              tableId: widget.table!.id,
              items: items,
            );
      }

      // Refresh bàn và đơn sau khi tạo
      ref.invalidate(restaurantTablesProvider);
      ref.invalidate(openOrdersProvider);

      if (context.mounted) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Đặt món thành công!'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Lỗi: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }
}

// ─── Category Tab Bar ─────────────────────────────────────────────────────────

class CategoryTabBar extends ConsumerWidget {
  const CategoryTabBar({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final groupsAsync = ref.watch(itemGroupsProvider);
    final selectedId = ref.watch(selectedItemGroupIdProvider);

    return groupsAsync.when(
      loading: () => const SizedBox(height: 44),
      error: (_, __) => const SizedBox.shrink(),
      data: (groups) {
        final all = <ItemGroupEntity?>[null, ...groups];
        return SizedBox(
          height: 44,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: all.length,
            itemBuilder: (_, i) {
              final group = all[i];
              final isSelected = group?.id == selectedId;
              final label = group?.name ?? 'Tất cả';
              return Padding(
                padding: const EdgeInsets.only(right: 8, top: 6, bottom: 6),
                child: GestureDetector(
                  onTap: () {
                    ref.read(selectedItemGroupIdProvider.notifier).state =
                        group?.id;
                  },
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 18, vertical: 4),
                    decoration: BoxDecoration(
                      color: isSelected ? OrderingDesignTokens.accent : const Color(0xFFF0F0F0),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      label,
                      style: TextStyle(
                        color: isSelected ? Colors.white : OrderingDesignTokens.textDark,
                        fontWeight: isSelected
                            ? FontWeight.w700
                            : FontWeight.w500,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }
}

// ─── Item List ────────────────────────────────────────────────────────────────

class ItemList extends ConsumerWidget {
  final bool isTakeaway;
  const ItemList({super.key, required this.isTakeaway});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(menuItemsProvider);
    final selectedGroupId = ref.watch(selectedItemGroupIdProvider);
    final cart = ref.watch(cartProvider);

    return itemsAsync.when(
      loading: () => const Center(
          child: CircularProgressIndicator(color: OrderingDesignTokens.accent)),
      error: (e, _) => Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Text('Lỗi tải menu: $e',
              textAlign: TextAlign.center,
              style: const TextStyle(color: OrderingDesignTokens.textLight)),
        ),
      ),
      data: (items) {
        final filtered = selectedGroupId == null
            ? items
            : items.where((i) => i.itemGroupId == selectedGroupId).toList();

        if (filtered.isEmpty) {
          return const Center(
            child: Text('Không có món trong danh mục này',
                style: TextStyle(color: OrderingDesignTokens.textLight)),
          );
        }

        return ListView.builder(
          padding: const EdgeInsets.symmetric(vertical: 4),
          itemCount: filtered.length,
          itemBuilder: (_, i) => ItemRow(
            item: filtered[i],
            quantity: cart[filtered[i].id] ?? 0,
          ),
        );
      },
    );
  }
}

// ─── Item row ─────────────────────────────────────────────────────────────────

class ItemRow extends ConsumerWidget {
  final ItemEntity item;
  final int quantity;

  const ItemRow({super.key, required this.item, required this.quantity});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifier = ref.read(cartProvider.notifier);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: OrderingDesignTokens.surface,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Thumbnail
          ClipRRect(
            borderRadius: const BorderRadius.horizontal(
                left: Radius.circular(12)),
            child: item.imageUrl != null
                ? Image.network(
                    item.imageUrl!,
                    width: 90,
                    height: 80,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _placeholder(),
                  )
                : _placeholder(),
          ),
          // Info
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: 12, vertical: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.name,
                    style: const TextStyle(
                      color: OrderingDesignTokens.textDark,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _formatCurrency(item.salePrice),
                    style: const TextStyle(
                      color: OrderingDesignTokens.accent,
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Quantity controls
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: Row(
              children: [
                QtyButton(
                  icon: Icons.remove,
                  onTap: quantity > 0
                      ? () => notifier.decrement(item.id)
                      : null,
                ),
                SizedBox(
                  width: 28,
                  child: Text(
                    '$quantity',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: OrderingDesignTokens.textDark,
                    ),
                  ),
                ),
                QtyButton(
                  icon: Icons.add,
                  onTap: () => notifier.increment(item.id),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _placeholder() {
    return Container(
      width: 90,
      height: 80,
      color: const Color(0xFFF0E8D8),
      child: const Icon(Icons.restaurant, color: OrderingDesignTokens.accent, size: 28),
    );
  }

  String _formatCurrency(double amount) {
    final intPart = amount.toInt();
    return '${intPart.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')} đ';
  }
}

// ─── Quantity button ──────────────────────────────────────────────────────────

class QtyButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;

  const QtyButton({super.key, required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: onTap != null ? OrderingDesignTokens.accent : OrderingDesignTokens.textLight,
            width: 1.5,
          ),
        ),
        child: Icon(
          icon,
          size: 16,
          color: onTap != null ? OrderingDesignTokens.accent : OrderingDesignTokens.textLight,
        ),
      ),
    );
  }
}
