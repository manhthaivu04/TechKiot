<?php

namespace App\Contracts\Services;

use App\DTOs\RestaurantTableDTO;

/**
 * Contract cho RestaurantTable Service.
 *
 * Controller chỉ inject interface này (DIP).
 * Sau này có thể mở rộng thêm method cho QR, sơ đồ bàn mà
 * không sửa Controller — tuân thủ OCP.
 */
interface RestaurantTableServiceInterface
{
    public function getAllTables(string $restaurantId, array $filters = []);

    public function getTableById(string $id, string $restaurantId);

    public function createTable(string $restaurantId, RestaurantTableDTO $dto);

    /** @param array<string, mixed> $data Chỉ chứa các field thật sự được gửi lên (PATCH-safe) */
    public function updateTable(string $id, string $restaurantId, array $data);

    public function deleteTable(string $id, string $restaurantId): bool;
}
