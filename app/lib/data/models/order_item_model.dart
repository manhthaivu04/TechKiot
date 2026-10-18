import 'package:kiottayapp/domain/entities/order_item_entity.dart';

/// Data model for [OrderItemEntity].
class OrderItemModel extends OrderItemEntity {
  const OrderItemModel({
    required super.id,
    required super.itemId,
    super.itemName,
    super.itemImage,
    required super.quantity,
    required super.unitPrice,
    required super.lineTotal,
    required super.status,
    super.note,
    super.createdAt,
  });

  factory OrderItemModel.fromJson(Map<String, dynamic> json) {
    return OrderItemModel(
      id: json['id'] as String,
      itemId: json['item_id'] as String,
      itemName: json['item_name'] as String?,
      itemImage: json['item_image'] as String? ?? (json['item'] != null ? json['item']['image_url'] as String? : null),
      quantity: json['quantity'] as int,
      unitPrice: double.tryParse(json['unit_price']?.toString() ?? '') ?? 0.0,
      lineTotal: double.tryParse(json['line_total']?.toString() ?? '') ?? 0.0,
      status: json['status'] as String? ?? 'pending',
      note: json['note'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }
}
