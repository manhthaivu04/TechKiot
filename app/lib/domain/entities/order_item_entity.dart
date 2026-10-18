import 'package:equatable/equatable.dart';

class OrderItemEntity extends Equatable {
  final String id;
  final String itemId;
  final String? itemName; // Lấy từ relation khi backend trả về
  final String? itemImage;
  final int quantity;
  final double unitPrice;
  final double lineTotal;
  final String status;
  final String? note;
  final DateTime? createdAt;

  const OrderItemEntity({
    required this.id,
    required this.itemId,
    this.itemName,
    this.itemImage,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
    required this.status,
    this.note,
    this.createdAt,
  });

  @override
  List<Object?> get props => [
        id, itemId, itemName, itemImage, quantity, unitPrice, lineTotal, status, note, createdAt,
      ];
}
