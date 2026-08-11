<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    // ĐÃ SỬA: Bọc lót cả 2 phom đường dẫn để mở khóa cổng bảo mật tuyệt đối
    $middleware->validateCsrfTokens(except: [
        'chatbot/send',
        '/chatbot/send',
    ]);


        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'no-cache' => \App\Http\Middleware\NoCacheHeaders::class,
            'force-password-change' => \App\Http\Middleware\ForcePasswordChange::class,
            'history.readonly' => \App\Http\Middleware\ReadOnlyHistoricalSchoolYear::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'api.json' => \App\Http\Middleware\ForceJsonResponse::class,
            'api.role' => \App\Http\Middleware\EnsureApiRole::class,
            'api.permission' => \App\Http\Middleware\EnsureApiPermission::class,
            'score.assignment' => \App\Http\Middleware\EnsureScoreAssignmentAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\Api\ApiResponse::error('Bạn cần đăng nhập để truy cập tài nguyên này', 401);
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\Api\ApiResponse::error(
                    message: 'Dữ liệu không hợp lệ',
                    status: 422,
                    errors: $exception->errors(),
                );
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\Api\ApiResponse::error('Bạn không có quyền truy cập tài nguyên này', 403);
            }

            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\Api\ApiResponse::error('Không tìm thấy endpoint API', 404);
            }

            return null;
        });
    })->create();

