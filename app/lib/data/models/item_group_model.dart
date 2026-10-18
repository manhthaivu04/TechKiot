import '../../domain/entities/item_group_entity.dart';

/// Data model for [ItemGroupEntity].
/// Extends the entity and adds JSON serialization.
class ItemGroupModel extends ItemGroupEntity {
  const ItemGroupModel({
    required super.id,
    required super.name,
    super.displayOrder = 0,
    required super.isActive,
  });

  factory ItemGroupModel.fromJson(Map<String, dynamic> json) {
    return ItemGroupModel(
      id: json['id'] as String,
      name: json['name'] as String,
      displayOrder: json['display_order'] as int? ?? 0,
      isActive: json['is_active'] as bool? ?? true,
    );
  }
}
