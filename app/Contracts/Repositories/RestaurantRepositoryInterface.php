<?php

namespace App\Contracts\Repositories;

use App\Models\Restaurant;
use Illuminate\Support\Collection;

/**
 * Restaurant-specific read/write operations.
 * Extends BaseRepositoryInterface (ISP: only what consumers of restaurants need).
 */
interface RestaurantRepositoryInterface extends BaseRepositoryInterface
{
    public function findByStatus(string $status): Collection;

    public function findWithActiveSubscription(string $id): ?Restaurant;

    /** Check if a public_order_token is already in use */
    public function tokenExists(string $token, ?string $excludeId = null): bool;
}
