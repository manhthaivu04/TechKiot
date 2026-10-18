import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart';
import '../../providers/auth_controller.dart';
import '../../providers/auth_state.dart';
import '../../providers/kitchen_providers.dart';
import '../../providers/realtime_provider.dart';
import '../../../domain/entities/order_entity.dart';
import '../../../domain/entities/order_item_entity.dart';
import '../../../core/services/realtime_service.dart';

// ─── Design Tokens (đồng nhất với OrderingDesignTokens) ─────────────────────
class _K {
  static const bg = Color(0xFFF5F5F5);
  static const surface = Color(0xFFFFFFFF);
  static const accent = Color(0xFFFF8C00);       // cam — đồng nhất ordering
  static const textDark = Color(0xFF1A1A2E);
  static const textLight = Color(0xFF888888);

  // Màu trạng thái món
  static const pending = Color(0xFFEEEEEE);
  static const pendingText = Color(0xFF555555);
  static const cooking = Color(0xFFFFECCC);
  static const cookingText = Color(0xFFB85C00);
  static const cookingBorder = Color(0xFFFF8C00);
  static const ready = Color(0xFFD4EDDA);
  static const readyText = Color(0xFF155724);
  static const readyBorder = Color(0xFF28A745);
  static const served = Color(0xFFCCE5FF);
  static const servedText = Color(0xFF004085);
}

// ─── Screen ──────────────────────────────────────────────────────────────────
class KitchenScreen extends ConsumerStatefulWidget {
  const KitchenScreen({super.key});

  @override
  ConsumerState<KitchenScreen> createState() => _KitchenScreenState();
}

class _KitchenScreenState extends ConsumerState<KitchenScreen>
    with WidgetsBindingObserver {
  Timer? _autoRefreshTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    // Tự động refresh mỗi 30 giây
    _autoRefreshTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      ref.read(kitchenOrdersProvider.notifier).refresh();
    });
  }

  @override
  void dispose() {
    _autoRefreshTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.read(kitchenOrdersProvider.notifier).refresh();
    }
  }

  void _refresh() => ref.read(kitchenOrdersProvider.notifier).refresh();

  @override
  Widget build(BuildContext context) {
    ref.watch(realtimeConnectionProvider);

    // Lắng nghe đơn mới từ QR hoặc quầy (chỉ tải lại danh sách ngầm)
    ref.listen<AsyncValue<NewQrOrderEvent>>(newQrOrderStreamProvider,
        (_, next) {
      next.whenOrNull(data: (e) => _refresh());
    });

    // Lắng nghe chuyển trạng thái đơn (chỉ báo khi chuyển sang nấu — cooking)
    ref.listen<AsyncValue<OrderStatusTransitionedEvent>>(
        orderStatusTransitionStreamProvider, (_, next) {
      next.whenOrNull(data: (e) {
        _refresh();
        if (e.toStatus == 'cooking') {
          FlutterRingtonePlayer().playNotification();
          _showBanner(
            '🛎 Bắt đầu nấu!',
            'Đơn hàng đã được chuyển sang chế độ nấu.',
            const Color(0xFFFF8C00),
          );
        }
      });
    });

    // Lắng nghe món trong đơn được cập nhật (chỉ báo khi có món được thêm mới)
    ref.listen<AsyncValue<OrderItemUpdatedEvent>>(
        orderItemUpdatedStreamProvider, (_, next) {
      next.whenOrNull(data: (e) {
        _refresh();
        if (e.isAdded) {
          FlutterRingtonePlayer().playNotification();
          _showBanner(
            '➕ Món mới!',
            'Đơn hàng vừa được bổ sung món mới.',
            const Color(0xFF28A745),
          );
        }
      });
    });

    final authState = ref.watch(authControllerProvider);
    final userName = authState is AuthAuthenticated
        ? authState.user.name
        : 'Nhân viên bếp';

    return Scaffold(
      backgroundColor: _K.bg,
      appBar: _buildAppBar(userName),
      body: const _OrderGrid(),
    );
  }

  AppBar _buildAppBar(String userName) {
    return AppBar(
      backgroundColor: _K.surface,
      elevation: 1,
      shadowColor: Colors.black12,
      title: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: _K.accent.withOpacity(0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.outdoor_grill, color: _K.accent, size: 20),
          ),
          const SizedBox(width: 8),
          const Flexible(
            child: Text(
              'BẾP · KDS',
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: _K.textDark,
                fontWeight: FontWeight.w800,
                fontSize: 18,
                letterSpacing: 1.2,
              ),
            ),
          ),
        ],
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.refresh, color: _K.textDark),
          tooltip: 'Tải lại',
          onPressed: _refresh,
        ),
        IconButton(
          icon: const Icon(Icons.logout, color: _K.textDark),
          tooltip: 'Đăng xuất',
          onPressed: () =>
              ref.read(authControllerProvider.notifier).logout(),
        ),
      ],
    );
  }

  void _showBanner(String title, String sub, Color color) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title,
              style: const TextStyle(
                  fontWeight: FontWeight.bold, color: Colors.white)),
          Text(sub,
              style: const TextStyle(color: Colors.white70, fontSize: 12)),
        ],
      ),
      backgroundColor: color,
      behavior: SnackBarBehavior.floating,
      duration: const Duration(seconds: 4),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }
}

// ─── Order Grid ──────────────────────────────────────────────────────────────
class _OrderGrid extends ConsumerWidget {
  const _OrderGrid();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordersAsync = ref.watch(kitchenOrdersProvider);

    return ordersAsync.when(
      loading: () => const Center(
        child: CircularProgressIndicator(color: _K.accent),
      ),
      error: (e, _) => _ErrorView(message: e.toString()),
      data: (orders) {
        if (orders.isEmpty) {
          return const _EmptyView();
        }

        final double width = MediaQuery.of(context).size.width;
        int crossAxisCount = 1;
        if (width > 1200) {
          crossAxisCount = 4;
        } else if (width > 800) {
          crossAxisCount = 3;
        } else if (width > 500) {
          crossAxisCount = 2;
        }

        final List<List<OrderEntity>> columns =
            List.generate(crossAxisCount, (_) => []);
        for (int i = 0; i < orders.length; i++) {
          columns[i % crossAxisCount].add(orders[i]);
        }

        return RefreshIndicator(
          color: _K.accent,
          onRefresh: () =>
              ref.read(kitchenOrdersProvider.notifier).refresh(),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: List.generate(crossAxisCount, (colIndex) {
                final colOrders = columns[colIndex];
                return Expanded(
                  child: Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: colIndex > 0 ? 6.0 : 0.0,
                    ),
                    child: Column(
                      children: colOrders.map((order) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12.0),
                          child: _OrderCard(order: order),
                        );
                      }).toList(),
                    ),
                  ),
                );
              }),
            ),
          ),
        );
      },
    );
  }
}

// ─── Order Card ──────────────────────────────────────────────────────────────
class _OrderCard extends ConsumerWidget {
  final OrderEntity order;
  const _OrderCard({required this.order});

  Color get _headerColor {
    switch (order.status.name) {
      case 'cooking':
        return _K.cookingBorder;
      case 'open':
        return _K.accent;
      default:
        return _K.textLight;
    }
  }

  String get _statusLabel {
    switch (order.status.name) {
      case 'open':
        return 'Mới vào';
      case 'cooking':
        return 'Đang nấu';
      case 'served':
        return 'Đã phục vụ';
      default:
        return order.statusLabel;
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final pendingItems =
        order.items.where((i) => i.status == 'pending').length;
    final cookingItems =
        order.items.where((i) => i.status == 'cooking').length;
    final allReady = order.items.isNotEmpty &&
        order.items
            .every((i) => i.status == 'ready' || i.status == 'served');

    return Card(
      elevation: 2,
      shadowColor: Colors.black12,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(color: _headerColor.withOpacity(0.35), width: 1.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // ── Header ──────────────────────────────────────────────────
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: _headerColor.withOpacity(0.1),
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(13)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        order.orderCode,
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 14,
                          color: _headerColor,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            order.serviceType == 'dine_in'
                                ? Icons.table_restaurant
                                : Icons.takeout_dining,
                            size: 12,
                            color: _K.textLight,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              order.serviceType == 'dine_in'
                                  ? 'Bàn ${order.tableId ?? "?"}'
                                  : 'Mang về',
                              style: const TextStyle(
                                  fontSize: 12, color: _K.textLight),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _headerColor.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _statusLabel,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: _headerColor,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ── Note ────────────────────────────────────────────────────
          if (order.note != null && order.note!.isNotEmpty)
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              color: const Color(0xFFFFF8E1),
              child: Row(
                children: [
                  const Icon(Icons.sticky_note_2_outlined,
                      size: 13, color: Color(0xFFB8860B)),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      order.note!,
                      style: const TextStyle(
                          fontSize: 12, color: Color(0xFF7D5900)),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),

          // ── Items ───────────────────────────────────────────────────
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(10),
            itemCount: order.items.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (ctx, i) => _ItemRow(
              orderId: order.id,
              item: order.items[i],
            ),
          ),

          // ── Footer counter + action ─────────────────────────────────
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              color: _K.bg,
              borderRadius:
                  const BorderRadius.vertical(bottom: Radius.circular(13)),
            ),
            child: Row(
              children: [
                // Counters
                _CountBadge(
                    count: pendingItems,
                    color: _K.pendingText,
                    label: 'Chờ'),
                const SizedBox(width: 6),
                _CountBadge(
                    count: cookingItems,
                    color: _K.cookingText,
                    label: 'Nấu'),
                const Spacer(),
                // "Xong đơn" button
                if (allReady)
                  _ActionButton(
                    label: 'Xong đơn',
                    icon: Icons.check_circle_outline,
                    color: _K.readyBorder,
                    onTap: () async {
                      try {
                        await ref
                            .read(kitchenOrdersProvider.notifier)
                            .updateOrderStatus(
                              orderId: order.id,
                              status: 'served',
                            );
                      } catch (e) {
                        // No context available in ConsumerWidget callback
                      }
                    },
                  )
                else if (order.status.name == 'open' && pendingItems > 0)
                  _ActionButton(
                    label: 'Bắt đầu nấu',
                    icon: Icons.outdoor_grill,
                    color: _K.accent,
                    onTap: () async {
                      try {
                        await ref
                            .read(kitchenOrdersProvider.notifier)
                            .updateOrderStatus(
                              orderId: order.id,
                              status: 'cooking',
                            );
                      } catch (_) {}
                    },
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Item Row ────────────────────────────────────────────────────────────────
class _ItemRow extends ConsumerStatefulWidget {
  final String orderId;
  final OrderItemEntity item;
  const _ItemRow({required this.orderId, required this.item});

  @override
  ConsumerState<_ItemRow> createState() => _ItemRowState();
}

class _ItemRowState extends ConsumerState<_ItemRow> {
  bool _loading = false;

  Future<void> _next() async {
    final nextStatus = _nextStatus(widget.item.status);
    if (nextStatus == null) return;
    setState(() => _loading = true);
    try {
      await ref.read(kitchenOrdersProvider.notifier).updateItemStatus(
            orderId: widget.orderId,
            itemId: widget.item.id,
            status: nextStatus,
          );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Lỗi: $e'),
          backgroundColor: Colors.red,
        ));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String? _nextStatus(String current) {
    switch (current) {
      case 'pending':
        return 'cooking';
      case 'cooking':
        return 'ready';
      default:
        return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.item.status;
    Color bgColor;
    Color textColor;
    Color? borderColor;
    IconData statusIcon;

    switch (s) {
      case 'cooking':
        bgColor = _K.cooking;
        textColor = _K.cookingText;
        borderColor = _K.cookingBorder;
        statusIcon = Icons.local_fire_department;
        break;
      case 'ready':
        bgColor = _K.ready;
        textColor = _K.readyText;
        borderColor = _K.readyBorder;
        statusIcon = Icons.check_circle;
        break;
      case 'served':
        bgColor = _K.served;
        textColor = _K.servedText;
        borderColor = null;
        statusIcon = Icons.done_all;
        break;
      case 'cancelled':
        bgColor = const Color(0xFFFFE0E0);
        textColor = Colors.red[700]!;
        borderColor = null;
        statusIcon = Icons.cancel;
        break;
      default: // pending
        bgColor = _K.pending;
        textColor = _K.pendingText;
        borderColor = null;
        statusIcon = Icons.hourglass_empty;
    }

    final canAdvance = s == 'pending' || s == 'cooking';

    return GestureDetector(
      onTap: canAdvance ? _next : null,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.symmetric(vertical: 3),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(8),
          border: borderColor != null
              ? Border.all(color: borderColor, width: 1.2)
              : null,
        ),
        child: Row(
          children: [
            // Qty badge
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: textColor.withOpacity(0.15),
                borderRadius: BorderRadius.circular(6),
              ),
              alignment: Alignment.center,
              child: Text(
                'x${widget.item.quantity}',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 12,
                  color: textColor,
                ),
              ),
            ),
            const SizedBox(width: 8),
            // Name + note
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.item.itemName ?? 'Món ăn',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: _K.textDark,
                      decoration: s == 'cancelled'
                          ? TextDecoration.lineThrough
                          : null,
                    ),
                  ),
                  if (widget.item.note != null &&
                      widget.item.note!.isNotEmpty)
                    Text(
                      '📝 ${widget.item.note}',
                      style: const TextStyle(
                          fontSize: 11, color: _K.textLight),
                    ),
                ],
              ),
            ),
            // Status button
            if (_loading)
              const SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(
                    strokeWidth: 2, color: _K.accent),
              )
            else
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: textColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Icon(
                  canAdvance ? Icons.touch_app : statusIcon,
                  size: 16,
                  color: textColor,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
class _CountBadge extends StatelessWidget {
  final int count;
  final Color color;
  final String label;
  const _CountBadge(
      {required this.count, required this.color, required this.label});

  @override
  Widget build(BuildContext context) {
    if (count == 0) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        '$count $label',
        style:
            TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _ActionButton(
      {required this.label,
      required this.icon,
      required this.color,
      required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 13, color: Colors.white),
            const SizedBox(width: 4),
            Text(label,
                style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.white)),
          ],
        ),
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  const _EmptyView();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: _K.accent.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(Icons.outdoor_grill,
                color: _K.accent, size: 40),
          ),
          const SizedBox(height: 16),
          const Text('Không có đơn nào',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: _K.textDark)),
          const SizedBox(height: 6),
          const Text(
            'Chưa có món nào đang nấu. Bếp đang rảnh! ☕',
            style: TextStyle(fontSize: 13, color: _K.textLight),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  const _ErrorView({required this.message});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off, size: 48, color: Colors.red),
            const SizedBox(height: 12),
            const Text('Không tải được đơn',
                style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: _K.textDark)),
            const SizedBox(height: 6),
            Text(message,
                textAlign: TextAlign.center,
                style:
                    const TextStyle(fontSize: 12, color: _K.textLight)),
          ],
        ),
      ),
    );
  }
}
