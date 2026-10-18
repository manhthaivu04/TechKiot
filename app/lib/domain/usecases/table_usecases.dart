import '../entities/restaurant_table_entity.dart';
import '../entities/table_area_entity.dart';
import '../repositories/menu_table_repository.dart';

/// Use case: Fetch all table areas (khu vực bàn).
class GetTableAreasUseCase {
  final TableRepository _repository;
  const GetTableAreasUseCase(this._repository);

  Future<List<TableAreaEntity>> call() => _repository.getTableAreas();
}

/// Use case: Fetch restaurant tables, optionally filtered by area.
class GetRestaurantTablesUseCase {
  final TableRepository _repository;
  const GetRestaurantTablesUseCase(this._repository);

  Future<List<RestaurantTableEntity>> call({String? areaId}) =>
      _repository.getRestaurantTables(areaId: areaId);
}
