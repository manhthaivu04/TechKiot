import 'package:equatable/equatable.dart';
import 'package:kiottayapp/core/enums/PaymentMethod.dart';

class PaymentEntity extends Equatable {
  final String id;
  final double amount;
  final PaymentMethod paymentMethod;
  final String methodLabel;
  final String? referenceNo;
  final DateTime? paidAt;

  const PaymentEntity({
    required this.id,
    required this.amount,
    required this.paymentMethod,
    required this.methodLabel,
    this.referenceNo,
    this.paidAt,
  });

  @override
  List<Object?> get props => [
        id, amount, paymentMethod, methodLabel, referenceNo, paidAt,
      ];
}