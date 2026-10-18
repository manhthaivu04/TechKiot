/// Phương thức thanh toán
enum PaymentMethod {
  cash, // Tiền mặt
  card, // Thẻ
  transfer, // Chuyển khoản
  ewallet, // Ví điện tử (Momo, ZaloPay,...)
  unknown;

  static PaymentMethod fromString(String method) {
    return switch (method.toLowerCase()) {
      'cash' => PaymentMethod.cash,
      'card' => PaymentMethod.card,
      'transfer' => PaymentMethod.transfer,
      'ewallet' => PaymentMethod.ewallet,
      _ => PaymentMethod.unknown,
    };
  }
}
