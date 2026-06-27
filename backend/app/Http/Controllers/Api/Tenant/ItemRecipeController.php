<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Repositories\ItemRepositoryInterface;
use App\Contracts\Repositories\RecipeRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SyncRecipeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemRecipeController extends Controller
{
    public function __construct(
        protected ItemRepositoryInterface $itemRepository,
        protected RecipeRepositoryInterface $recipeRepository
    ) {}

    /**
     * Lấy danh sách nguyên liệu của một món ăn.
     * GET /api/tenant/items/{id}/ingredients
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        
        // Find the item to ensure it belongs to the tenant
        $item = $this->itemRepository->findByIdAndRestaurantId($id, $restaurantId);
        
        $item->load('ingredients');

        return response()->json(['data' => $item->ingredients]);
    }

    /**
     * Đồng bộ công thức (nguyên liệu) cho món ăn.
     * POST /api/tenant/items/{id}/recipe
     */
    public function sync(SyncRecipeRequest $request, string $id): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        
        // Ensure the item belongs to the tenant
        $item = $this->itemRepository->findByIdAndRestaurantId($id, $restaurantId);

        $ingredientsData = $request->validated()['ingredients'] ?? [];

        // Sync ingredients using RecipeRepository
        $itemWithRecipe = $this->recipeRepository->syncIngredients($item->id, $ingredientsData);

        return response()->json([
            'data' => $itemWithRecipe,
            'message' => 'Đồng bộ công thức thành công',
        ]);
    }
}
