<?php

namespace App\Contracts\Orders;

use App\DTOs\AddItemsDTO;
use Closure;

interface AddItemsPipeInterface
{
    /**
     * @param  AddItemsDTO $dto   Dữ liệu gọi thêm món
     * @param  Closure     $next  Callback để gọi Pipe tiếp theo
     * @return mixed              Kết quả từ Pipe cuối cùng
     */
    public function handle(AddItemsDTO $dto, Closure $next): mixed;
}
