<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use App\Support\Api\ApiAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $exception) {
            return $this->error(
                message: 'Dữ liệu đăng nhập không hợp lệ',
                status: 422,
                errors: $exception->errors(),
            );
        }

        $user = User::where('username', $credentials['username'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            return $this->error(
                message: 'Sai tài khoản hoặc mật khẩu',
                status: 401,
                errors: [
                    'username' => ['Sai tài khoản hoặc mật khẩu'],
                ],
            );
        }

        $token = $user->createToken($credentials['device_name'] ?? 'android')->plainTextToken;

        return $this->success(
            data: [
                'user' => ApiAuth::userPayload($user),
            ],
            message: 'Đăng nhập thành công',
            extra: [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Đăng xuất thành công');
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->is_active) {
            $user->currentAccessToken()?->delete();

            return $this->error(
                message: 'Tài khoản đã bị khóa',
                status: 403,
            );
        }

        return $this->success([
            'user' => ApiAuth::userPayload($user),
        ], 'Lấy thông tin người dùng thành công');
    }
}
