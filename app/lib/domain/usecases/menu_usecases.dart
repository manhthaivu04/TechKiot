import '../entities/item_entity.dart';
import '../entities/item_group_entity.dart';
import '../repositories/menu_table_repository.dart';

/// Use case: Fetch all item groups (danh mục món).
class GetItemGroupsUseCase {
  final MenuRepository _repository;
  const GetItemGroupsUseCase(this._repository);

  Future<List<ItemGroupEntity>> call() => _repository.getItemGroups();
}

/// Use case: Fetch all menu items (danh sách món).
class GetItemsUseCase {
  final MenuRepository _repository;
  const GetItemsUseCase(this._repository);

  Future<List<ItemEntity>> call() => _repository.getItems();
}
