<?php

namespace App\Contracts\Services;

use App\DTOs\TableAreaDTO;

/**
 * Contract cho TableArea Service.
 *
 * Controller chỉ inject interface này (DIP), không biết implementation cụ thể.
 */
interface TableAreaServiceInterface
{
    public function getAllAreas(string $restaurantId);

    public function getAreaById(string $id, string $restaurantId);

    public function createArea(string $restaurantId, TableAreaDTO $dto);

    /** @param array<string, mixed> $data Chỉ chứa các field thật sự được gửi lên (PATCH-safe) */
    public function updateArea(string $id, string $restaurantId, array $data);

    public function deleteArea(string $id, string $restaurantId): bool;
}
