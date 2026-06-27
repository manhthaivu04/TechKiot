<?php

namespace App\Services;

use App\Contracts\Services\AnalyticsServiceInterface;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService — tính toán toàn bộ chỉ số thống kê doanh thu.
 *
 * Thiết kế: stateless, mỗi method nhận đủ tham số, không dùng instance state.
 * An toàn để đăng ký singleton trong RepositoryServiceProvider.
 */
class AnalyticsService implements AnalyticsServiceInterface
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    // ─── Public API ───────────────────────────────────────────────────────────

    public function getDashboard(string $restaurantId, array $params): array
    {
        [$start, $end, $previousStart, $previousEnd] = $this->buildDateRange($params);

        $overview        = $this->getOverview($restaurantId, $start, $end, $previousStart, $previousEnd);
        $chartData       = $this->getChartData($restaurantId, $start, $end, $params['period'], Carbon::now(self::TIMEZONE));
        $byPayment       = $this->getByPaymentMethod($restaurantId, $start, $end);
        $byChannel       = $this->getBySourceChannel($restaurantId, $start, $end);
        $topItems        = $this->getTopItems($restaurantId, $start, $end);

        return [
            'period'             => $params['period'],
            'start_date'         => $start->toDateString(),
            'end_date'           => $end->toDateString(),
            'overview'           => $overview,
            'chart_data'         => $chartData,
            'by_payment_method'  => $byPayment,
            'by_source_channel'  => $byChannel,
            'top_items'          => $topItems,
        ];
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Tính khoảng thời gian hiện tại và kỳ trước (để tính % thay đổi).
     *
     * @return array{Carbon, Carbon, Carbon, Carbon}  [start, end, previousStart, previousEnd]
     */
    private function buildDateRange(array $params): array
    {
        $tz     = self::TIMEZONE;
        $period = $params['period'] ?? 'today';

        $now = Carbon::now($tz);

        switch ($period) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfDay();
                $previousStart = $start->copy()->subWeek();
                $previousEnd   = $end->copy()->subWeek();
                break;

            case 'month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfDay();
                $previousStart = $start->copy()->subMonth();
                $previousEnd   = $end->copy()->subMonth();
                break;

            case 'custom':
                $start = Carbon::parse($params['start_date'], $tz)->startOfDay();
                $end   = Carbon::parse($params['end_date'], $tz)->endOfDay();
                $diff  = $start->diffInDays($end) + 1;
                $previousStart = $start->copy()->subDays($diff);
                $previousEnd   = $end->copy()->subDays($diff);
                break;

            case 'today':
            default:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd   = $now->copy()->subDay()->endOfDay();
                break;
        }

        // GIỮ NGUYÊN múi giờ Asia/Ho_Chi_Minh:
        // APP_TIMEZONE='Asia/Ho_Chi_Minh' → Laravel/PHP lưu created_at vào DB
        // theo giờ Việt Nam (không phải UTC). Không cần chuyển sang UTC.
        return [
            $start,
            $end,
            $previousStart,
            $previousEnd,
        ];
    }

    /**
     * Tính các chỉ số tổng quan (revenue, orders, aov, % change).
     */
    private function getOverview(
        string $restaurantId,
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd
    ): array {
        // Kỳ hiện tại
        $current = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN status = ? THEN final_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status != ? THEN 1 ELSE 0 END)           as total_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)            as cancelled_orders
            ", [
                OrderStatus::Paid->value,
                OrderStatus::Cancelled->value,
                OrderStatus::Cancelled->value,
            ])
            ->first();

        // Kỳ trước (để tính % thay đổi)
        $previous = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', OrderStatus::Paid->value)
            ->selectRaw('SUM(final_amount) as total_revenue, COUNT(*) as total_orders')
            ->first();

        $currentRevenue = (float) ($current->total_revenue ?? 0);
        $currentOrders  = (int)   ($current->total_orders ?? 0);
        $prevRevenue    = (float) ($previous->total_revenue ?? 0);
        $prevOrders     = (int)   ($previous->total_orders ?? 0);
        $aov            = $currentOrders > 0 ? round($currentRevenue / $currentOrders, 0) : 0;

        return [
            'total_revenue'      => $currentRevenue,
            'total_orders'       => $currentOrders,
            'cancelled_orders'   => (int) ($current->cancelled_orders ?? 0),
            'avg_order_value'    => $aov,
            'revenue_change_pct' => $this->calcChangePct($currentRevenue, $prevRevenue),
            'orders_change_pct'  => $this->calcChangePct($currentOrders, $prevOrders),
        ];
    }

    /**
     * Lấy dữ liệu doanh thu theo thời gian cho biểu đồ.
     *
     * - today  : đủ 24 khung giờ (00:00 – 23:00), kể cả giờ chưa có doanh thu.
     * - week   : đủ 7 ngày trong tuần (T2 → CN), kể cả ngày chưa đến.
     * - month  : đủ mọi ngày trong tháng (01 → 30/31), kể cả ngày chưa đến.
     * - custom : chỉ các ngày trong khoảng được chọn.
     */
    private function getChartData(
        string $restaurantId,
        Carbon $start,
        Carbon $end,
        string $period,
        Carbon $now
    ): array {
        // ── TODAY: 24 khung giờ ──────────────────────────────────────────────
        if ($period === 'today') {
            $rows = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', OrderStatus::Paid->value)
                ->selectRaw("
                    DATE_FORMAT(created_at, '%H:00') as label,
                    SUM(final_amount) as revenue,
                    COUNT(*)          as orders
                ")
                ->groupBy('label')
                ->orderBy('label')
                ->get()
                ->keyBy('label');

            $result = [];
            for ($h = 0; $h < 24; $h++) {
                $label = sprintf('%02d:00', $h);
                $result[] = [
                    'label'   => $label,
                    'revenue' => isset($rows[$label]) ? (float) $rows[$label]->revenue : 0,
                    'orders'  => isset($rows[$label]) ? (int)   $rows[$label]->orders  : 0,
                ];
            }
            return $result;
        }

        // ── WEEK: 7 ngày (T2 → CN) ───────────────────────────────────────────
        if ($period === 'week') {
            // Query: từ đầu tuần đến HIỆN TẠI (không query tương lai)
            $queryEnd = $now->copy()->endOfDay();

            $rows = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereBetween('created_at', [$start, $queryEnd])
                ->where('status', OrderStatus::Paid->value)
                ->selectRaw("
                    DATE(created_at) as date_key,
                    SUM(final_amount) as revenue,
                    COUNT(*)          as orders
                ")
                ->groupBy('date_key')
                ->orderBy('date_key')
                ->get()
                ->keyBy('date_key');

            // Luôn điền đủ 7 ngày trong tuần, gắn label tiếng Việt
            $dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $weekStart = $start->copy(); // đã là startOfWeek (Monday)
            $result    = [];

            for ($i = 0; $i < 7; $i++) {
                $day = $weekStart->copy()->addDays($i);
                $key = $day->toDateString();
                $result[] = [
                    'label'   => $dayLabels[$i],
                    'revenue' => isset($rows[$key]) ? (float) $rows[$key]->revenue : 0,
                    'orders'  => isset($rows[$key]) ? (int)   $rows[$key]->orders  : 0,
                ];
            }
            return $result;
        }

        // ── MONTH: toàn bộ ngày trong tháng (01 → N) ─────────────────────────
        if ($period === 'month') {
            // Query: từ đầu tháng đến HIỆN TẠI (không query tương lai)
            $queryEnd    = $now->copy()->endOfDay();
            $daysInMonth = $start->copy()->daysInMonth; // số ngày thực của tháng

            $rows = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereBetween('created_at', [$start, $queryEnd])
                ->where('status', OrderStatus::Paid->value)
                ->selectRaw("
                    DATE(created_at)            as date_key,
                    DAY(created_at)             as day_num,
                    SUM(final_amount) as revenue,
                    COUNT(*)          as orders
                ")
                ->groupBy('date_key', 'day_num')
                ->orderBy('date_key')
                ->get()
                ->keyBy('date_key');

            $result = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $day = $start->copy()->day($d);
                $key = $day->toDateString();
                $result[] = [
                    'label'   => str_pad((string)$d, 2, '0', STR_PAD_LEFT),
                    'revenue' => isset($rows[$key]) ? (float) $rows[$key]->revenue : 0,
                    'orders'  => isset($rows[$key]) ? (int)   $rows[$key]->orders  : 0,
                ];
            }
            return $result;
        }

        // ── CUSTOM: các ngày trong khoảng người dùng chọn ────────────────────
        $rows = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', OrderStatus::Paid->value)
            ->selectRaw("
                DATE(created_at) as date_key,
                DATE_FORMAT(created_at, '%d/%m') as label,
                SUM(final_amount) as revenue,
                COUNT(*)          as orders
            ")
            ->groupBy('date_key', 'label')
            ->orderBy('date_key')
            ->get()
            ->keyBy('date_key');

        $result  = [];
        $datePeriod = CarbonPeriod::create($start->toDateString(), $end->toDateString());

        foreach ($datePeriod as $date) {
            $key = $date->toDateString();
            $result[] = [
                'label'   => $date->format('d/m'),
                'revenue' => isset($rows[$key]) ? (float) $rows[$key]->revenue : 0,
                'orders'  => isset($rows[$key]) ? (int)   $rows[$key]->orders  : 0,
            ];
        }

        return $result;
    }

    /**
     * Phân bổ doanh thu theo phương thức thanh toán (JOIN bảng payments).
     */
    private function getByPaymentMethod(string $restaurantId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', OrderStatus::Paid->value)
            ->selectRaw('payments.payment_method as method, SUM(payments.amount) as revenue, COUNT(*) as count')
            ->groupBy('payments.payment_method')
            ->orderByDesc('revenue')
            ->get();

        $labels = [
            'cash'     => 'Tiền mặt',
            'card'     => 'Thẻ ngân hàng',
            'transfer' => 'Chuyển khoản',
            'ewallet'  => 'Ví điện tử',
        ];

        return $rows->map(fn ($row) => [
            'method'  => $row->method,
            'label'   => $labels[$row->method] ?? $row->method,
            'revenue' => (float) $row->revenue,
            'count'   => (int)   $row->count,
        ])->values()->toArray();
    }

    /**
     * Phân bổ doanh thu theo kênh tạo đơn (cashier, qr_static...).
     */
    private function getBySourceChannel(string $restaurantId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', OrderStatus::Paid->value)
            ->selectRaw('source_channel as channel, SUM(final_amount) as revenue, COUNT(*) as count')
            ->groupBy('source_channel')
            ->orderByDesc('revenue')
            ->get();

        $labels = [
            'cashier'    => 'POS / Thu ngân',
            'qr_static'  => 'QR Tĩnh',
            'qr_table'   => 'QR Bàn',
        ];

        return $rows->map(fn ($row) => [
            'channel' => $row->channel,
            'label'   => $labels[$row->channel] ?? $row->channel,
            'revenue' => (float) $row->revenue,
            'count'   => (int)   $row->count,
        ])->values()->toArray();
    }

    /**
     * Top 5 món ăn bán chạy (theo số lượng).
     */
    private function getTopItems(string $restaurantId, Carbon $start, Carbon $end): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('items',  'order_items.item_id',  '=', 'items.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', OrderStatus::Paid->value)
            ->selectRaw('
                items.id,
                items.name,
                items.image_url,
                SUM(order_items.quantity)   as total_sold,
                SUM(order_items.line_total) as total_revenue
            ')
            ->groupBy('items.id', 'items.name', 'items.image_url')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'item_id'       => $row->id,
                'name'          => $row->name,
                'image_url'     => $row->image_url,
                'total_sold'    => (int)   $row->total_sold,
                'total_revenue' => (float) $row->total_revenue,
            ])
            ->toArray();
    }

    /**
     * Tính % thay đổi giữa 2 kỳ. Trả về null nếu không thể tính.
     */
    private function calcChangePct(float|int $current, float|int $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : null;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
