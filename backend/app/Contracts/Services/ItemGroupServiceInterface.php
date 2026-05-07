<?php

namespace App\Contracts\Services;

interface ItemGroupServiceInterface
{
    public function getAllGroups(string $restaurantId);
    public function getGroupById(string $id, string $restaurantId);
    public function createGroup(string $restaurantId, array $data);
    public function updateGroup(string $id, string $restaurantId, array $data);
    public function deleteGroup(string $id, string $restaurantId);
}
