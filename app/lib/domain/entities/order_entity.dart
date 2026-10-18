import 'package:equatable/equatable.dart';
import 'package:kiottayapp/core/enums/OrderSourceChannel.dart';
import 'package:kiottayapp/core/enums/OrderStatus.dart';
import 'package:kiottayapp/core/enums/PaymentMethod.dart';
import 'package:kiottayapp/domain/entities/order_item_entity.dart';
import 'package:kiottayapp/domain/entities/payment_entity.dart';

class OrderEntity extends Equatable {
  final String id;
  final String orderCode;
  final OrderSourceChannel sourceChannel;
  final String? serviceType; // 'dine_in', 'takeaway', 'delivery'
  final OrderStatus status;
  final String statusLabel;
  final String? tableId;

  // Khách hàng
  final String? customerName;
  final String? customerPhone;
  final String? customerReference;
  final int? guestCount;

  // Tài chính
  final double subtotalAmount;
  final double discountAmount;
  final double taxAmount;
  final double finalAmount;

  final String? note;
  final List<OrderItemEntity> items;      // Danh sách món đã gọi
  final List<PaymentEntity> payments;     // Danh sách các lần thanh toán
  
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const OrderEntity({
    required this.id,
    required this.orderCode,
    required this.sourceChannel,
    this.serviceType,
    required this.status,
    required this.statusLabel,
    this.tableId,
    this.customerName,
    this.customerPhone,
    this.customerReference,
    this.guestCount,
    required this.subtotalAmount,
    required this.discountAmount,
    required this.taxAmount,
    required this.finalAmount,
    this.note,
    this.items = const [],
    this.payments = const [],
    this.createdAt,
    this.updatedAt,
  });

  @override
  List<Object?> get props => [
        id, orderCode, sourceChannel, serviceType, status, statusLabel, tableId,
        customerName, customerPhone, customerReference, guestCount,
        subtotalAmount, discountAmount, taxAmount, finalAmount,
        note, items, payments, createdAt, updatedAt,
      ];
}



