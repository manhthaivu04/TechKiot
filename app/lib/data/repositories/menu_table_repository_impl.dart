import '../../domain/entities/item_entity.dart';
import '../../domain/entities/item_group_entity.dart';
import '../../domain/entities/restaurant_table_entity.dart';
import '../../domain/entities/table_area_entity.dart';
import '../../domain/repositories/menu_table_repository.dart';
import '../datasources/menu_table_remote_datasource.dart';

/// Concrete implementation of [MenuRepository].
class MenuRepositoryImpl implements MenuRepository {
  final MenuRemoteDataSource _dataSource;

  const MenuRepositoryImpl(this._dataSource);

  @override
  Future<List<ItemGroupEntity>> getItemGroups() =>
      _dataSource.getItemGroups();

  @override
  Future<List<ItemEntity>> getItems() => _dataSource.getItems();
}

/// Concrete implementation of [TableRepository].
class TableRepositoryImpl implements TableRepository {
  final TableRemoteDataSource _dataSource;

  const TableRepositoryImpl(this._dataSource);

  @override
  Future<List<TableAreaEntity>> getTableAreas() =>
      _dataSource.getTableAreas();

  @override
  Future<List<RestaurantTableEntity>> getRestaurantTables({String? areaId}) =>
      _dataSource.getRestaurantTables(areaId: areaId);
}
