<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\FeatureCode;

class EnsureFeature
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware('feature:MENU_MANAGEMENT')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $restaurant = $user->restaurant;

        if (! $restaurant) {
            return response()->json(['message' => 'User does not belong to a restaurant.'], 403);
        }

        if (! $restaurant->isAccessible()) {
            return response()->json(['message' => 'Restaurant is not accessible.'], 403);
        }

        foreach ($features as $feature) {
            if (! $restaurant->hasFeature($feature)) {
                return response()->json([
                    'message' => 'Your subscription package does not include the required feature: ' . $feature,
                ], 403);
            }
        }

        return $next($request);
    }
}
