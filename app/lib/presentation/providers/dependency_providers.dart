import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../data/datasources/auth_remote_datasource.dart';
import '../../data/datasources/menu_table_remote_datasource.dart';
import '../../data/datasources/order_remote_datasource.dart';
import '../../data/repositories/auth_repository_impl.dart';
import '../../data/repositories/menu_table_repository_impl.dart';
import '../../data/repositories/order_repository_impl.dart';
import '../../domain/repositories/auth_repository.dart';
import '../../domain/repositories/menu_table_repository.dart';
import '../../domain/repositories/order_repository.dart';
import '../../domain/usecases/login_usecase.dart';
import '../../domain/usecases/menu_usecases.dart';
import '../../domain/usecases/order_usecases.dart';
import '../../domain/usecases/table_usecases.dart';
import '../../core/services/realtime_service.dart';
import 'auth_controller.dart';
import 'auth_state.dart';

// ─── Infrastructure Providers ─────────────────────────────────────────────────

final dioProvider = Provider<Dio>((ref) {
  final secureStorage = ref.watch(secureStorageProvider);
  final dio = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      sendTimeout: const Duration(seconds: 15),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  // Add auth interceptor
  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await secureStorage.read(key: 'auth_token'); // AppConstants.tokenKey
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
    ),
  );

  // Add logging interceptor in debug mode
  assert(() {
    dio.interceptors.add(
      LogInterceptor(
        requestBody: true,
        responseBody: true,
        logPrint: (obj) => print(obj),
      ),
    );
    return true;
  }());
  return dio;
});

final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );
});

// ─── Data Layer Providers ──────────────────────────────────────────────────────

final authRemoteDataSourceProvider = Provider<AuthRemoteDataSource>((ref) {
  return AuthRemoteDataSourceImpl(ref.watch(dioProvider));
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(
    remoteDataSource: ref.watch(authRemoteDataSourceProvider),
    secureStorage: ref.watch(secureStorageProvider),
  );
});

// ─── Menu & Table Data Layer Providers ───────────────────────────────────────

final menuRemoteDataSourceProvider = Provider<MenuRemoteDataSource>((ref) {
  return MenuRemoteDataSourceImpl(ref.watch(dioProvider));
});

final tableRemoteDataSourceProvider = Provider<TableRemoteDataSource>((ref) {
  return TableRemoteDataSourceImpl(ref.watch(dioProvider));
});

final menuRepositoryProvider = Provider<MenuRepository>((ref) {
  return MenuRepositoryImpl(ref.watch(menuRemoteDataSourceProvider));
});

final tableRepositoryProvider = Provider<TableRepository>((ref) {
  return TableRepositoryImpl(ref.watch(tableRemoteDataSourceProvider));
});

// ─── Order Data Layer Providers ───────────────────────────────────────────────

final orderRemoteDataSourceProvider = Provider<OrderRemoteDataSource>((ref) {
  return OrderRemoteDataSourceImpl(ref.watch(dioProvider));
});

final orderRepositoryProvider = Provider<OrderRepository>((ref) {
  return OrderRepositoryImpl(ref.watch(orderRemoteDataSourceProvider));
});

// ─── Domain Layer Providers ────────────────────────────────────────────────────

final loginUseCaseProvider = Provider<LoginUseCase>((ref) {
  return LoginUseCase(ref.watch(authRepositoryProvider));
});

// Menu use cases
final getItemGroupsUseCaseProvider = Provider<GetItemGroupsUseCase>((ref) {
  return GetItemGroupsUseCase(ref.watch(menuRepositoryProvider));
});

final getItemsUseCaseProvider = Provider<GetItemsUseCase>((ref) {
  return GetItemsUseCase(ref.watch(menuRepositoryProvider));
});

// Table use cases
final getTableAreasUseCaseProvider = Provider<GetTableAreasUseCase>((ref) {
  return GetTableAreasUseCase(ref.watch(tableRepositoryProvider));
});

final getRestaurantTablesUseCaseProvider =
    Provider<GetRestaurantTablesUseCase>((ref) {
  return GetRestaurantTablesUseCase(ref.watch(tableRepositoryProvider));
});

// Order use cases
final createDineInOrderUseCaseProvider =
    Provider<CreateDineInOrderUseCase>((ref) {
  return CreateDineInOrderUseCase(ref.watch(orderRepositoryProvider));
});

final createTakeawayOrderUseCaseProvider =
    Provider<CreateTakeawayOrderUseCase>((ref) {
  return CreateTakeawayOrderUseCase(ref.watch(orderRepositoryProvider));
});

final getOpenOrdersUseCaseProvider = Provider<GetOpenOrdersUseCase>((ref) {
  return GetOpenOrdersUseCase(ref.watch(orderRepositoryProvider));
});

final addItemsToOrderUseCaseProvider =
    Provider<AddItemsToOrderUseCase>((ref) {
  return AddItemsToOrderUseCase(ref.watch(orderRepositoryProvider));
});

final updateOrderItemUseCaseProvider =
    Provider<UpdateOrderItemUseCase>((ref) {
  return UpdateOrderItemUseCase(ref.watch(orderRepositoryProvider));
});

final removeOrderItemUseCaseProvider =
    Provider<RemoveOrderItemUseCase>((ref) {
  return RemoveOrderItemUseCase(ref.watch(orderRepositoryProvider));
});

// Payment use cases
final getPaymentMethodSettingsUseCaseProvider =
    Provider<GetPaymentMethodSettingsUseCase>((ref) {
  return GetPaymentMethodSettingsUseCase(ref.watch(orderRepositoryProvider));
});

final recordPaymentUseCaseProvider = Provider<RecordPaymentUseCase>((ref) {
  return RecordPaymentUseCase(ref.watch(orderRepositoryProvider));
});

final updateOrderStatusUseCaseProvider =
    Provider<UpdateOrderStatusUseCase>((ref) {
  return UpdateOrderStatusUseCase(ref.watch(orderRepositoryProvider));
});

final getKitchenOrdersUseCaseProvider = Provider<GetKitchenOrdersUseCase>((ref) {
  return GetKitchenOrdersUseCase(ref.watch(orderRepositoryProvider));
});

final updateOrderItemStatusUseCaseProvider =
    Provider<UpdateOrderItemStatusUseCase>((ref) {
  return UpdateOrderItemStatusUseCase(ref.watch(orderRepositoryProvider));
});

// ─── Realtime Service Providers ────────────────────────────────────────────────

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  final service = RealtimeService();
  ref.onDispose(() => service.dispose());
  return service;
});

final currentRestaurantIdProvider = Provider<String?>((ref) {
  final authState = ref.watch(authControllerProvider);
  if (authState is AuthAuthenticated) {
    return authState.user.restaurantId;
  }
  return null;
});
