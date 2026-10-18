import 'package:equatable/equatable.dart';
import 'package:kiottayapp/core/enums/TableStatus.dart';
import 'table_area_entity.dart';

class RestaurantTableEntity extends Equatable {
  final String id;
  final String restaurantId;
  final String? areaId;
  final TableAreaEntity? area; // Nếu API có eager load relationship 'area'
  final String? uid; // Mã bàn (ví dụ B-001)
  final String name; // Tên bàn hiển thị (ví dụ: Bàn số 1)
  final int capacity; // Sức chứa (Số ghế)
  final TableStatus status;
  final String
  statusLabel; // Nhãn trạng thái tiếng Việt ("Trống", "Đang sử dụng"...)
  final String? qrToken;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const RestaurantTableEntity({
    required this.id,
    required this.restaurantId,
    this.areaId,
    this.area,
    this.uid,
    required this.name,
    this.capacity = 4,
    required this.status,
    required this.statusLabel,
    this.qrToken,
    this.createdAt,
    this.updatedAt,
  });

  @override
  List<Object?> get props => [
    id,
    restaurantId,
    areaId,
    area,
    uid,
    name,
    capacity,
    status,
    statusLabel,
    qrToken,
    createdAt,
    updatedAt,
  ];
}
