import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:kiottayapp/domain/entities/order_entity.dart';
import 'package:kiottayapp/domain/entities/payment_method_setting_entity.dart';
import 'package:kiottayapp/presentation/providers/dependency_providers.dart';
import 'package:kiottayapp/presentation/providers/ordering_providers.dart';
import 'package:cached_network_image/cached_network_image.dart';

import 'widgets/ordering_design_tokens.dart';

class PaymentScreen extends ConsumerStatefulWidget {
  final OrderEntity order;

  const PaymentScreen({super.key, required this.order});

  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen> {
  String? _selectedMethod;
  bool _isSubmitting = false;

  Future<void> _submitPayment() async {
    if (_selectedMethod == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Vui lòng chọn phương thức thanh toán'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final String? referenceNo = _selectedMethod == 'transfer'
          ? 'TXN${DateTime.now().millisecondsSinceEpoch}'
          : null;

      await ref
          .read(recordPaymentUseCaseProvider)
          .call(
            orderId: widget.order.id,
            amount: widget.order.finalAmount > 0
                ? widget.order.finalAmount
                : widget.order.subtotalAmount,
            paymentMethod: _selectedMethod!,
            referenceNo: referenceNo,
          );

      // Update order status to paid
      try {
        await ref
            .read(updateOrderStatusUseCaseProvider)
            .call(orderId: widget.order.id, status: 'paid');
      } catch (e) {
        // Có thể backend đã tự động chuyển sang paid khi đủ tiền, nên ta bỏ qua lỗi này
        debugPrint('Update status to paid warning: $e');
      }

      ref.invalidate(openOrdersProvider);
      ref.invalidate(restaurantTablesProvider);

      if (mounted) {
        Navigator.popUntil(context, (route) => route.isFirst);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Thanh toán thành công!'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Lỗi thanh toán: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final paymentMethodsAsync = ref.watch(paymentMethodsProvider);
    final finalAmount = widget.order.finalAmount > 0
        ? widget.order.finalAmount
        : widget.order.subtotalAmount;

    return Scaffold(
      backgroundColor: Colors.white,
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
        title: const Text(
          'Thanh toán',
          style: TextStyle(
            color: OrderingDesignTokens.accent,
            fontWeight: FontWeight.bold,
            fontSize: 20,
          ),
        ),
        centerTitle: true,
      ),
      body: paymentMethodsAsync.when(
        data: (methods) {
          final activeMethods = methods.where((m) => m.isActive).toList();

          if (_selectedMethod == null && activeMethods.isNotEmpty) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted)
                setState(
                  () =>
                      _selectedMethod = activeMethods.first.paymentMethod.name,
                );
            });
          }

          // Group items by itemId
          final Map<String, Map<String, dynamic>> groupedItems = {};
          for (var item in widget.order.items) {
            final key = item.itemId;
            if (groupedItems.containsKey(key)) {
              groupedItems[key]!['quantity'] += item.quantity;
            } else {
              groupedItems[key] = {
                'name': item.itemName ?? 'Món',
                'quantity': item.quantity,
                'unitPrice': item.unitPrice,
              };
            }
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Chi tiết đơn hàng',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: Colors.black,
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      ...groupedItems.values.map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 8.0),
                          child: Row(
                            spacing: 10,
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Text(
                                  '${item['quantity']}x ${item['name']}',
                                  style: const TextStyle(
                                    fontSize: 15,
                                    color: Colors.black,
                                  ),
                                ),
                              ),

                              Text(
                                _formatCurrency(item['unitPrice'] * item['quantity']),
                                style: const TextStyle(
                                  fontSize: 15,
                                  color: Colors.grey,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const Divider(height: 24),
                      _buildSummaryRow(
                        'Tổng tiền',
                        widget.order.subtotalAmount,
                      ),
                      if (widget.order.discountAmount > 0) ...[
                        const SizedBox(height: 8),
                        _buildSummaryRow(
                          'Giảm giá',
                          widget.order.discountAmount,
                        ),
                      ],
                      if (widget.order.taxAmount > 0) ...[
                        const SizedBox(height: 8),
                        _buildSummaryRow('Thuế', widget.order.taxAmount),
                      ],
                      const SizedBox(height: 12),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Expanded(
                            child: Text(
                              'Khách cần trả',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                                color: OrderingDesignTokens.accent,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            _formatCurrency(finalAmount),
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 20,
                              color: OrderingDesignTokens.accent,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 32),
                const Text(
                  'Hình thức thanh toán',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
                const SizedBox(height: 12),
                if (activeMethods.isEmpty)
                  const Text(
                    'Không có phương thức thanh toán nào khả dụng.',
                    style: TextStyle(color: Colors.red),
                  )
                else
                  ...activeMethods.map(
                    (method) => Padding(
                      padding: const EdgeInsets.only(bottom: 12.0),
                      child: _buildPaymentMethod(method),
                    ),
                  ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, stack) =>
            Center(child: Text('Lỗi tải phương thức thanh toán: $err')),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isSubmitting ? null : _submitPayment,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                backgroundColor: OrderingDesignTokens.accent,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : const Text(
                      'Xác nhận',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, double amount) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 16, color: Colors.black54),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          _formatCurrency(amount),
          style: const TextStyle(
            fontSize: 16,
            color: Colors.black87,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildPaymentMethod(PaymentMethodSettingEntity method) {
    final isSelected = _selectedMethod == method.paymentMethod.name;

    return GestureDetector(
      onTap: () {
        setState(() => _selectedMethod = method.paymentMethod.name);
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected
              ? OrderingDesignTokens.accent.withValues(alpha: 0.05)
              : Colors.white,
          border: Border.all(
            color: isSelected
                ? OrderingDesignTokens.accent
                : Colors.grey.shade300,
            width: isSelected ? 2 : 1,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Icon(
                  isSelected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_unchecked,
                  color: isSelected
                      ? OrderingDesignTokens.accent
                      : Colors.grey.shade400,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    method.displayName ?? method.methodLabel,
                    style: TextStyle(
                      fontWeight: isSelected
                          ? FontWeight.bold
                          : FontWeight.w500,
                      fontSize: 16,
                      color: isSelected
                          ? OrderingDesignTokens.accent
                          : Colors.black87,
                    ),
                  ),
                ),
              ],
            ),
            if (isSelected &&
                method.qrCodeUrl != null &&
                method.qrCodeUrl!.isNotEmpty) ...[
              const SizedBox(height: 16),
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: CachedNetworkImage(
                  imageUrl: method.qrCodeUrl!,
                  width: 200,
                  fit: BoxFit.contain,
                  placeholder: (context, url) => const SizedBox(
                    height: 200,
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  errorWidget: (context, url, error) => const SizedBox(
                    height: 200,
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.error_outline,
                            color: Colors.red,
                            size: 40,
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Lỗi tải mã QR',
                            style: TextStyle(color: Colors.red),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Quét mã QR để thanh toán',
                style: TextStyle(color: Colors.black54, fontSize: 13),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _formatCurrency(double amount) {
    final intPart = amount.toInt();
    return '${intPart.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')} Đ';
  }
}
