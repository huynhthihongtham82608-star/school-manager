<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $rbacTables = [
        'rbac_permission_role',
        'rbac_role_user',
        'rbac_permissions',
        'rbac_roles',
    ];

    private array $queueTables = [
        'failed_jobs',
        'jobs',
        'job_batches',
    ];

    private array $cacheTables = [
        'cache',
        'cache_locks',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->prepareRbacMatrix();
            $this->prepareQueueLogs();
            $this->prepareSystemCache();
            $this->mergeRbac();
            $this->mergeQueueTables();
            $this->mergeCacheTables();
            $this->dropSourceTables();
            $this->createRoutingTriggers();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This infrastructure compression migration is intentionally irreversible. Restore from backup if rollback is required.');
    }

    private function prepareRbacMatrix(): void
    {
        if (! Schema::hasTable('rbac_matrix')) {
            Schema::create('rbac_matrix', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('record_type', 30)->index();
                $table->string('source_id', 191)->nullable()->index();
                $table->string('role_id', 50)->nullable()->index();
                $table->string('permission_id', 50)->nullable()->index();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('key', 100)->nullable()->index();
                $table->string('name')->nullable();
                $table->string('group')->nullable()->index();
                $table->text('description')->nullable();
                $table->longText('permissions_map')->nullable();
                $table->longText('roles_map')->nullable();
                $table->boolean('is_system')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        } elseif (Schema::hasColumn('rbac_matrix', 'source_id')) {
            DB::statement('ALTER TABLE rbac_matrix MODIFY source_id VARCHAR(191) NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'rbac_role_ids')) {
                $table->longText('rbac_role_ids')->nullable()->after('is_homeroom');
            }

            if (! Schema::hasColumn('users', 'permissions_map')) {
                $table->longText('permissions_map')->nullable()->after('rbac_role_ids');
            }
        });
    }

    private function prepareQueueLogs(): void
    {
        if (! Schema::hasTable('queue_logs')) {
            Schema::create('queue_logs', function (Blueprint $table) {
                $table->id();
                $table->string('record_type', 30)->default('queue_job')->index();
                $table->string('source_id')->nullable()->index();
                $table->string('uuid')->nullable()->index();
                $table->text('connection')->nullable();
                $table->text('queue')->nullable();
                $table->longText('payload')->nullable();
                $table->unsignedTinyInteger('attempts')->nullable();
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at')->nullable();
                $table->unsignedInteger('created_at_int')->nullable();
                $table->longText('exception')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('batch_uuid')->nullable()->index();
                $table->string('name')->nullable();
                $table->integer('total_jobs')->nullable();
                $table->integer('pending_jobs')->nullable();
                $table->integer('failed_jobs_count')->nullable();
                $table->longText('failed_job_ids')->nullable();
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function prepareSystemCache(): void
    {
        if (! Schema::hasTable('system_cache')) {
            Schema::create('system_cache', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('record_type', 30)->index();
                $table->string('cache_key')->index();
                $table->mediumText('value')->nullable();
                $table->string('owner')->nullable();
                $table->integer('expiration');
                $table->timestamps();
            });
        }
    }

    private function mergeRbac(): void
    {
        $permissions = Schema::hasTable('rbac_permissions')
            ? DB::table('rbac_permissions')->get()->keyBy('id')
            : collect();
        $roles = Schema::hasTable('rbac_roles')
            ? DB::table('rbac_roles')->get()->keyBy('id')
            : collect();
        $rolePermissions = Schema::hasTable('rbac_permission_role')
            ? DB::table('rbac_permission_role')->get()->groupBy('role_id')
            : collect();
        $userRoles = Schema::hasTable('rbac_role_user')
            ? DB::table('rbac_role_user')->get()->groupBy('user_id')
            : collect();

        foreach ($permissions as $permission) {
            DB::table('rbac_matrix')->updateOrInsert(
                ['id' => (string) $permission->id],
                [
                    'record_type' => 'permission',
                    'source_id' => (string) $permission->id,
                    'key' => $permission->key,
                    'name' => $permission->name,
                    'group' => $permission->group,
                    'description' => $permission->description,
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $permission->created_at ?? now(),
                    'updated_at' => $permission->updated_at ?? now(),
                ]
            );
        }

        foreach ($roles as $role) {
            $permissionIds = ($rolePermissions->get($role->id) ?? collect())
                ->pluck('permission_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();
            $permissionKeys = $permissionIds
                ->map(fn (string $id) => $permissions[$id]->key ?? null)
                ->filter()
                ->unique()
                ->values();

            DB::table('rbac_matrix')->updateOrInsert(
                ['id' => (string) $role->id],
                [
                    'record_type' => 'role',
                    'source_id' => (string) $role->id,
                    'key' => $role->key,
                    'name' => $role->name,
                    'description' => $role->description,
                    'permissions_map' => json_encode([
                        'ids' => $permissionIds->all(),
                        'keys' => $permissionKeys->all(),
                    ], JSON_UNESCAPED_UNICODE),
                    'is_system' => (bool) $role->is_system,
                    'is_active' => (bool) $role->is_active,
                    'created_at' => $role->created_at ?? now(),
                    'updated_at' => $role->updated_at ?? now(),
                ]
            );
        }

        foreach ($rolePermissions as $roleId => $rows) {
            foreach ($rows as $row) {
                DB::table('rbac_matrix')->updateOrInsert(
                    [
                        'record_type' => 'role_permission',
                        'role_id' => (string) $roleId,
                        'permission_id' => (string) $row->permission_id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'source_id' => (string) $roleId . ':' . (string) $row->permission_id,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );
            }
        }

        foreach ($userRoles as $userId => $rows) {
            $roleIds = $rows->pluck('role_id')->map(fn ($id) => (string) $id)->unique()->values();
            $permissionKeys = $roleIds
                ->filter(fn (string $roleId) => (bool) ($roles[$roleId]->is_active ?? false))
                ->flatMap(fn (string $roleId) => ($rolePermissions->get($roleId) ?? collect())->pluck('permission_id'))
                ->map(fn ($permissionId) => $permissions[(string) $permissionId]->key ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->values();

            foreach ($roleIds as $roleId) {
                DB::table('rbac_matrix')->updateOrInsert(
                    [
                        'record_type' => 'user_role',
                        'user_id' => (string) $userId,
                        'role_id' => (string) $roleId,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'source_id' => (string) $userId . ':' . (string) $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('users')->where('id', $userId)->update([
                'rbac_role_ids' => json_encode($roleIds->all(), JSON_UNESCAPED_UNICODE),
                'permissions_map' => json_encode($permissionKeys->all(), JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    private function mergeQueueTables(): void
    {
        if (Schema::hasTable('jobs')) {
            foreach (DB::table('jobs')->get() as $job) {
                DB::table('queue_logs')->insert([
                    'record_type' => 'queue_job',
                    'source_id' => (string) $job->id,
                    'queue' => $job->queue,
                    'payload' => $job->payload,
                    'attempts' => $job->attempts,
                    'reserved_at' => $job->reserved_at,
                    'available_at' => $job->available_at,
                    'created_at_int' => $job->created_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('failed_jobs')) {
            foreach (DB::table('failed_jobs')->get() as $job) {
                DB::table('queue_logs')->insert([
                    'record_type' => 'failed_job',
                    'source_id' => (string) $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'payload' => $job->payload,
                    'exception' => $job->exception,
                    'failed_at' => $job->failed_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('job_batches')) {
            foreach (DB::table('job_batches')->get() as $batch) {
                DB::table('queue_logs')->insert([
                    'record_type' => 'job_batch',
                    'source_id' => (string) $batch->id,
                    'batch_uuid' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->total_jobs,
                    'pending_jobs' => $batch->pending_jobs,
                    'failed_jobs_count' => $batch->failed_jobs,
                    'failed_job_ids' => $batch->failed_job_ids,
                    'options' => $batch->options,
                    'cancelled_at' => $batch->cancelled_at,
                    'created_at_int' => $batch->created_at,
                    'finished_at' => $batch->finished_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function mergeCacheTables(): void
    {
        if (Schema::hasTable('cache')) {
            foreach (DB::table('cache')->get() as $row) {
                DB::table('system_cache')->updateOrInsert(
                    ['id' => 'cache:' . $row->key],
                    [
                        'record_type' => 'cache',
                        'cache_key' => $row->key,
                        'value' => $row->value,
                        'expiration' => $row->expiration,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('cache_locks')) {
            foreach (DB::table('cache_locks')->get() as $row) {
                DB::table('system_cache')->updateOrInsert(
                    ['id' => 'lock:' . $row->key],
                    [
                        'record_type' => 'lock',
                        'cache_key' => $row->key,
                        'owner' => $row->owner,
                        'expiration' => $row->expiration,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function dropSourceTables(): void
    {
        foreach (array_merge($this->rbacTables, $this->queueTables, $this->cacheTables) as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createRoutingTriggers(): void
    {
        foreach (['rbac_matrix_route_insert', 'queue_logs_route_insert', 'system_cache_route_insert'] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        DB::unprepared("
            CREATE TRIGGER rbac_matrix_route_insert BEFORE INSERT ON rbac_matrix
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF;
                IF NEW.record_type IS NULL THEN
                    IF NEW.user_id IS NOT NULL AND NEW.role_id IS NOT NULL THEN SET NEW.record_type = 'user_role';
                    ELSEIF NEW.role_id IS NOT NULL AND NEW.permission_id IS NOT NULL THEN SET NEW.record_type = 'role_permission';
                    ELSEIF NEW.group IS NOT NULL THEN SET NEW.record_type = 'permission';
                    ELSE SET NEW.record_type = 'role';
                    END IF;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER queue_logs_route_insert BEFORE INSERT ON queue_logs
            FOR EACH ROW
            BEGIN
                IF NEW.record_type IS NULL THEN
                    IF NEW.exception IS NOT NULL THEN SET NEW.record_type = 'failed_job';
                    ELSEIF NEW.batch_uuid IS NOT NULL THEN SET NEW.record_type = 'job_batch';
                    ELSE SET NEW.record_type = 'queue_job';
                    END IF;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER system_cache_route_insert BEFORE INSERT ON system_cache
            FOR EACH ROW
            BEGIN
                IF NEW.record_type IS NULL THEN
                    IF NEW.owner IS NOT NULL THEN SET NEW.record_type = 'lock';
                    ELSE SET NEW.record_type = 'cache';
                    END IF;
                END IF;
            END
        ");
    }
};
