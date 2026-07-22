<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use UsesUuid;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'hanh_dong',
        'entity_type',
        'entity_id',
        'module',
        'description',
        'noi_dung_thay_doi',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moduleLabel(): string
    {
        if ($this->module) {
            return $this->module;
        }

        if ($this->entity_type) {
            return self::moduleLabelFor($this->entity_type, $this->action);
        }

        return match (true) {
            str_contains($this->action, 'login') || str_contains($this->action, 'logout') => 'Đăng nhập',
            str_contains($this->action, 'backup') => 'Sao lưu dữ liệu',
            str_contains($this->action, 'system_settings') => 'Cài đặt hệ thống',
            default => 'Hệ thống',
        };
    }

    public function actionTypeLabel(): string
    {
        if ($this->hanh_dong) {
            return $this->hanh_dong;
        }

        return match (true) {
            str_contains($this->action, 'created') || str_contains($this->action, 'store') => 'Thêm mới',
            str_contains($this->action, 'updated') || str_contains($this->action, 'changed') => 'Cập nhật',
            str_contains($this->action, 'deleted') => 'Xóa bỏ',
            str_contains($this->action, 'reset') => 'Reset mật khẩu',
            str_contains($this->action, 'login') => 'Đăng nhập',
            str_contains($this->action, 'logout') => 'Đăng xuất',
            str_contains($this->action, 'backup') => 'Sao lưu',
            default => 'Khác',
        };
    }

    public function roleLabel(): string
    {
        $role = $this->role ?: $this->user?->role;

        return match ($role) {
            'admin' => 'Quản trị viên',
            'staff' => 'Nhân sự quản trị',
            'teacher' => 'Giáo viên',
            'student' => 'Học sinh',
            'parent' => 'Phụ huynh',
            default => $role ?: 'Hệ thống',
        };
    }

    public function changeContent(): string
    {
        return $this->noi_dung_thay_doi ?: ($this->description ?: '-');
    }

    public function actionBadgeClass(): string
    {
        return match ($this->actionTypeLabel()) {
            'Xóa bỏ' => 'audit-action-delete',
            'Thêm mới' => 'audit-action-create',
            'Cập nhật' => 'audit-action-update',
            'Đăng nhập' => 'audit-action-login',
            default => 'audit-action-default',
        };
    }

    public static function moduleLabelFor(?string $entityType, ?string $action = null): string
    {
        $basename = $entityType ? class_basename($entityType) : '';
        $action = (string) $action;

        return match (true) {
            str_contains($action, 'score') || $basename === 'ScoreHeader' || $basename === 'ScoreDetail' || $basename === 'ScoreColumn' => 'Điểm số',
            str_contains($action, 'attendance') || $basename === 'AttendanceRecord' => 'Điểm danh',
            str_contains($action, 'conduct') || $basename === 'Conduct' => 'Hạnh kiểm',
            str_contains($action, 'subject') || $basename === 'Subject' => 'Môn học',
            str_contains($action, 'class') || $basename === 'SchoolClass' => 'Lớp học',
            str_contains($action, 'assignment') || $basename === 'TeachingAssignment' => 'Phân công',
            str_contains($action, 'admin_user') || str_contains($action, 'rbac') || $basename === 'User' || $basename === 'RbacRole' => 'Tài khoản',
            str_contains($action, 'teacher') || $basename === 'Teacher' => 'Tài khoản',
            str_contains($action, 'student') || $basename === 'Student' => 'Tài khoản',
            str_contains($action, 'parent') || $basename === 'ParentProfile' => 'Tài khoản',
            str_contains($action, 'login') || str_contains($action, 'logout') => 'Tài khoản',
            str_contains($action, 'timetable') || $basename === 'TimetableEntry' => 'Thời khóa biểu',
            str_contains($action, 'exam') || $basename === 'ExamSchedule' => 'Lịch kiểm tra',
            str_contains($action, 'document') || $basename === 'LearningDocument' => 'Tài liệu',
            str_contains($action, 'post') || str_contains($action, 'announcement') || $basename === 'SchoolPost' => 'Thông báo',
            str_contains($action, 'event') || $basename === 'SchoolEvent' => 'Sự kiện',
            str_contains($action, 'room') || $basename === 'Room' => 'Phòng học',
            str_contains($action, 'department') || $basename === 'TeacherDepartment' => 'Tổ chuyên môn',
            default => $basename ?: 'Hệ thống',
        };
    }
}
