<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    public static function log(string $action, ?string $entityType = null, ?string $entityId = null, ?string $description = null): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        request()?->attributes->set('audit_log_written', true);

        $user = Auth::user();
        $payload = [
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('audit_logs', 'role')) {
            $payload['role'] = $user?->role;
        }

        if (Schema::hasColumn('audit_logs', 'hanh_dong')) {
            $payload['hanh_dong'] = self::actionLabel($action);
        }

        if (Schema::hasColumn('audit_logs', 'module')) {
            $payload['module'] = AuditLog::moduleLabelFor($entityType, $action);
        }

        if (Schema::hasColumn('audit_logs', 'noi_dung_thay_doi')) {
            $payload['noi_dung_thay_doi'] = $description;
        }

        AuditLog::create($payload);
    }

    public static function actionLabel(string $action): string
    {
        return match (true) {
            str_contains($action, 'created') || str_contains($action, 'store') || str_contains($action, 'imported') || str_contains($action, 'assigned') => 'Thêm mới',
            str_contains($action, 'updated') || str_contains($action, 'changed') || str_contains($action, 'activated') || str_contains($action, 'locked') || str_contains($action, 'archived') || str_contains($action, 'reset') || str_contains($action, 'approved') || str_contains($action, 'rejected') || str_contains($action, 'cloned') => 'Cập nhật',
            str_contains($action, 'deleted') || str_contains($action, 'unassigned') || str_contains($action, 'destroy') => 'Xóa bỏ',
            str_contains($action, 'login') => 'Đăng nhập',
            str_contains($action, 'logout') => 'Đăng xuất',
            default => 'Cập nhật',
        };
    }
}
