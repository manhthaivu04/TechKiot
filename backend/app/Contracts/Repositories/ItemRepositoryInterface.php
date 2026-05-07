<?php

namespace App\Contracts\Repositories;

interface ItemRepositoryInterface extends BaseRepositoryInterface
{
    public function getByRestaurantId(string $restaurantId, array $filters = []);
    public function findByIdAndRestaurantId(string $id, string $restaurantId);
}
