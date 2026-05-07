<?php

namespace App\Contracts\Repositories;

interface ItemGroupRepositoryInterface extends BaseRepositoryInterface
{
    public function getByRestaurantId(string $restaurantId);
    public function findByIdAndRestaurantId(string $id, string $restaurantId);
}
