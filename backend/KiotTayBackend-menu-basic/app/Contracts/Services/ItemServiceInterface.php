<?php

namespace App\Contracts\Services;

use Illuminate\Http\UploadedFile;

interface ItemServiceInterface
{
    public function getAllItems(string $restaurantId, array $filters = []);
    public function getItemById(string $id, string $restaurantId);
    public function createItem(string $restaurantId, array $data, ?UploadedFile $image = null);
    public function updateItem(string $id, string $restaurantId, array $data, ?UploadedFile $image = null);
    public function deleteItem(string $id, string $restaurantId);
}
