<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoAuditWriteRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        $routeName = (string) $request->route()?->getName();
        AuditLogger::log(
            $this->actionKey($request, $routeName),
            null,
            null,
            $this->description($request, $routeName)
        );

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->attributes->get('audit_log_written')) {
            return false;
        }

        $user = $request->user();
        if (! $user || ! ($user->isAdmin() || $user->isStaff() || $user->isTeacher())) {
            return false;
        }

        return ! $request->routeIs('chatbot.*', 'parent.select-child');
    }

    private function actionKey(Request $request, string $routeName): string
    {
        if ($request->isMethod('DELETE')) {
            return 'auto_deleted_' . str_replace('.', '_', $routeName ?: 'record');
        }

        if ($request->isMethod('POST') && (str_contains($routeName, '.store') || str_contains($routeName, 'import'))) {
            return 'auto_created_' . str_replace('.', '_', $routeName ?: 'record');
        }

        return 'auto_updated_' . str_replace('.', '_', $routeName ?: 'record');
    }

    private function description(Request $request, string $routeName): string
    {
        $module = $this->moduleFromRoute($routeName, $request->path());
        $action = match ($request->method()) {
            'DELETE' => 'xóa dữ liệu',
            'POST' => str_contains($routeName, '.store') ? 'thêm mới dữ liệu' : 'cập nhật dữ liệu',
            default => 'cập nhật dữ liệu',
        };

        return 'Tự động ghi nhận thao tác ' . $action . ' tại phân hệ ' . $module . '.';
    }

    private function moduleFromRoute(string $routeName, string $path): string
    {
        $target = $routeName ?: $path;

        return match (true) {
            str_contains($target, 'scores') || str_contains($target, 'score-columns') || str_contains($target, 'grade-windows') => 'Điểm số',
            str_contains($target, 'attendance') => 'Điểm danh',
            str_contains($target, 'conduct') => 'Hạnh kiểm',
            str_contains($target, 'subjects') => 'Môn học',
            str_contains($target, 'classes') => 'Lớp học',
            str_contains($target, 'assignments') => 'Phân công',
            str_contains($target, 'admin-users') || str_contains($target, 'rbac-roles') || str_contains($target, 'teachers') || str_contains($target, 'students') || str_contains($target, 'parents') => 'Tài khoản',
            str_contains($target, 'timetable') => 'Thời khóa biểu',
            str_contains($target, 'exam-schedules') => 'Lịch kiểm tra',
            str_contains($target, 'documents') => 'Tài liệu',
            str_contains($target, 'announcements') => 'Thông báo',
            str_contains($target, 'events') => 'Sự kiện',
            str_contains($target, 'rooms') => 'Phòng học',
            str_contains($target, 'departments') => 'Tổ chuyên môn',
            default => 'Hệ thống',
        };
    }
}
