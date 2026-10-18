/// Nguồn tạo đơn hàng
enum OrderSourceChannel {
  cashier, // Thu ngân/Nhân viên tạo (POS_QUICK_ORDER)
  qrStatic, // Quét QR Code tĩnh
  qrTable, // Quét QR tại bàn
  unknown;

  static OrderSourceChannel fromString(String source) {
    return switch (source.toLowerCase()) {
      'cashier' => OrderSourceChannel.cashier,
      'qr_static' => OrderSourceChannel.qrStatic,
      'qr_table' => OrderSourceChannel.qrTable,
      _ => OrderSourceChannel.unknown,
    };
  }
}
