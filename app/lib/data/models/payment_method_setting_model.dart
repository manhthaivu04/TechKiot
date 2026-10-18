import 'package:kiottayapp/core/enums/PaymentMethod.dart';
import 'package:kiottayapp/domain/entities/payment_method_setting_entity.dart';

/// Data model for [PaymentMethodSettingEntity].
class PaymentMethodSettingModel extends PaymentMethodSettingEntity {
  const PaymentMethodSettingModel({
    required super.id,
    required super.paymentMethod,
    required super.methodLabel,
    required super.isActive,
    super.displayName,
    super.qrCodeUrl,
    super.updatedAt,
  });

  factory PaymentMethodSettingModel.fromJson(Map<String, dynamic> json) {
    return PaymentMethodSettingModel(
      // The API response does not include an 'id' field; we derive a stable
      // key from the payment_method string itself.
      id: json['id'] as String? ?? json['payment_method'] as String,
      paymentMethod: PaymentMethod.fromString(
        json['payment_method'] as String? ?? '',
      ),
      methodLabel: json['method_label'] as String? ?? '',
      isActive: json['is_active'] as bool? ?? false,
      displayName: json['display_name'] as String?,
      qrCodeUrl: json['qr_code_url'] as String?,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'] as String)
          : null,
    );
  }
}
