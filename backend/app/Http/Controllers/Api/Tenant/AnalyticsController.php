<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Services\AnalyticsServiceInterface;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * AnalyticsController — Dashboard thống kê doanh thu cho Tenant.
 *
 * Chỉ dành cho OWNER và MANAGER (kiểm soát tại route middleware).
 * Controller chỉ validate input và delegate sang AnalyticsService.
 */
class AnalyticsController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly AnalyticsServiceInterface $analyticsService
    ) {}

    /**
     * GET /api/tenant/analytics/dashboard
     *
     * Query params:
     *   - period: today | week | month | custom  (default: today)
     *   - start_date: Y-m-d  (bắt buộc khi period=custom)
     *   - end_date:   Y-m-d  (bắt buộc khi period=custom)
     */
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period'     => ['sometimes', Rule::in(['today', 'week', 'month', 'custom'])],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date'   => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $validated['period'] ??= 'today';

        /** @var \App\Models\User $user */
        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        $data = $this->analyticsService->getDashboard($restaurantId, $validated);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * GET /api/tenant/analytics/transactions
     *
     * Tra cứu hóa đơn đã giao dịch (status = paid).
     *
     * Query params:
     *   - search        : order_code | customer_name | customer_phone
     *   - date_from     : Y-m-d
     *   - date_to       : Y-m-d
     *   - payment_method: cash | card | transfer | ewallet
     *   - page          : int (default 1)
     *   - per_page      : int (default 20, max 100)
     */
    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search'         => ['nullable', 'string', 'max:100'],
            'date_from'      => ['nullable', 'date_format:Y-m-d'],
            'date_to'        => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'payment_method' => ['nullable', 'string', Rule::in(['cash', 'card', 'transfer', 'ewallet'])],
            'page'           => ['nullable', 'integer', 'min:1'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var \App\Models\User $user */
        $user         = $request->user();
        $restaurantId = $user->restaurant_id;
        $perPage      = (int) ($validated['per_page'] ?? 20);
        $tz           = self::TIMEZONE;

        $query = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', OrderStatus::Paid)
            ->with(['items.item', 'payments'])
            // Tìm kiếm theo mã đơn / khách hàng
            ->when($validated['search'] ?? null, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('order_code',     'LIKE', "%{$search}%")
                       ->orWhere('customer_name',  'LIKE', "%{$search}%")
                       ->orWhere('customer_phone', 'LIKE', "%{$search}%");
                });
            })
            // Lọc theo ngày bắt đầu
            ->when($validated['date_from'] ?? null, function ($q, $dateFrom) use ($tz) {
                $q->where('created_at', '>=', Carbon::parse($dateFrom, $tz)->startOfDay());
            })
            // Lọc theo ngày kết thúc
            ->when($validated['date_to'] ?? null, function ($q, $dateTo) use ($tz) {
                $q->where('created_at', '<=', Carbon::parse($dateTo, $tz)->endOfDay());
            })
            // Lọc theo phương thức thanh toán
            ->when($validated['payment_method'] ?? null, function ($q, $method) {
                $q->whereHas('payments', fn ($p) => $p->where('payment_method', $method));
            })
            ->latest();

        // Tổng tiền theo toàn bộ filter (không giới hạn trang)
        $totalRevenue = (clone $query)->sum('final_amount');

        $paginated = $query->paginate($perPage);

        return $this->successResponse([
            'items' => OrderResource::collection($paginated->items()),
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_orders'  => $paginated->total(),
            ],
        ]);
    }
}
