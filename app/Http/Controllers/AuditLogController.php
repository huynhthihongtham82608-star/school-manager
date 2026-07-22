<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    private const MODULES = [
        'Điểm số',
        'Điểm danh',
        'Hạnh kiểm',
        'Môn học',
        'Lớp học',
        'Phân công',
        'Tài khoản',
    ];

    private const ACTIONS = [
        'Đăng nhập',
        'Thêm mới',
        'Cập nhật',
        'Xóa bỏ',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'user_id', 'module', 'action', 'date_from', 'date_to']);
        $users = Schema::hasTable('users')
            ? User::query()
                ->whereIn('role', ['admin', 'staff', 'teacher'])
                ->orderBy('full_name')
                ->orderBy('username')
                ->get()
            : collect();
        $modules = collect(self::MODULES);
        $actions = collect(self::ACTIONS);

        $logs = collect();

        if (Schema::hasTable('audit_logs')) {
            $query = AuditLog::with('user')->latest('created_at');

            $query->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search->where('action', 'like', '%' . $keyword . '%')
                        ->orWhere('hanh_dong', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhere('noi_dung_thay_doi', 'like', '%' . $keyword . '%')
                        ->orWhere('entity_type', 'like', '%' . $keyword . '%')
                        ->orWhere('module', 'like', '%' . $keyword . '%')
                        ->orWhere('ip_address', 'like', '%' . $keyword . '%')
                        ->orWhereHas('user', function ($userQuery) use ($keyword) {
                            $userQuery->where('username', 'like', '%' . $keyword . '%')
                                ->orWhere('full_name', 'like', '%' . $keyword . '%');
                        });
                });
            });

            $query->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId));
            $query->when($filters['module'] ?? null, fn ($query, $module) => $this->applyModuleFilter($query, $module));
            $query->when($filters['action'] ?? null, fn ($query, $action) => $this->applyActionFilter($query, $action));
            $query->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date));
            $query->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

            $logs = $query->paginate(20)->withQueryString();
        }

        return view('audit_logs.index', compact('logs', 'users', 'modules', 'actions', 'filters'));
    }

    private function applyModuleFilter($query, string $module)
    {
        $keywords = match ($module) {
            'Điểm số' => ['score', 'ScoreHeader', 'ScoreDetail', 'ScoreColumn'],
            'Điểm danh' => ['attendance', 'AttendanceRecord'],
            'Hạnh kiểm' => ['conduct', 'Conduct'],
            'Môn học' => ['subject', 'Subject'],
            'Lớp học' => ['class', 'SchoolClass'],
            'Phân công' => ['assignment', 'TeachingAssignment'],
            'Tài khoản' => ['user', 'admin_user', 'rbac', 'teacher', 'student', 'parent', 'login', 'logout', 'User', 'Teacher', 'Student', 'ParentProfile', 'RbacRole'],
            default => [$module],
        };

        return $query->where(function ($filter) use ($module, $keywords) {
            $filter->where('module', $module);

            foreach ($keywords as $keyword) {
                $filter->orWhere('action', 'like', '%' . $keyword . '%')
                    ->orWhere('entity_type', 'like', '%' . $keyword . '%');
            }
        });
    }

    private function applyActionFilter($query, string $action)
    {
        $keywords = match ($action) {
            'Đăng nhập' => ['login'],
            'Thêm mới' => ['created', 'store', 'imported', 'assigned'],
            'Cập nhật' => ['updated', 'changed', 'activated', 'locked', 'archived', 'reset', 'approved', 'rejected', 'cloned'],
            'Xóa bỏ' => ['deleted', 'unassigned', 'destroy'],
            default => [$action],
        };

        return $query->where(function ($filter) use ($action, $keywords) {
            $filter->where('hanh_dong', $action);

            foreach ($keywords as $keyword) {
                $filter->orWhere('action', 'like', '%' . $keyword . '%');
            }
        });
    }
}
