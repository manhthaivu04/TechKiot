/// Trạng thái của bàn ăn
enum TableStatus {
  available, // Trống
  occupied, // Đang sử dụng (đang có khách/đơn hàng)
  reserved, // Đã đặt trước
  inactive, // Ngưng hoạt động
  unknown;

  static TableStatus fromString(String status) {
    return switch (status.toLowerCase()) {
      'available' => TableStatus.available,
      'occupied' => TableStatus.occupied,
      'reserved' => TableStatus.reserved,
      'inactive' => TableStatus.inactive,
      _ => TableStatus.unknown,
    };
  }
}
