<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\StaffServiceInterface;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffService implements StaffServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function paginate(string $restaurantId, array $filters = [])
    {
        return $this->users->getByRestaurantId($restaurantId, $filters);
    }

    public function find(string $restaurantId, string $staffId): User
    {
        /** @var User $user */
        $user = $this->users->findByIdAndRestaurantId($staffId, $restaurantId);

        if ($user->role === UserRole::SUPER_ADMIN) {
            abort(404);
        }

        return $user;
    }

    public function create(string $restaurantId, array $data): User
    {
        if ($this->users->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $this->checkRoleLimit($restaurantId, $data['role']);

        $payload = [
            'restaurant_id' => $restaurantId,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => $data['password'],
            'role'          => $data['role'],
            'is_active'     => $data['is_active'] ?? true,
        ];

        /** @var User $created */
        $created = $this->users->create($payload);

        return $created;
    }

    public function update(string $restaurantId, string $staffId, array $data): User
    {
        $user = $this->find($restaurantId, $staffId);

        if (isset($data['email']) && $this->users->emailExists($data['email'], $user->id)) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        if (isset($data['role']) && $data['role'] !== $user->role->value) {
            $this->checkRoleLimit($restaurantId, $data['role']);
        }

        $payload = [
            'name'      => $data['name'] ?? $user->name,
            'email'     => $data['email'] ?? $user->email,
            'role'      => $data['role'] ?? $user->role->value,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        /** @var User $updated */
        $updated = $this->users->update($user, $payload);

        return $updated;
    }

    public function deactivate(string $restaurantId, string $staffId, string $actorUserId): User
    {
        $user = $this->find($restaurantId, $staffId);

        if ($user->id === $actorUserId) {
            throw ValidationException::withMessages([
                'staff_id' => ['You cannot deactivate your own account.'],
            ]);
        }

        /** @var User $updated */
        $updated = $this->users->update($user, [
            'is_active' => false,
        ]);

        return $updated;
    }

    private function checkRoleLimit(string $restaurantId, string $role): void
    {
        $restaurant = Restaurant::with('subscriptions.package')->find($restaurantId);
        $activeSub = $restaurant?->activeSubscription;

        if (! $activeSub || ! $activeSub->package) {
            return;
        }

        $limits = $activeSub->package->role_limits;
        if (! is_array($limits) || ! isset($limits[$role])) {
            return; // Unlimited if not specified
        }

        $limit = (int) $limits[$role];

        // We count active users with this role to allow replacing deactivated staff
        $currentCount = User::where('restaurant_id', $restaurantId)
            ->where('role', $role)
            ->where('is_active', true)
            ->count();

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'role' => ["Giới hạn gói cước: Bạn chỉ được tạo tối đa {$limit} tài khoản chức vụ này."],
            ]);
        }
    }
}

