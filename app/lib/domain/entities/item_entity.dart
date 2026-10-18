import 'package:equatable/equatable.dart';
import 'item_group_entity.dart';

class ItemEntity extends Equatable {
  final String id;
  final String name;
  final String? itemGroupId;
  final double salePrice;
  final String? imageUrl;
  final String? unit;
  final bool isActive;
  final String availabilityStatus;

  // Thêm thuộc tính này nếu API get Items có join kèm object ItemGroup
  final ItemGroupEntity? itemGroup;

  const ItemEntity({
    required this.id,
    required this.name,
    this.itemGroupId,
    required this.salePrice,
    this.imageUrl,
    this.unit,
    required this.isActive,
    required this.availabilityStatus,
    this.itemGroup,
  });

  @override
  List<Object?> get props => [
    id,
    name,
    itemGroupId,
    salePrice,
    imageUrl,
    unit,
    isActive,
    availabilityStatus,
    itemGroup, // Nhớ thêm vào props
  ];
}
