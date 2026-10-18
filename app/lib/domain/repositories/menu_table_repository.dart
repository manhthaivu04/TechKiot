import '../entities/item_entity.dart';
import '../entities/item_group_entity.dart';
import '../entities/restaurant_table_entity.dart';
import '../entities/table_area_entity.dart';

/// Abstract contract for Menu & Table data retrieval.
/// The Domain layer depends ONLY on this interface, not on concrete implementations.
abstract class MenuRepository {
  /// Returns all active item groups.
  Future<List<ItemGroupEntity>> getItemGroups();

  /// Returns all active items (menu items).
  Future<List<ItemEntity>> getItems();
}

abstract class TableRepository {
  /// Returns all table areas for the current restaurant.
  Future<List<TableAreaEntity>> getTableAreas();

  /// Returns all tables, optionally filtered by [areaId].
  Future<List<RestaurantTableEntity>> getRestaurantTables({String? areaId});
}
