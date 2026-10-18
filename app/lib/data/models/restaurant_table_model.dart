import 'package:kiottayapp/core/enums/TableStatus.dart';
import 'package:kiottayapp/domain/entities/restaurant_table_entity.dart';
import 'table_area_model.dart';

/// Data model for [RestaurantTableEntity].
/// Extends the entity and adds JSON serialization.
class RestaurantTableModel extends RestaurantTableEntity {
  const RestaurantTableModel({
    required super.id,
    required super.restaurantId,
    super.areaId,
    super.area,
    super.uid,
    required super.name,
    super.capacity = 4,
    required super.status,
    required super.statusLabel,
    super.qrToken,
    super.createdAt,
    super.updatedAt,
  });

  factory RestaurantTableModel.fromJson(Map<String, dynamic> json) {
    return RestaurantTableModel(
      id: json['id'] as String,
      restaurantId: json['restaurant_id'] as String? ?? '',
      areaId: json['area_id'] as String?,
      area: json['area'] != null
          ? TableAreaModel.fromJson(json['area'] as Map<String, dynamic>)
          : null,
      uid: json['uid'] as String?,
      name: json['name'] as String,
      capacity: json['capacity'] as int? ?? 4,
      status: TableStatus.fromString(json['status'] as String? ?? 'available'),
      statusLabel: json['status_label'] as String? ?? '',
      qrToken: json['qr_token'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'] as String)
          : null,
    );
  }
}
