<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRbacRoleRequest;
use App\Http\Requests\UpdateRbacRoleRequest;
use App\Models\RbacPermission;
use App\Models\RbacRole;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RbacRoleController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status', 'all'),
        ];

        $roles = RbacRole::with(['permissions', 'users'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('key', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                });
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $permissionGroups = RbacPermission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        return view('rbac_roles.index', compact('roles', 'permissionGroups', 'filters'));
    }

    public function store(StoreRbacRoleRequest $request)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $role = RbacRole::create([
                    'key' => $data['key'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_system' => false,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

                $role->permissions()->sync($data['permission_ids'] ?? []);

                AuditLogger::log('rbac_role_created', RbacRole::class, (string) $role->getKey(), 'Tạo vai trò ' . $role->name);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['error' => 'Không thể tạo vai trò. Vui lòng kiểm tra lại dữ liệu.']);
        }

        return redirect()->route('rbac-roles.index')->with('success', 'Đã tạo vai trò.');
    }

    public function update(UpdateRbacRoleRequest $request, RbacRole $rbacRole)
    {
        if ($rbacRole->isLocked()) {
            return back()->withErrors(['error' => 'Vai trò hệ thống không được phép chỉnh sửa.']);
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($rbacRole, $data) {
                $rbacRole->update([
                    'key' => $data['key'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

                $rbacRole->permissions()->sync($data['permission_ids'] ?? []);

                AuditLogger::log('rbac_role_updated', RbacRole::class, (string) $rbacRole->getKey(), 'Cập nhật vai trò ' . $rbacRole->name);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['error' => 'Không thể cập nhật vai trò.']);
        }

        return redirect()->route('rbac-roles.index')->with('success', 'Đã cập nhật vai trò.');
    }

    public function toggle(RbacRole $rbacRole)
    {
        if ($rbacRole->isLocked()) {
            return back()->withErrors(['error' => 'Vai trò hệ thống không được phép bật/tắt.']);
        }

        DB::transaction(function () use ($rbacRole) {
            $rbacRole->update(['is_active' => ! $rbacRole->is_active]);
            AuditLogger::log('rbac_role_status_changed', RbacRole::class, (string) $rbacRole->getKey(), 'Đổi trạng thái vai trò ' . $rbacRole->name);
        });

        return back()->with('success', 'Đã cập nhật trạng thái vai trò.');
    }

    public function destroy(RbacRole $rbacRole)
    {
        if ($rbacRole->isLocked()) {
            return back()->withErrors(['error' => 'Vai trò hệ thống không được phép xóa.']);
        }

        if ($rbacRole->users()->exists()) {
            return back()->withErrors(['error' => 'Không thể xóa vai trò đang được gán cho tài khoản quản trị.']);
        }

        DB::transaction(function () use ($rbacRole) {
            $rbacRole->permissions()->detach();
            $rbacRole->delete();
            AuditLogger::log('rbac_role_deleted', RbacRole::class, (string) $rbacRole->getKey(), 'Xóa vai trò ' . $rbacRole->name);
        });

        return redirect()->route('rbac-roles.index')->with('success', 'Đã xóa vai trò.');
    }
}
