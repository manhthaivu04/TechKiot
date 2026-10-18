import 'package:flutter/material.dart';
import '../../../../core/enums/TableStatus.dart';
import '../../../../domain/entities/order_entity.dart';
import '../../../../domain/entities/restaurant_table_entity.dart';
import 'ordering_design_tokens.dart';

class TableCard extends StatefulWidget {
  final RestaurantTableEntity? table; // null = takeaway
  final OrderEntity? order;
  final VoidCallback onTap;
  final bool hasNotification;

  const TableCard({
    super.key,
    required this.table,
    required this.order,
    required this.onTap,
    this.hasNotification = false,
  });

  @override
  State<TableCard> createState() => _TableCardState();
}

class _TableCardState extends State<TableCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _pulseCtrl;
  late Animation<double> _pulseAnim;

  bool get _isTakeaway => widget.table == null;
  bool get _isOccupied =>
      !_isTakeaway &&
      (widget.table!.status == TableStatus.occupied || widget.order != null);

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.5, end: 1.0).animate(
      CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: widget.onTap,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            decoration: BoxDecoration(
              color: _isOccupied
                  ? OrderingDesignTokens.cardOccupied
                  : OrderingDesignTokens.cardEmpty,
              borderRadius: BorderRadius.circular(14),
              border: widget.hasNotification
                  ? Border.all(color: Colors.orange.shade400, width: 2)
                  : null,
            ),
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      _isTakeaway
                          ? Icons.shopping_bag_outlined
                          : Icons.wifi_tethering,
                      color: _isOccupied
                          ? Colors.white
                          : OrderingDesignTokens.accent,
                      size: 18,
                    ),
                    const Spacer(),
                  ],
                ),
                const Spacer(),
                Text(
                  _isTakeaway ? 'Mang đi' : widget.table!.name,
                  style: TextStyle(
                    color: _isOccupied ? Colors.white : OrderingDesignTokens.accent,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
                if (_isOccupied && widget.order != null) ...[
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.receipt_outlined, color: Colors.white, size: 11),
                        const SizedBox(width: 4),
                        Flexible(
                          child: Text(
                            '${_formatCurrency(_displayAmount(widget.order!))} đ',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),

          // ── Notification badge ──────────────────────────────────────────
          if (widget.hasNotification)
            Positioned(
              top: -5,
              right: -5,
              child: AnimatedBuilder(
                animation: _pulseAnim,
                builder: (_, __) => Opacity(
                  opacity: _pulseAnim.value,
                  child: Container(
                    width: 14,
                    height: 14,
                    decoration: const BoxDecoration(
                      color: Colors.orange,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.notifications, color: Colors.white, size: 8),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  double _displayAmount(OrderEntity order) {
    return order.finalAmount > 0 ? order.finalAmount : order.subtotalAmount;
  }

  String _formatCurrency(double amount) {
    return amount.toInt().toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]}.',
    );
  }
}
