<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\RbacRole;
use App\Models\User;
use App\Services\AdminProtectionService;
use App\Support\AuditLogger;
use App\Support\Rbac\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status', 'all'),
            'role_id' => $request->query('role_id', 'all'),
        ];

        $users = User::with('rbacRoles.permissions')
            ->whereIn('role', ['admin', 'staff'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('username', 'like', '%' . $keyword . '%')
                        ->orWhere('full_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhereHas('rbacRoles', fn ($role) => $role->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['role_id'] !== 'all', fn ($query) => $query->whereHas('rbacRoles', fn ($role) => $role->whereKey($filters['role_id'])))
            ->orderByDesc('is_super_admin')
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();

        $roles = $this->assignableRoles();
        $filterRoles = RbacRole::where('is_active', true)->orderBy('name')->get();

        return view('admin_users.index', compact('users', 'roles', 'filterRoles', 'filters'));
    }

    public function store(StoreAdminUserRequest $request)
    {
        $data = $request->validated();
        $roleIds = $this->sanitizeRoleIds($data['role_ids'] ?? []);

        if (empty($roleIds)) {
            return back()->withInput()->withErrors(['role_ids' => 'Vui lòng chọn ít nhất một vai trò quản trị hợp lệ.']);
        }

        try {
            DB::transaction(function () use ($data, $roleIds) {
                $user = User::create([
                    'username' => $data['username'],
                    'full_name' => $data['full_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'role' => 'staff',
                    'password_hash' => Hash::make('12345678'),
                    'force_change_password' => true,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'is_super_admin' => false,
                ]);

                $user->rbacRoles()->sync($roleIds);

                AuditLogger::log('admin_user_created', User::class, (string) $user->getKey(), 'Tạo tài khoản quản trị phụ ' . $user->username);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['error' => 'Không thể tạo tài khoản quản trị. Vui lòng kiểm tra lại dữ liệu.']);
        }

        return redirect()->route('admin-users.index')->with('success', 'Đã tạo tài khoản quản trị. Mật khẩu mặc định là 12345678.');
    }

    public function update(UpdateAdminUserRequest $request, User $adminUser)
    {
        $protection = AdminProtectionService::canManageUser($request->user(), $adminUser);
        if (! $protection['allowed']) {
            return back()->withErrors(['error' => $protection['message']]);
        }

        $data = $request->validated();
        $roleIds = $this->sanitizeRoleIds($data['role_ids'] ?? []);

        if (empty($roleIds) && ! $adminUser->isSuperAdmin()) {
            return back()->withInput()->withErrors(['role_ids' => 'Vui lòng chọn ít nhất một vai trò quản trị hợp lệ.']);
        }

        try {
            DB::transaction(function () use ($adminUser, $data, $roleIds) {
                $adminUser->update([
                    'username' => $data['username'],
                    'full_name' => $data['full_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'role' => $adminUser->isSuperAdmin() ? 'admin' : 'staff',
                    'is_active' => $adminUser->isSuperAdmin() ? true : (bool) ($data['is_active'] ?? true),
                ]);

                if (! $adminUser->isSuperAdmin()) {
                    $adminUser->rbacRoles()->sync($roleIds);
                }

                AuditLogger::log('admin_user_updated', User::class, (string) $adminUser->getKey(), 'Cập nhật tài khoản quản trị ' . $adminUser->username);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['error' => 'Không thể cập nhật tài khoản quản trị.']);
        }

        return redirect()->route('admin-users.index')->with('success', 'Đã cập nhật tài khoản quản trị.');
    }

    public function toggle(Request $request, User $adminUser)
    {
        $protection = AdminProtectionService::canManageUser($request->user(), $adminUser);
        if (! $protection['allowed']) {
            return back()->withErrors(['error' => $protection['message']]);
        }

        $validation = AdminProtectionService::validateAdminChange($adminUser, ['is_active' => ! $adminUser->is_active]);
        if (! $validation['allowed']) {
            return back()->withErrors(['error' => $validation['message']]);
        }

        DB::transaction(function () use ($adminUser) {
            $adminUser->update(['is_active' => ! $adminUser->is_active]);
            AuditLogger::log('admin_user_status_changed', User::class, (string) $adminUser->getKey(), 'Đổi trạng thái tài khoản quản trị ' . $adminUser->username);
        });

        return back()->with('success', 'Đã cập nhật trạng thái tài khoản.');
    }

    public function resetPassword(Request $request, User $adminUser)
    {
        $protection = AdminProtectionService::canManageUser($request->user(), $adminUser);
        if (! $protection['allowed']) {
            return back()->withErrors(['error' => $protection['message']]);
        }

        DB::transaction(function () use ($adminUser) {
            $adminUser->update([
                'password_hash' => Hash::make('12345678'),
                'force_change_password' => true,
            ]);

            AuditLogger::log('admin_user_password_reset', User::class, (string) $adminUser->getKey(), 'Đặt lại mật khẩu tài khoản quản trị ' . $adminUser->username);
        });

        return back()->with('success', 'Đã đặt lại mật khẩu về 12345678.');
    }

    public function destroy(Request $request, User $adminUser)
    {
        $protection = AdminProtectionService::canManageUser($request->user(), $adminUser);
        if (! $protection['allowed']) {
            return back()->withErrors(['error' => $protection['message']]);
        }

        $validation = AdminProtectionService::validateAdminDeletion($adminUser);
        if (! $validation['allowed']) {
            return back()->withErrors(['error' => $validation['message']]);
        }

        DB::transaction(function () use ($adminUser) {
            $adminUser->rbacRoles()->detach();
            $adminUser->delete();
            AuditLogger::log('admin_user_deleted', User::class, (string) $adminUser->getKey(), 'Xóa tài khoản quản trị ' . $adminUser->username);
        });

        return redirect()->route('admin-users.index')->with('success', 'Đã xóa tài khoản quản trị.');
    }

    private function assignableRoles()
    {
        return RbacRole::where('is_active', true)
            ->where(function ($query) {
                $query->whereIn('key', PermissionCatalog::adminRoleKeys())
                    ->orWhere('is_system', false);
            })
            ->whereNotIn('key', ['super_admin', 'admin', 'teacher', 'homeroom', 'student', 'parent'])
            ->orderBy('name')
            ->get();
    }

    private function sanitizeRoleIds(array $roleIds): array
    {
        return $this->assignableRoles()
            ->whereIn('id', $roleIds)
            ->pluck('id')
            ->values()
            ->all();
    }
}
