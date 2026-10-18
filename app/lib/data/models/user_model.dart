import 'dart:convert';
import '../../domain/entities/user.dart';

/// Data model extending the User entity.
/// Responsible for JSON serialization/deserialization.
class UserModel extends User {
  const UserModel({
    required super.id,
    required super.name,
    required super.email,
    required super.role,
    required super.roleLabel,
    required super.isActive,
    required super.restaurantId,
    required super.features,
    super.lastLoginAt,
    super.createdAt,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'].toString(),
      name: json['name'] as String,
      email: json['email'] as String,
      role: UserRole.fromString(json['role'] as String? ?? ''),
      roleLabel: json['role_label'] as String? ?? '',
      isActive: json['is_active'] as bool? ?? false,
      restaurantId: json['restaurant_id']?.toString() ?? '',
      features:
          (json['features'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
      lastLoginAt: json['last_login_at'] != null
          ? DateTime.tryParse(json['last_login_at'] as String)
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'role': role.name.toUpperCase(),
      'role_label': roleLabel,
      'is_active': isActive,
      'restaurant_id': restaurantId,
      'features': features,
      'last_login_at': lastLoginAt?.toIso8601String(),
      'created_at': createdAt?.toIso8601String(),
    };
  }

  String toJsonString() => jsonEncode(toJson());

  factory UserModel.fromJsonString(String jsonString) =>
      UserModel.fromJson(jsonDecode(jsonString) as Map<String, dynamic>);
}
