<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Works for all roles — the caller decides which guard / dashboard to redirect to.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Chặn đăng nhập nếu là nhân viên nhưng nhà hàng không có tính năng STAFF_MANAGEMENT
        if (! $user->hasAnyRole(\App\Enums\UserRole::SUPER_ADMIN, \App\Enums\UserRole::OWNER)) {
            if (! $user->restaurant || ! $user->restaurant->hasFeature('STAFF_MANAGEMENT')) {
                throw ValidationException::withMessages([
                    'email' => ['Nhà hàng của bạn chưa đăng ký tính năng Quản lý Nhân viên. Vui lòng liên hệ Chủ quán.'],
                ]);
            }
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        return response()->json(new UserResource(Auth::user()));
    }
}
