<?php

namespace App\Services;

use App\Contracts\Repositories\RestaurantTableRepositoryInterface;
use App\Contracts\Repositories\TableAreaRepositoryInterface;
use App\Contracts\Services\RestaurantTableServiceInterface;
use App\DTOs\RestaurantTableDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * RestaurantTableService — xử lý business logic cho bàn ăn.
 *
 * SRP: Service chứa logic nghiệp vụ (sinh uid, qr_token, validate ownership).
 * DIP: Inject repository qua interface.
 * OCP: Sau này mở rộng QR order, sơ đồ bàn chỉ cần thêm method mới.
 */
class RestaurantTableService implements RestaurantTableServiceInterface
{
    /** Số lần retry khi gặp duplicate UID do race condition */
    private const MAX_UID_RETRIES = 5;

    public function __construct(
        protected RestaurantTableRepositoryInterface $tableRepository,
        protected TableAreaRepositoryInterface       $tableAreaRepository,
    ) {}

    public function getAllTables(string $restaurantId, array $filters = [])
    {
        return $this->tableRepository->getByRestaurantId($restaurantId, $filters);
    }

    public function getTableById(string $id, string $restaurantId)
    {
        return $this->tableRepository->findByIdAndRestaurantId($id, $restaurantId);
    }

    /**
     * Tạo bàn mới — bọc trong transaction + retry để chống race condition trên auto UID.
     *
     * Nếu 2 request đồng thời sinh cùng UID, request sau sẽ gặp unique constraint
     * violation. Service bắt lỗi này, tính lại UID, và retry (tối đa MAX_UID_RETRIES lần).
     */
    public function createTable(string $restaurantId, RestaurantTableDTO $dto)
    {
        // 1. Nếu có area_id, kiểm tra phải thuộc cùng restaurant
        if ($dto->areaId) {
            $this->tableAreaRepository->findByIdAndRestaurantId($dto->areaId, $restaurantId);
        }

        $data = $dto->toArray();
        $data['restaurant_id'] = $restaurantId;
        $data['qr_token'] = $this->generateQrToken();

        // 2. Nếu client truyền uid → validate unique rồi tạo (không retry)
        if (!empty($data['uid'])) {
            $this->ensureUidUnique($restaurantId, $data['uid']);
            return DB::transaction(fn () => $this->tableRepository->create($data));
        }

        // 3. Auto-generate uid — retry khi gặp duplicate key do race condition
        $lastException = null;
        for ($attempt = 1; $attempt <= self::MAX_UID_RETRIES; $attempt++) {
            try {
                return DB::transaction(function () use ($restaurantId, $data) {
                    $data['uid'] = $this->generateNextUid($restaurantId);
                    return $this->tableRepository->create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // Error code 23000 = Integrity constraint violation (duplicate key)
                if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uid')) {
                    $lastException = $e;
                    continue; // Retry với UID mới
                }
                throw $e; // Lỗi khác → throw ngay
            }
        }

        // Hết retry → trả lỗi rõ ràng thay vì 500
        throw ValidationException::withMessages([
            'uid' => ['Không thể sinh mã bàn tự động. Vui lòng thử lại hoặc nhập mã bàn thủ công.'],
        ]);
    }

    /**
     * Cập nhật bàn — chỉ update các field thật sự được gửi lên (PATCH-safe).
     *
     * Nhận raw array thay vì DTO để tránh DTO gán default cho field không gửi,
     * gây reset dữ liệu khi PATCH chỉ gửi 1-2 field.
     *
     * Cho phép clear area_id bằng cách gửi "area_id": null.
     *
     * @param array<string, mixed> $data Chỉ chứa field client gửi lên (validated)
     */
    public function updateTable(string $id, string $restaurantId, array $data)
    {
        $table = $this->getTableById($id, $restaurantId); // Đảm bảo ownership

        // Nếu client gửi area_id và khác null → validate thuộc cùng restaurant
        if (array_key_exists('area_id', $data) && $data['area_id'] !== null) {
            $this->tableAreaRepository->findByIdAndRestaurantId($data['area_id'], $restaurantId);
        }

        // Chỉ lấy các field được phép update
        $allowedFields = ['area_id', 'uid', 'name', 'capacity', 'status'];
        $fillable = array_intersect_key($data, array_flip($allowedFields));

        // Kiểm tra uid không trùng (bỏ qua chính nó)
        if (isset($fillable['uid']) && $fillable['uid'] !== $table->uid) {
            $this->ensureUidUnique($restaurantId, $fillable['uid'], $id);
        }

        return $this->tableRepository->update($table, $fillable);
    }

    public function deleteTable(string $id, string $restaurantId): bool
    {
        $table = $this->getTableById($id, $restaurantId);

        return $this->tableRepository->delete($table);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Sinh uid tiếp theo theo format B-001, B-002, ...
     * Lấy uid cuối cùng từ DB rồi tăng số lên 1.
     */
    private function generateNextUid(string $restaurantId): string
    {
        $lastUid = $this->tableRepository->getLastUid($restaurantId);

        if ($lastUid) {
            // Tách số từ uid (B-001 → 1), tăng lên 1
            $number = (int) substr($lastUid, 2); // bỏ "B-"
            $next = $number + 1;
        } else {
            $next = 1;
        }

        return 'B-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Sinh qr_token unique toàn hệ thống.
     * Dùng UUID v4 để đảm bảo tính duy nhất.
     */
    private function generateQrToken(): string
    {
        return 'tbl_' . Str::uuid()->toString();
    }

    /**
     * Đảm bảo uid unique trong phạm vi nhà hàng.
     *
     * @throws ValidationException nếu uid đã tồn tại
     */
    private function ensureUidUnique(string $restaurantId, string $uid, ?string $excludeId = null): void
    {
        if ($this->tableRepository->uidExists($restaurantId, $uid, $excludeId)) {
            throw ValidationException::withMessages([
                'uid' => ["Mã bàn '{$uid}' đã tồn tại trong nhà hàng này."],
            ]);
        }
    }
}
