import 'package:equatable/equatable.dart';

class ItemGroupEntity extends Equatable {
  final String id;
  final String name; // Tên danh mục (Đồ ăn, Nước uống...)
  final int displayOrder; // Thứ tự hiển thị trên UI
  final bool isActive; // Trạng thái bật/tắt của danh mục

  const ItemGroupEntity({
    required this.id,
    required this.name,
    this.displayOrder = 0,
    required this.isActive,
  });

  @override
  List<Object?> get props => [id, name, displayOrder, isActive];
}
