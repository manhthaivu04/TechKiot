import 'package:kiottayapp/core/enums/PaymentMethod.dart';
import 'package:kiottayapp/domain/entities/payment_entity.dart';

/// Data model for [PaymentEntity].
class PaymentModel extends PaymentEntity {
  const PaymentModel({
    required super.id,
    required super.amount,
    required super.paymentMethod,
    required super.methodLabel,
    super.referenceNo,
    super.paidAt,
  });

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    return PaymentModel(
      id: json['id'] as String,
      amount: double.tryParse(json['amount']?.toString() ?? '') ?? 0.0,
      paymentMethod: PaymentMethod.fromString(
        json['payment_method'] as String? ?? '',
      ),
      methodLabel: json['method_label'] as String? ?? '',
      referenceNo: json['reference_no'] as String?,
      paidAt: json['paid_at'] != null
          ? DateTime.tryParse(json['paid_at'] as String)
          : null,
    );
  }
}
