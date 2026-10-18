import 'package:equatable/equatable.dart';
import 'package:kiottayapp/core/enums/PaymentMethod.dart';

class PaymentMethodSettingEntity extends Equatable {
  final String id;
  final PaymentMethod paymentMethod;
  final String methodLabel; // Ví dụ: "Chuyển khoản", "Tiền mặt"
  final bool isActive; // Trạng thái (được sử dụng hay không)
  final String? displayName; // Tên hiển thị tự chỉnh (Ví dụ: "Vietcombank")
  final String? qrCodeUrl; // Link ảnh QR nếu có (Chuyển khoản / Momo)
  final DateTime? updatedAt;

  const PaymentMethodSettingEntity({
    required this.id,
    required this.paymentMethod,
    required this.methodLabel,
    required this.isActive,
    this.displayName,
    this.qrCodeUrl,
    this.updatedAt,
  });

  @override
  List<Object?> get props => [
    id,
    paymentMethod,
    methodLabel,
    isActive,
    displayName,
    qrCodeUrl,
    updatedAt,
  ];
}
