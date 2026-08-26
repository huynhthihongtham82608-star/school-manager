<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rbac_permissions') || ! Schema::hasTable('rbac_roles') || ! Schema::hasTable('rbac_permission_role')) {
            return;
        }

        $now = now();
        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            $existingId = DB::table('rbac_permissions')->where('key', $permission['key'])->value('id');

            if ($existingId) {
                DB::table('rbac_permissions')->where('id', $existingId)->update([
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('rbac_permissions')->insert([
                'id' => (string) Str::orderedUuid(),
                'key' => $permission['key'],
                'name' => $permission['name'],
                'group' => $permission['group'],
                'description' => $permission['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIdsByKey = DB::table('rbac_permissions')
            ->whereIn('key', array_column($permissions, 'key'))
            ->pluck('id', 'key');

        foreach ($this->rolePermissionMap() as $roleKey => $permissionKeys) {
            $roleId = DB::table('rbac_roles')->where('key', $roleKey)->value('id');
            if (! $roleId) {
                continue;
            }

            foreach ($permissionKeys as $permissionKey) {
                $permissionId = $permissionIdsByKey[$permissionKey] ?? null;
                if (! $permissionId) {
                    continue;
                }

                DB::table('rbac_permission_role')->updateOrInsert(
                    ['role_id' => (string) $roleId, 'permission_id' => (string) $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $this->refreshUserPermissionSnapshots();
    }

    public function down(): void
    {
        // Data-only permission sync: keep permissions in place to avoid breaking assigned roles on rollback.
    }

    private function permissions(): array
    {
        return [
            ['key' => 'view_tuition', 'name' => 'Xem học phí', 'group' => 'Học phí', 'description' => 'Xem danh sách, trạng thái và lịch sử học phí.'],
            ['key' => 'collect_tuition', 'name' => 'Thu học phí', 'group' => 'Học phí', 'description' => 'Cập nhật trạng thái thu học phí cho học sinh.'],
            ['key' => 'setup_tuition_fees', 'name' => 'Cấu hình mức thu học phí', 'group' => 'Học phí', 'description' => 'Thiết lập mức thu và tham số học phí.'],
            ['key' => 'view_rewards', 'name' => 'Xem khen thưởng', 'group' => 'Khen thưởng', 'description' => 'Tra cứu danh sách khen thưởng học sinh.'],
            ['key' => 'create_rewards', 'name' => 'Lập khen thưởng', 'group' => 'Khen thưởng', 'description' => 'Tạo và cập nhật hồ sơ khen thưởng.'],
            ['key' => 'delete_rewards', 'name' => 'Xóa khen thưởng', 'group' => 'Khen thưởng', 'description' => 'Xóa hồ sơ khen thưởng khi được phân quyền.'],
            ['key' => 'view_leave_requests', 'name' => 'Xem đơn xin nghỉ', 'group' => 'Đơn từ', 'description' => 'Xem danh sách đơn xin nghỉ của phụ huynh, học sinh.'],
            ['key' => 'approve_leave_requests', 'name' => 'Duyệt đơn xin nghỉ', 'group' => 'Đơn từ', 'description' => 'Phê duyệt hoặc từ chối đơn xin nghỉ.'],
            ['key' => 'access_chatbot', 'name' => 'Truy cập chatbot', 'group' => 'Chatbot', 'description' => 'Sử dụng chatbot hỗ trợ trong hệ thống.'],
            ['key' => 'configure_gemini_api', 'name' => 'Cấu hình Gemini API', 'group' => 'Chatbot', 'description' => 'Quản lý tham số kết nối Gemini API.'],
            ['key' => 'view_users', 'name' => 'Xem người dùng', 'group' => 'Người dùng', 'description' => 'Xem dữ liệu người dùng phẳng: admin, giáo viên, học sinh, phụ huynh.'],
            ['key' => 'create_users', 'name' => 'Tạo người dùng', 'group' => 'Người dùng', 'description' => 'Tạo mới tài khoản và hồ sơ người dùng.'],
            ['key' => 'edit_users', 'name' => 'Sửa người dùng', 'group' => 'Người dùng', 'description' => 'Cập nhật thông tin, trạng thái và khóa tài khoản người dùng.'],
            ['key' => 'delete_users', 'name' => 'Xóa người dùng', 'group' => 'Người dùng', 'description' => 'Xóa người dùng khi không phát sinh dữ liệu nghiệp vụ.'],
            ['key' => 'view_scores', 'name' => 'Xem điểm', 'group' => 'Điểm số', 'description' => 'Xem bảng điểm, học bạ và báo cáo điểm.'],
            ['key' => 'input_scores', 'name' => 'Nhập điểm', 'group' => 'Điểm số', 'description' => 'Nhập và cập nhật điểm theo cột điểm đang mở.'],
            ['key' => 'lock_score_window', 'name' => 'Khóa cửa sổ nhập điểm', 'group' => 'Điểm số', 'description' => 'Cấu hình cột điểm, hệ số và thời gian khóa nhập điểm.'],
        ];
    }

    private function rolePermissionMap(): array
    {
        $all = array_column($this->permissions(), 'key');

        return [
            'super_admin' => $all,
            'admin' => $all,
            'staff' => $all,
            'school_leadership' => $all,
            'academic_officer' => $all,
            'system_technician' => ['access_chatbot', 'configure_gemini_api', 'view_users', 'view_scores'],
            'teacher' => ['access_chatbot', 'view_scores', 'input_scores', 'view_rewards', 'create_rewards', 'view_leave_requests', 'approve_leave_requests', 'view_tuition'],
            'homeroom' => ['access_chatbot', 'view_scores', 'input_scores', 'view_rewards', 'create_rewards', 'view_leave_requests', 'approve_leave_requests', 'view_tuition'],
            'student' => ['access_chatbot', 'view_scores'],
            'parent' => ['access_chatbot', 'view_scores', 'view_tuition', 'view_leave_requests'],
        ];
    }

    private function refreshUserPermissionSnapshots(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable('rbac_role_user')
            || ! Schema::hasColumn('users', 'permissions_map')
            || ! Schema::hasColumn('users', 'rbac_role_ids')
        ) {
            return;
        }

        DB::table('users')->select('id')->orderBy('id')->chunk(100, function ($users): void {
            foreach ($users as $user) {
                $roleIds = DB::table('rbac_role_user')
                    ->where('user_id', $user->id)
                    ->pluck('role_id')
                    ->map(fn ($roleId) => (string) $roleId)
                    ->unique()
                    ->values();

                $storedRoleIds = json_decode((string) DB::table('users')->where('id', $user->id)->value('rbac_role_ids'), true);
                if (is_array($storedRoleIds)) {
                    $roleIds = $roleIds
                        ->merge(collect($storedRoleIds)->map(fn ($roleId) => (string) $roleId))
                        ->unique()
                        ->values();
                }

                foreach ($roleIds as $roleId) {
                    if (! DB::table('rbac_roles')->where('id', $roleId)->exists()) {
                        continue;
                    }

                    DB::table('rbac_role_user')->updateOrInsert(
                        ['user_id' => (string) $user->id, 'role_id' => (string) $roleId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                $permissionKeys = DB::table('rbac_roles')
                    ->join('rbac_permission_role', 'rbac_permission_role.role_id', '=', 'rbac_roles.id')
                    ->join('rbac_permissions', 'rbac_permissions.id', '=', 'rbac_permission_role.permission_id')
                    ->whereIn('rbac_roles.id', $roleIds->all())
                    ->where('rbac_roles.is_active', true)
                    ->pluck('rbac_permissions.key')
                    ->map(fn ($key) => (string) $key)
                    ->unique()
                    ->sort()
                    ->values();

                DB::table('users')->where('id', $user->id)->update([
                    'rbac_role_ids' => json_encode($roleIds->all(), JSON_UNESCAPED_UNICODE),
                    'permissions_map' => json_encode($permissionKeys->all(), JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        });
    }
};
