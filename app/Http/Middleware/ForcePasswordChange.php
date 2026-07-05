<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->force_change_password) {
            return $next($request);
        }

        if ($request->routeIs('profile.change-password', 'profile.update-password', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('profile.change-password')
            ->with('warning', 'Vui lòng đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
    }
}
