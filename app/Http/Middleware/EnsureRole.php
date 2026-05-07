<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole middleware — checks that the authenticated user has one of the allowed roles.
 *
 * Usage in routes:  ->middleware('role:SUPER_ADMIN')
 *                   ->middleware('role:SUPER_ADMIN,OWNER')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        $allowedRoles = array_map(
            fn (string $r) => UserRole::from($r),
            $roles,
        );

        if (! $user->hasAnyRole(...$allowedRoles)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
