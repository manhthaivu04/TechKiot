import 'package:equatable/equatable.dart';

class TableAreaEntity extends Equatable {
  final String id;
  final String restaurantId;
  final String name;
  final String? description;
  final int displayOrder;
  final int? tablesCount; // Số lượng bàn trong khu vực này (nếu API trả về)
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const TableAreaEntity({
    required this.id,
    required this.restaurantId,
    required this.name,
    this.description,
    this.displayOrder = 0,
    this.tablesCount,
    this.createdAt,
    this.updatedAt,
  });

  @override
  List<Object?> get props => [
    id,
    restaurantId,
    name,
    description,
    displayOrder,
    tablesCount,
    createdAt,
    updatedAt,
  ];
}
