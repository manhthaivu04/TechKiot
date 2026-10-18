import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/ordering_providers.dart';
import 'ordering_design_tokens.dart';

class AreaFilterBar extends ConsumerWidget {
  const AreaFilterBar({super.key, required this.ref});

  final WidgetRef ref;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final areasAsync = ref.watch(tableAreasProvider);
    final selectedId = ref.watch(selectedAreaIdProvider);

    return areasAsync.when(
      loading: () => const SizedBox(
        height: 34,
        child: Center(child: LinearProgressIndicator()),
      ),
      error: (e, _) => const SizedBox.shrink(),
      data: (areas) {
        // Ẩn nếu không có khu vực nào
        if (areas.isEmpty) return const SizedBox.shrink();

        // Tự động chọn khu vực đầu tiên nếu chưa có gì được chọn
        if (selectedId == null) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            ref.read(selectedAreaIdProvider.notifier).state = areas.first.id;
          });
        }

        return Container(
          color: OrderingDesignTokens.bg,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: areas.map((area) {
                final isSelected = selectedId == area.id;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: GestureDetector(
                    onTap: () {
                      // Chỉ cho phép chuyển sang area khác, không bỏ chọn
                      if (!isSelected) {
                        ref.read(selectedAreaIdProvider.notifier).state = area.id;
                      }
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 20,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? OrderingDesignTokens.accent
                            : const Color(0xFFE0E0E0),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        area.name,
                        style: TextStyle(
                          color: isSelected
                              ? Colors.white
                              : OrderingDesignTokens.textDark,
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        );
      },
    );
  }
}
