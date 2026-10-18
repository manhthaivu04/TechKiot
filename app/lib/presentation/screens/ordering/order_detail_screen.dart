import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:kiottayapp/core/enums/OrderStatus.dart';
import 'package:kiottayapp/core/enums/OrderSourceChannel.dart';
import 'package:kiottayapp/domain/entities/order_entity.dart';
import 'package:kiottayapp/domain/entities/restaurant_table_entity.dart';
import 'package:kiottayapp/presentation/providers/dependency_providers.dart';
import 'package:kiottayapp/presentation/providers/ordering_providers.dart';
import 'package:kiottayapp/presentation/providers/realtime_provider.dart';
import 'package:kiottayapp/core/services/realtime_service.dart';
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart';
import 'widgets/ordering_design_tokens.dart';
import 'widgets/menu_selection_sheet.dart';
import 'payment_screen.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:kiottayapp/core/constants/app_constants.dart';

class OrderDetailScreen extends ConsumerStatefulWidget {
  final OrderEntity order;
  final RestaurantTableEntity table;

  const OrderDetailScreen({
    super.key,
    required this.order,
    required this.table,
  });

  @override
  ConsumerState<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends ConsumerState<OrderDetailScreen> {
  late OrderEntity _currentOrder;
  bool _isNotifyingKitchen = false;

  @override
  void initState() {
    super.initState();
    _currentOrder = widget.order;
  }

  void _refreshOrders() {
    ref.invalidate(openOrdersProvider);
  }

  Future<void> _notifyKitchen() async {
    if (_isNotifyingKitchen) return;
    setState(() => _isNotifyingKitchen = true);
    try {
      await ref
          .read(updateOrderStatusUseCaseProvider)
          .call(orderId: _currentOrder.id, status: 'cooking');
      
      _refreshOrders();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Đã báo bếp thành công!'),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Lỗi báo bếp: $e'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isNotifyingKitchen = false);
      }
    }
  }

  void _openMenu() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) =>
          MenuSelectionSheet(table: widget.table, existingOrder: _currentOrder),
    ).then((_) {
      // Reload order when menu closes
      _refreshOrders();
      if (mounted) {
        Navigator.pop(context); // pop to go back to grid to see new state
      }
    });
  }

  void _openPayment() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PaymentScreen(order: _currentOrder),
      ),
    ).then((_) => _refreshOrders());
  }

  @override
  Widget build(BuildContext context) {
    // Watch openOrdersProvider to rebuild in real-time when orders change
    final openOrdersAsync = ref.watch(openOrdersProvider);

    // Listen to WebSocket events directly to ensure refresh when screen is active
    ref.listen<AsyncValue<OrderStatusTransitionedEvent>>(orderStatusTransitionStreamProvider, (previous, next) {
      next.whenOrNull(data: (event) {
        if (event.orderId == widget.order.id) {
          _refreshOrders();
        }
      });
    });

    ref.listen<AsyncValue<OrderItemUpdatedEvent>>(orderItemUpdatedStreamProvider, (previous, next) {
      next.whenOrNull(data: (event) {
        if (event.orderId == widget.order.id) {
          _refreshOrders();
        }
      });
    });

    // Listen to status changes to show notification
    ref.listen(openOrdersProvider, (previous, next) {
      if (previous == null || previous.value == null || next.value == null) return;
      
      OrderEntity? prevOrder;
      try { prevOrder = previous.value!.firstWhere((o) => o.id == widget.order.id); } catch (_) {}
      
      OrderEntity? nextOrder;
      try { nextOrder = next.value!.firstWhere((o) => o.id == widget.order.id); } catch (_) {}

      if (prevOrder != null && nextOrder != null) {
        if (prevOrder.status != nextOrder.status) {
          if (nextOrder.status == OrderStatus.served || nextOrder.status == OrderStatus.cooking) {
            
            // Play notification sound
            FlutterRingtonePlayer().playNotification();

            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Row(
                  children: [
                    const Icon(Icons.notifications_active, color: Colors.white),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Trạng thái đơn: ${nextOrder.statusLabel}',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                backgroundColor: nextOrder.status == OrderStatus.served 
                    ? Colors.green 
                    : Colors.orange,
                behavior: SnackBarBehavior.floating,
                margin: const EdgeInsets.only(bottom: 80, left: 16, right: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            );
          }
        }
      }
    });

    final currentOrder = openOrdersAsync.whenOrNull(
      data: (orders) {
        try {
          return orders.firstWhere((o) => o.id == widget.order.id);
        } catch (_) {
          return null;
        }
      },
    ) ?? _currentOrder;

    _currentOrder = currentOrder;
    
    // Only allow payment when order status is served
    final bool canPay = _currentOrder.status == OrderStatus.served;

    final bool isQrOrder = _currentOrder.sourceChannel == OrderSourceChannel.qrStatic ||
        _currentOrder.sourceChannel == OrderSourceChannel.qrTable;
    final bool canNotifyKitchen = isQrOrder && _currentOrder.status == OrderStatus.open;

    Widget mainContent = Scaffold(
      backgroundColor: const Color(0xFFF9F9F9),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios,
            color: OrderingDesignTokens.accent,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          children: [
            Text(
              widget.table.name,
              style: const TextStyle(
                color: Colors.black87,
                fontWeight: FontWeight.bold,
                fontSize: 18,
              ),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  '${_currentOrder.items.length} món',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: _getStatusColor(_currentOrder.status).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    _currentOrder.statusLabel.toUpperCase(),
                    style: TextStyle(
                      color: _getStatusColor(_currentOrder.status),
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(
              Icons.more_vert,
              color: Colors.black54,
            ),
            onPressed: () {},
          ),
        ],
      ),
      body: ListView.builder(
        padding: const EdgeInsets.only(bottom: 24, top: 16),
        itemCount: _currentOrder.items.length + 1,
        itemBuilder: (context, index) {
          if (index == _currentOrder.items.length) {
            // Nút gọi thêm món ở cuối danh sách
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
              child: ElevatedButton.icon(
                onPressed: _openMenu,
                icon: const Icon(Icons.add_circle_outline, size: 22),
                label: const Text(
                  'GỌI THÊM MÓN',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: OrderingDesignTokens.accent,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: const BorderSide(color: OrderingDesignTokens.accent, width: 1.5),
                  ),
                ),
              ),
            );
          }

          final item = _currentOrder.items[index];
          return Container(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: item.itemImage != null && item.itemImage!.isNotEmpty
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: CachedNetworkImage(
                            imageUrl: item.itemImage!,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => const Center(
                              child: SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              ),
                            ),
                            errorWidget: (context, url, error) => Icon(Icons.restaurant_menu, color: Colors.grey.shade400, size: 28),
                          ),
                        )
                      : Icon(Icons.restaurant_menu, color: Colors.grey.shade400, size: 28),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.itemName ?? 'Món',
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Wrap(
                        crossAxisAlignment: WrapCrossAlignment.center,
                        spacing: 8,
                        runSpacing: 4,
                        children: [
                          Text(
                            _formatCurrency(item.unitPrice),
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.w500,
                              fontSize: 14,
                            ),
                          ),
                          _buildItemStatusBadge(item.status),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: OrderingDesignTokens.accent.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    'x${item.quantity}',
                    style: const TextStyle(
                      color: OrderingDesignTokens.accent,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 20,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Expanded(
                    child: Text(
                      'Tổng tiền',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                        color: Colors.black54,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    _formatCurrency(_currentOrder.finalAmount > 0
                        ? _currentOrder.finalAmount
                        : _currentOrder.subtotalAmount),
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 22,
                      color: OrderingDesignTokens.accent,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    flex: 1,
                    child: OutlinedButton(
                      onPressed: canNotifyKitchen ? _notifyKitchen : null,
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: BorderSide(
                          color: canNotifyKitchen
                              ? OrderingDesignTokens.accent
                              : Colors.grey.shade300,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                        foregroundColor: OrderingDesignTokens.accent,
                        disabledForegroundColor: Colors.grey.shade400,
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.restaurant,
                            size: 20,
                            color: canNotifyKitchen
                                ? OrderingDesignTokens.accent
                                : Colors.grey.shade400,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Báo bếp',
                            style: TextStyle(
                              color: canNotifyKitchen
                                  ? OrderingDesignTokens.accent
                                  : Colors.grey.shade500,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      onPressed: canPay ? _openPayment : null,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: OrderingDesignTokens.accent,
                        foregroundColor: Colors.white,
                        disabledBackgroundColor: Colors.grey.shade300,
                        disabledForegroundColor: Colors.grey.shade500,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: const Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.payment, size: 20),
                          SizedBox(height: 4),
                          Text(
                            'Thanh toán',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );

    final screenW = MediaQuery.of(context).size.width;
    if (screenW > 600) {
      return Material(
        color: const Color(0xFFEEEEEE),
        child: Center(
          child: Container(
            width: 600,
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.1),
                  blurRadius: 20,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: mainContent,
          ),
        ),
      );
    }

    return mainContent;
  }

  String _formatCurrency(double amount) {
    final intPart = amount.toInt();
    return '${intPart.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')} Đ';
  }

  Color _getStatusColor(OrderStatus status) {
    switch (status) {
      case OrderStatus.open:
        return Colors.blue;
      case OrderStatus.cooking:
        return Colors.orange;
      case OrderStatus.served:
        return Colors.green;
      case OrderStatus.paid:
        return Colors.teal;
      case OrderStatus.cancelled:
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Widget _buildItemStatusBadge(String status) {
    Color bgColor;
    Color textColor;
    String text;

    switch (status) {
      case 'cooking':
        bgColor = Colors.orange.shade100;
        textColor = Colors.orange.shade800;
        text = 'Đang nấu';
        break;
      case 'ready':
        bgColor = Colors.green.shade100;
        textColor = Colors.green.shade800;
        text = 'Lên món';
        break;
      case 'served':
        bgColor = Colors.blue.shade100;
        textColor = Colors.blue.shade800;
        text = 'Đã phục vụ';
        break;
      case 'cancelled':
        bgColor = Colors.red.shade100;
        textColor = Colors.red.shade800;
        text = 'Đã huỷ';
        break;
      default: // pending
        bgColor = Colors.grey.shade200;
        textColor = Colors.grey.shade700;
        text = 'Chờ xử lý';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: textColor,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}
