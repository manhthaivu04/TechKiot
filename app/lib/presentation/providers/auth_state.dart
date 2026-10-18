import 'package:equatable/equatable.dart';
import '../../domain/entities/user.dart';

/// Sealed state for authentication
sealed class AuthState extends Equatable {
  const AuthState();

  @override
  List<Object?> get props => [];
}

/// App is initializing — checking persisted session
class AuthInitial extends AuthState {
  const AuthInitial();
}

/// Login request is in progress
class AuthLoading extends AuthState {
  const AuthLoading();
}

/// Successfully authenticated
class AuthAuthenticated extends AuthState {
  final User user;
  final String token;

  const AuthAuthenticated({required this.user, required this.token});

  @override
  List<Object?> get props => [user, token];
}

/// Not authenticated (logged out or session expired)
class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated();
}

/// Authentication failed with an error message
class AuthError extends AuthState {
  final String message;

  const AuthError(this.message);

  @override
  List<Object?> get props => [message];
}
