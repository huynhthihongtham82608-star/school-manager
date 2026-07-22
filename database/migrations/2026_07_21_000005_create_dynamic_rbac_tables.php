<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable()->after('username');
            }

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->after('full_name');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('is_active');
            }
        });

        Schema::create('rbac_roles', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('rbac_permissions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('key', 120)->unique();
            $table->string('name');
            $table->string('group', 120)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('rbac_permission_role', function (Blueprint $table) {
            $table->char('role_id', 36);
            $table->char('permission_id', 36);
            $table->timestamps();
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('rbac_roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('rbac_permissions')->cascadeOnDelete();
        });

        Schema::create('rbac_role_user', function (Blueprint $table) {
            $table->string('user_id', 50);
            $table->char('role_id', 36);
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
            $table->index('user_id');
            $table->foreign('role_id')->references('id')->on('rbac_roles')->cascadeOnDelete();
        });

        $now = now();
        $permissions = $this->permissions();
        $permissionIds = [];

        foreach ($permissions as $permission) {
            $id = (string) Str::orderedUuid();
            $permissionIds[$permission['key']] = $id;
            DB::table('rbac_permissions')->updateOrInsert(
                ['key' => $permission['key']],
                [
                    'id' => DB::table('rbac_permissions')->where('key', $permission['key'])->value('id') ?: $id,
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $permissionIds[$permission['key']] = DB::table('rbac_permissions')->where('key', $permission['key'])->value('id');
        }

        $allPermissionKeys = array_keys($permissionIds);
        $rolePermissions = [
            'super_admin' => $allPermissionKeys,
            'admin' => $allPermissionKeys,
            'staff' => [
                'dashboard.view',
                'academic.manage',
                'classes.manage',
                'students.manage',
                'teachers.manage',
                'parents.manage',
                'subjects.manage',
                'rooms.manage',
                'departments.manage',
                'assignments.manage',
                'timetable.manage',
                'exams.manage',
                'scores.manage',
                'attendance.manage',
                'conduct.manage',
                'content.manage',
                'documents.manage',
                'messages.manage',
                'reports.view',
            ],
            'school_leadership' => [
                'dashboard.view',
                'academic.manage',
                'classes.manage',
                'students.manage',
                'teachers.manage',
                'parents.manage',
                'assignments.manage',
                'timetable.manage',
                'exams.manage',
                'scores.view',
                'attendance.view',
                'conduct.view',
                'content.manage',
                'messages.manage',
                'reports.view',
                'audit_logs.view',
            ],
            'academic_officer' => [
                'dashboard.view',
                'academic.manage',
                'classes.manage',
                'students.manage',
                'teachers.manage',
                'parents.manage',
                'subjects.manage',
                'rooms.manage',
                'departments.manage',
                'assignments.manage',
                'timetable.manage',
                'exams.manage',
                'scores.manage',
                'attendance.manage',
                'conduct.manage',
                'documents.manage',
                'reports.view',
            ],
            'system_technician' => [
                'dashboard.view',
                'system.settings',
                'backups.manage',
                'audit_logs.view',
            ],
            'teacher' => [],
            'homeroom' => [],
            'student' => [],
            'parent' => [],
        ];

        $roles = [
            ['key' => 'super_admin', 'name' => 'Quản trị tối cao', 'description' => 'Tài khoản gốc được bảo vệ của hệ thống.', 'is_system' => true],
            ['key' => 'admin', 'name' => 'Quản trị viên', 'description' => 'Vai trò hệ thống tương thích với tài khoản Admin cũ.', 'is_system' => true],
            ['key' => 'staff', 'name' => 'Cán bộ quản trị', 'description' => 'Vai trò hệ thống tương thích với tài khoản staff cũ.', 'is_system' => true],
            ['key' => 'teacher', 'name' => 'Giáo viên bộ môn', 'description' => 'Vai trò hệ thống cũ, không xóa được.', 'is_system' => true],
            ['key' => 'homeroom', 'name' => 'Giáo viên chủ nhiệm', 'description' => 'Vai trò nghiệp vụ suy ra từ giáo viên chủ nhiệm.', 'is_system' => true],
            ['key' => 'student', 'name' => 'Học sinh', 'description' => 'Vai trò hệ thống cũ, không xóa được.', 'is_system' => true],
            ['key' => 'parent', 'name' => 'Phụ huynh', 'description' => 'Vai trò hệ thống cũ, không xóa được.', 'is_system' => true],
            ['key' => 'school_leadership', 'name' => 'Ban Giám Hiệu', 'description' => 'Vai trò quản trị động dành cho lãnh đạo nhà trường.', 'is_system' => false],
            ['key' => 'academic_officer', 'name' => 'Cán bộ Giáo vụ', 'description' => 'Vai trò quản trị động dành cho bộ phận giáo vụ.', 'is_system' => false],
            ['key' => 'system_technician', 'name' => 'Kỹ thuật viên hệ thống', 'description' => 'Vai trò quản trị động dành cho bộ phận kỹ thuật.', 'is_system' => false],
        ];

        $roleIds = [];
        foreach ($roles as $role) {
            $id = (string) Str::orderedUuid();
            DB::table('rbac_roles')->updateOrInsert(
                ['key' => $role['key']],
                [
                    'id' => DB::table('rbac_roles')->where('key', $role['key'])->value('id') ?: $id,
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'is_system' => $role['is_system'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $roleIds[$role['key']] = DB::table('rbac_roles')->where('key', $role['key'])->value('id');
        }

        foreach ($rolePermissions as $roleKey => $keys) {
            $roleId = $roleIds[$roleKey] ?? null;
            if (! $roleId) {
                continue;
            }

            foreach ($keys as $permissionKey) {
                $permissionId = $permissionIds[$permissionKey] ?? null;
                if (! $permissionId) {
                    continue;
                }

                DB::table('rbac_permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $superAdmin = DB::table('users')
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderByRaw("case when username = 'admin' then 0 else 1 end")
            ->orderBy('created_at')
            ->first();

        if ($superAdmin) {
            DB::table('users')
                ->where('id', $superAdmin->id)
                ->update([
                    'is_super_admin' => true,
                    'full_name' => $superAdmin->full_name ?? 'Quản trị tối cao',
                    'updated_at' => $now,
                ]);

            DB::table('rbac_role_user')->updateOrInsert(
                ['user_id' => (string) $superAdmin->id, 'role_id' => $roleIds['super_admin']],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('users')
            ->where('role', 'admin')
            ->where('is_super_admin', false)
            ->orderBy('id')
            ->get()
            ->each(function ($user) use ($roleIds, $now) {
                DB::table('rbac_role_user')->updateOrInsert(
                    ['user_id' => (string) $user->id, 'role_id' => $roleIds['admin']],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            });

        DB::table('users')
            ->where('role', 'staff')
            ->orderBy('id')
            ->get()
            ->each(function ($user) use ($roleIds, $now) {
                DB::table('rbac_role_user')->updateOrInsert(
                    ['user_id' => (string) $user->id, 'role_id' => $roleIds['staff']],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            });

        if (! DB::table('users')->where('role', 'admin')->exists()) {
            $adminId = (string) Str::orderedUuid();
            DB::table('users')->insert([
                'id' => $adminId,
                'username' => 'admin',
                'full_name' => 'Quản trị tối cao',
                'password_hash' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'is_super_admin' => true,
                'force_change_password' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('rbac_role_user')->insert([
                'user_id' => $adminId,
                'role_id' => $roleIds['super_admin'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_role_user');
        Schema::dropIfExists('rbac_permission_role');
        Schema::dropIfExists('rbac_permissions');
        Schema::dropIfExists('rbac_roles');

        Schema::table('users', function (Blueprint $table) {
            foreach (['is_super_admin', 'phone', 'email', 'full_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function permissions(): array
    {
        return [
            ['key' => 'dashboard.view', 'name' => 'Xem bảng điều khiển', 'group' => 'Tổng quan'],
            ['key' => 'academic.manage', 'name' => 'Quản lý năm học và học kỳ', 'group' => 'Học vụ'],
            ['key' => 'classes.manage', 'name' => 'Quản lý lớp học', 'group' => 'Học vụ'],
            ['key' => 'students.manage', 'name' => 'Quản lý học sinh', 'group' => 'Người dùng'],
            ['key' => 'teachers.manage', 'name' => 'Quản lý giáo viên', 'group' => 'Người dùng'],
            ['key' => 'parents.manage', 'name' => 'Quản lý phụ huynh', 'group' => 'Người dùng'],
            ['key' => 'subjects.manage', 'name' => 'Quản lý môn học', 'group' => 'Học vụ'],
            ['key' => 'rooms.manage', 'name' => 'Quản lý phòng học', 'group' => 'Học vụ'],
            ['key' => 'departments.manage', 'name' => 'Quản lý tổ chuyên môn', 'group' => 'Học vụ'],
            ['key' => 'assignments.manage', 'name' => 'Phân công giảng dạy', 'group' => 'Học vụ'],
            ['key' => 'timetable.manage', 'name' => 'Quản lý thời khóa biểu', 'group' => 'Học vụ'],
            ['key' => 'exams.manage', 'name' => 'Quản lý lịch kiểm tra', 'group' => 'Học vụ'],
            ['key' => 'scores.view', 'name' => 'Xem điểm số', 'group' => 'Đánh giá'],
            ['key' => 'scores.manage', 'name' => 'Quản lý và nhập điểm', 'group' => 'Đánh giá'],
            ['key' => 'attendance.view', 'name' => 'Xem điểm danh', 'group' => 'Đánh giá'],
            ['key' => 'attendance.manage', 'name' => 'Quản lý điểm danh', 'group' => 'Đánh giá'],
            ['key' => 'conduct.view', 'name' => 'Xem hạnh kiểm', 'group' => 'Đánh giá'],
            ['key' => 'conduct.manage', 'name' => 'Quản lý hạnh kiểm', 'group' => 'Đánh giá'],
            ['key' => 'content.manage', 'name' => 'Quản lý nội dung hệ thống', 'group' => 'Nội dung'],
            ['key' => 'documents.manage', 'name' => 'Quản lý tài liệu học tập', 'group' => 'Nội dung'],
            ['key' => 'messages.manage', 'name' => 'Quản lý tin nhắn nội bộ', 'group' => 'Giao tiếp'],
            ['key' => 'reports.view', 'name' => 'Xem báo cáo thống kê', 'group' => 'Báo cáo'],
            ['key' => 'system.settings', 'name' => 'Cài đặt hệ thống', 'group' => 'Hệ thống'],
            ['key' => 'backups.manage', 'name' => 'Sao lưu và khôi phục dữ liệu', 'group' => 'Hệ thống'],
            ['key' => 'audit_logs.view', 'name' => 'Xem nhật ký hoạt động', 'group' => 'Hệ thống'],
            ['key' => 'manage_admin_accounts', 'name' => 'Quản lý tài khoản quản trị', 'group' => 'Phân quyền'],
            ['key' => 'manage_roles', 'name' => 'Quản lý vai trò và quyền', 'group' => 'Phân quyền'],
        ];
    }
};
