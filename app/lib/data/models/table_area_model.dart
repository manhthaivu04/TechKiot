import 'package:kiottayapp/domain/entities/table_area_entity.dart';

class TableAreaModel extends TableAreaEntity {
  const TableAreaModel({
    required super.id,
    required super.restaurantId,
    required super.name,
    super.description,
    super.displayOrder = 0,
    super.tablesCount,
    super.createdAt,
    super.updatedAt,
  });

  factory TableAreaModel.fromJson(Map<String, dynamic> json) {
    return TableAreaModel(
      id: json['id'],
      restaurantId: json['restaurant_id'],
      name: json['name'],
      description: json['description'],
      displayOrder: json['display_order'] ?? 0,
      tablesCount: json['tables_count'],
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'])
          : null,
    );
  }
}
