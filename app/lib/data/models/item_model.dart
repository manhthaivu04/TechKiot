import '../../domain/entities/item_entity.dart';
import 'item_group_model.dart';

/// Data model for [ItemEntity].
/// Extends the entity and adds JSON serialization.
class ItemModel extends ItemEntity {
  const ItemModel({
    required super.id,
    required super.name,
    super.itemGroupId,
    required super.salePrice,
    super.imageUrl,
    super.unit,
    required super.isActive,
    required super.availabilityStatus,
    super.itemGroup,
  });

  factory ItemModel.fromJson(Map<String, dynamic> json) {
    return ItemModel(
      id: json['id'] as String,
      name: json['name'] as String,
      itemGroupId: json['item_group_id'] as String?,
      salePrice: double.tryParse(json['sale_price'].toString()) ?? 0.0,
      imageUrl: json['image_url'] as String?,
      unit: json['unit'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      availabilityStatus: json['availability_status'] as String? ?? 'in_stock',
      itemGroup: json['item_group'] != null
          ? ItemGroupModel.fromJson(json['item_group'] as Map<String, dynamic>)
          : null,
    );
  }
}
