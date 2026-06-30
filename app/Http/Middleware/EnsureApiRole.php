<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiAuth;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! ApiAuth::hasRole($request->user(), $roles)) {
            return ApiResponse::error(
                message: 'Bạn không có quyền truy cập tài nguyên này',
                status: 403,
            );
        }

        return $next($request);
    }
}
