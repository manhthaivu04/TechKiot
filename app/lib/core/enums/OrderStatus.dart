enum OrderStatus {
  open, // Đang mở (mới tạo)
  cooking, // Đang nấu
  served, // Đã lên món
  paid, // Đã thanh toán (Hoàn thành)
  cancelled, // Đã hủy
  unknown;

  static OrderStatus fromString(String status) {
    return switch (status.toLowerCase()) {
      'open' => OrderStatus.open,
      'cooking' => OrderStatus.cooking,
      'served' => OrderStatus.served,
      'paid' => OrderStatus.paid,
      'cancelled' => OrderStatus.cancelled,
      _ => OrderStatus.unknown,
    };
  }
}
