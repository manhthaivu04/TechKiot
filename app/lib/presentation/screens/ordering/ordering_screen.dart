import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../widgets/shared_bottom_nav.dart';
import '../../../presentation/providers/ordering_providers.dart';
import '../../../presentation/providers/auth_controller.dart';
import '../../../presentation/providers/auth_state.dart';
import 'widgets/area_filter_bar.dart';
import 'widgets/ordering_design_tokens.dart';
import 'widgets/status_filter_bar.dart';
import 'widgets/table_grid.dart';
import '../../../core/services/realtime_service.dart';
import '../../providers/realtime_provider.dart';
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart';

class OrderingScreen extends ConsumerStatefulWidget {
  const OrderingScreen({super.key});

  @override
  ConsumerState<OrderingScreen> createState() => _OrderingScreenState();
}

class _OrderingScreenState extends ConsumerState<OrderingScreen>
    with WidgetsBindingObserver {
  final _scaffoldKey = GlobalKey<ScaffoldState>();
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Called when the app returns to foreground
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refresh();
    }
  }

  void _refresh() {
    ref.invalidate(restaurantTablesProvider);
    ref.invalidate(openOrdersProvider);
  }

  @override
  Widget build(BuildContext context) {
    // Watch connection provider to trigger and maintain connection lifecycle
    ref.watch(realtimeConnectionProvider);

    // Listen to new QR order events
    ref.listen<AsyncValue<NewQrOrderEvent>>(newQrOrderStreamProvider, (
      previous,
      next,
    ) {
      next.whenOrNull(
        data: (event) {
          // 1. Refresh tables and orders
          _refresh();
          // 2. Show in-app banner
          _showNewOrderBanner(event);
        },
      );
    });

    // Listen to order status transitions
    ref.listen<AsyncValue<OrderStatusTransitionedEvent>>(
      orderStatusTransitionStreamProvider,
      (previous, next) {
        next.whenOrNull(
          data: (event) {
            _refresh();
            // Mark table as having notification
            if (event.tableId != null) {
              final notifier = ref.read(notificationTableIdsProvider.notifier);
              notifier.state = {...notifier.state, event.tableId!};
            }
            if (event.toStatus == 'served') {
              _showKitchenUpdateBanner(
                'Món đã xong',
                'Đơn ${event.orderCode} đã hoàn tất (Served)',
                Icons.restaurant,
              );
            }
          },
        );
      },
    );

    // Listen to order item updates
    ref.listen<AsyncValue<OrderItemUpdatedEvent>>(
      orderItemUpdatedStreamProvider,
      (previous, next) {
        next.whenOrNull(
          data: (event) {
            _refresh();
            // Mark table as having notification
            if (event.tableId != null) {
              final notifier = ref.read(notificationTableIdsProvider.notifier);
              notifier.state = {...notifier.state, event.tableId!};
            }
            if (!event.isAdded && event.itemStatus == 'ready') {
              _showKitchenUpdateBanner(
                'Món đã sẵn sàng',
                'Nhà bếp đã chuẩn bị xong món cho đơn ${event.orderCode}',
                Icons.check_circle_outline,
              );
            }
          },
        );
      },
    );

    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: OrderingDesignTokens.bg,
      appBar: _buildAppBar(context),
      drawer: _buildProfileDrawer(context),
      body: Column(
        children: [
          LayoutBuilder(
            builder: (context, constraints) {
              if (constraints.maxWidth > 600) {
                return Row(
                  children: [
                    Expanded(child: AreaFilterBar(ref: ref)),
                    SizedBox(
                      width: 320,
                      child: StatusFilterBar(ref: ref),
                    ),
                  ],
                );
              } else {
                return Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AreaFilterBar(ref: ref),
                    StatusFilterBar(ref: ref),
                  ],
                );
              }
            },
          ),
          const Expanded(child: TableGrid()),
        ],
      ),
      //bottomNavigationBar: const SharedBottomNav(),
    );
  }

  void _showNewOrderBanner(NewQrOrderEvent event) {
    if (!mounted) return;

    // Play notification sound
    FlutterRingtonePlayer().playNotification();

    ScaffoldMessenger.of(context).clearSnackBars();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.qr_code_scanner, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    '🛎  Đơn QR mới!',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    'Mã đơn: ${event.orderCode}',
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF1A8754), // Xanh lá đậm
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 5),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
        action: SnackBarAction(
          label: 'Xem',
          textColor: Colors.white,
          onPressed: () {
            // Xem đơn hoặc load lại
            _refresh();
          },
        ),
      ),
    );
  }

  void _showKitchenUpdateBanner(String title, String message, IconData icon) {
    if (!mounted) return;

    // Play notification sound
    FlutterRingtonePlayer().playNotification();

    ScaffoldMessenger.of(context).clearSnackBars();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(icon, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    message,
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFFE67E22), // Màu cam
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 4),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  AppBar _buildAppBar(BuildContext context) {
    return AppBar(
      backgroundColor: OrderingDesignTokens.surface,
      elevation: 1,
      shadowColor: Colors.black12,
      leadingWidth: 40,
      leading: Padding(
        padding: const EdgeInsets.only(left: 12),
        child: IconButton(
          padding: EdgeInsets.zero,
          icon: const Icon(Icons.menu, color: OrderingDesignTokens.textDark, size: 22),
          onPressed: () => _scaffoldKey.currentState?.openDrawer(),
        ),
      ),
      title: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: OrderingDesignTokens.accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(
              Icons.restaurant_menu,
              color: OrderingDesignTokens.accent,
              size: 20,
            ),
          ),
          const SizedBox(width: 8),
          const Flexible(
            child: Text(
              'KIOTTAY',
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: OrderingDesignTokens.textDark,
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
          icon: const Icon(Icons.search, color: OrderingDesignTokens.textDark),
          onPressed: () {},
        ),
        IconButton(
          icon: const Icon(
            Icons.notifications_outlined,
            color: OrderingDesignTokens.textDark,
          ),
          onPressed: () {},
        ),
      ],
    );
  }

  Widget _buildProfileDrawer(BuildContext context) {
    final authState = ref.watch(authControllerProvider);
    final user = authState is AuthAuthenticated ? authState.user : null;

    return Drawer(
      width: 260,
      backgroundColor: Colors.white,
      child: SafeArea(
        child: Column(
          children: [
            // ── Header profile ──────────────────────────────────────────
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
              decoration: BoxDecoration(
                color: OrderingDesignTokens.accent.withValues(alpha: 0.06),
                border: Border(
                  bottom: BorderSide(color: Colors.grey.shade200),
                ),
              ),
              child: Row(
                children: [
                  // Avatar
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: OrderingDesignTokens.accent.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.person,
                      size: 24,
                      color: OrderingDesignTokens.accent,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?.name ?? '—',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: OrderingDesignTokens.textDark,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          user?.email ?? '',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade500,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: OrderingDesignTokens.accent.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            user?.roleLabel.toUpperCase() ?? '',
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: OrderingDesignTokens.accent,
                              letterSpacing: 0.4,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const Spacer(),

            // ── Logout ──────────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
              child: ListTile(
                dense: true,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                  side: BorderSide(color: Colors.red.shade100),
                ),
                tileColor: Colors.red.shade50,
                leading: const Icon(Icons.logout, color: Colors.red, size: 20),
                title: const Text(
                  'Đăng xuất',
                  style: TextStyle(
                    color: Colors.red,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
                onTap: () async {
                  Navigator.pop(context);
                  await ref.read(authControllerProvider.notifier).logout();
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
