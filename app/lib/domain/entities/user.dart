import 'package:equatable/equatable.dart';

/// Enum for user roles returned by the API
enum UserRole {
  owner,
  manager,
  waiter,
  kitchen,
  cashier,
  unknown;

  static UserRole fromString(String role) {
    return switch (role.toUpperCase()) {
      'OWNER' => UserRole.owner,
      'MANAGER' => UserRole.manager,
      'WAITER' => UserRole.waiter,
      'KITCHEN' => UserRole.kitchen,
      'CASHIER' => UserRole.cashier,
      _ => UserRole.unknown,
    };
  }

  /// Returns true if this role should be directed to the Ordering screen
  bool get isOrderingRole =>
      this == UserRole.owner ||
      this == UserRole.manager ||
      this == UserRole.waiter ||
      this == UserRole.cashier;

  /// Returns true if this role should be directed to the Kitchen screen
  bool get isKitchenRole => this == UserRole.kitchen;
}

/// Core User entity (Domain Layer)
class User extends Equatable {
  final String id;
  final String name;
  final String email;
  final UserRole role;
  final String roleLabel;
  final bool isActive;
  final String restaurantId;
  final List<String> features;
  final DateTime? lastLoginAt;
  final DateTime? createdAt;

  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.roleLabel,
    required this.isActive,
    required this.restaurantId,
    required this.features,
    this.lastLoginAt,
    this.createdAt,
  });

  @override
  List<Object?> get props => [
        id,
        name,
        email,
        role,
        roleLabel,
        isActive,
        restaurantId,
        features,
        lastLoginAt,
        createdAt,
      ];
}
