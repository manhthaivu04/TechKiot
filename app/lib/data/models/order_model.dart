import 'package:kiottayapp/core/enums/OrderSourceChannel.dart';
import 'package:kiottayapp/core/enums/OrderStatus.dart';
import 'package:kiottayapp/domain/entities/order_entity.dart';
import 'order_item_model.dart';
import 'payment_model.dart';

/// Data model for [OrderEntity].
/// Handles JSON deserialization from API responses.
class OrderModel extends OrderEntity {
  const OrderModel({
    required super.id,
    required super.orderCode,
    required super.sourceChannel,
    super.serviceType,
    required super.status,
    required super.statusLabel,
    super.tableId,
    super.customerName,
    super.customerPhone,
    super.customerReference,
    super.guestCount,
    required super.subtotalAmount,
    required super.discountAmount,
    required super.taxAmount,
    required super.finalAmount,
    super.note,
    super.items = const [],
    super.payments = const [],
    super.createdAt,
    super.updatedAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id'] as String,
      orderCode: json['order_code'] as String? ?? '',
      sourceChannel: OrderSourceChannel.fromString(
        json['source_channel'] as String? ?? '',
      ),
      serviceType: json['service_type'] as String?,
      status: OrderStatus.fromString(json['status'] as String? ?? ''),
      statusLabel: json['status_label'] as String? ?? '',
      tableId: json['table_id'] as String?,
      customerName: json['customer_name'] as String?,
      customerPhone: json['customer_phone'] as String?,
      customerReference: json['customer_reference'] as String?,
      guestCount: json['guest_count'] as int?,
      subtotalAmount: double.tryParse(json['subtotal_amount']?.toString() ?? '') ?? 0.0,
      discountAmount: double.tryParse(json['discount_amount']?.toString() ?? '') ?? 0.0,
      taxAmount: double.tryParse(json['tax_amount']?.toString() ?? '') ?? 0.0,
      finalAmount: double.tryParse(json['final_amount']?.toString() ?? '') ?? 0.0,
      note: json['note'] as String?,
      items: (json['items'] as List<dynamic>? ?? [])
          .map((e) => OrderItemModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      payments: (json['payments'] as List<dynamic>? ?? [])
          .map((e) => PaymentModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'] as String)
          : null,
    );
  }
}
