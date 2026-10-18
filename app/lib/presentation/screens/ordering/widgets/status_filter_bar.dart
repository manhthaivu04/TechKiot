import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/ordering_providers.dart';
import 'ordering_design_tokens.dart';

class StatusFilterBar extends ConsumerWidget {
  const StatusFilterBar({super.key, required this.ref});
  
  final WidgetRef ref;

  static const _tabs = [
    ('all', 'Tất cả'),
    ('occupied', 'Sử dụng'),
    ('available', 'Còn trống'),
  ];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selected = ref.watch(tableStatusFilterProvider);
    return Container(
      color: OrderingDesignTokens.surface,
      child: Row(
        children: _tabs.map((tab) {
          final isActive = selected == tab.$1;
          return Expanded(
            child: GestureDetector(
              onTap: () =>
                  ref.read(tableStatusFilterProvider.notifier).state = tab.$1,
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  border: Border(
                    bottom: BorderSide(
                      color: isActive ? OrderingDesignTokens.accent : Colors.transparent,
                      width: 2.5,
                    ),
                  ),
                ),
                alignment: Alignment.center,
                child: Text(
                  tab.$2,
                  style: TextStyle(
                    color: isActive ? OrderingDesignTokens.accent : OrderingDesignTokens.textLight,
                    fontWeight:
                        isActive ? FontWeight.w700 : FontWeight.w500,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}
