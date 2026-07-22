<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_departments')) {
            Schema::create('teacher_departments', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('code', 50)->unique();
                $table->string('name')->unique();
                $table->string('subject_id', 50)->nullable()->unique();
                $table->string('leader_teacher_id', 50)->nullable()->index();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();

                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('subjects')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('teachers') && ! Schema::hasColumn('teachers', 'department_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('department_id', 50)->nullable()->after('primary_subject_id')->index();
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasTable('teacher_departments')) {
            Schema::table('teachers', function (Blueprint $table) {
                try {
                    $table->foreign('department_id')
                        ->references('id')
                        ->on('teacher_departments')
                        ->nullOnDelete();
                } catch (Throwable) {
                    // Foreign key may already exist in manually updated databases.
                }
            });

            Schema::table('teacher_departments', function (Blueprint $table) {
                try {
                    $table->foreign('leader_teacher_id')
                        ->references('id')
                        ->on('teachers')
                        ->nullOnDelete();
                } catch (Throwable) {
                    // Foreign key may already exist in manually updated databases.
                }
            });
        }

        $this->seedDefaultDepartments();
        $this->syncTeachersByPrimarySubject();
        $this->syncDepartmentLeaders();
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'department_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (Throwable) {
                    // Ignore databases without this foreign key.
                }

                $table->dropColumn('department_id');
            });
        }

        if (Schema::hasTable('teacher_departments')) {
            Schema::dropIfExists('teacher_departments');
        }
    }

    private function seedDefaultDepartments(): void
    {
        if (! Schema::hasTable('teacher_departments') || ! Schema::hasTable('subjects')) {
            return;
        }

        $defaults = [
            ['TOAN', 'Tổ Toán', ['Toán', 'Toan']],
            ['NGUVAN', 'Tổ Ngữ văn', ['Ngữ văn', 'Ngu van', 'Văn', 'Van']],
            ['TIENGANH', 'Tổ Tiếng Anh', ['Tiếng Anh', 'Tieng Anh', 'Anh']],
            ['VATLY', 'Tổ Vật lý', ['Vật lý', 'Vat ly', 'Lý', 'Ly']],
            ['HOAHOC', 'Tổ Hóa học', ['Hóa học', 'Hoa hoc', 'Hóa', 'Hoa']],
            ['SINHHOC', 'Tổ Sinh học', ['Sinh học', 'Sinh hoc', 'Sinh']],
            ['LICHSU', 'Tổ Lịch sử', ['Lịch sử', 'Lich su', 'Sử', 'Su']],
            ['DIALY', 'Tổ Địa lý', ['Địa lý', 'Dia ly', 'Địa', 'Dia']],
            ['GDCD', 'Tổ GDCD', ['GDCD', 'Giáo dục công dân', 'Giao duc cong dan']],
            ['TINHOC', 'Tổ Tin học', ['Tin học', 'Tin hoc', 'Tin']],
            ['THEDUC', 'Tổ Thể dục', ['Thể dục', 'The duc']],
        ];

        foreach ($defaults as [$code, $name, $aliases]) {
            $subject = $this->findSubject($aliases);

            DB::table('teacher_departments')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => DB::table('teacher_departments')->where('code', $code)->value('id') ?: (string) Str::uuid(),
                    'name' => $name,
                    'subject_id' => $subject?->id,
                    'description' => 'Tổ chuyên môn phụ trách ' . str_replace('Tổ ', '', $name) . '.',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function syncTeachersByPrimarySubject(): void
    {
        if (
            ! Schema::hasTable('teachers')
            || ! Schema::hasColumn('teachers', 'department_id')
            || ! Schema::hasTable('teacher_departments')
        ) {
            return;
        }

        DB::table('teacher_departments')
            ->whereNotNull('subject_id')
            ->orderBy('code')
            ->get()
            ->each(function ($department) {
                DB::table('teachers')
                    ->whereNull('department_id')
                    ->where('primary_subject_id', $department->subject_id)
                    ->update([
                        'department_id' => $department->id,
                        'updated_at' => now(),
                    ]);
            });
    }

    private function syncDepartmentLeaders(): void
    {
        if (
            ! Schema::hasTable('teacher_departments')
            || ! Schema::hasTable('teachers')
            || ! Schema::hasColumn('teachers', 'department_id')
        ) {
            return;
        }

        DB::table('teacher_departments')
            ->whereNull('leader_teacher_id')
            ->orderBy('code')
            ->get()
            ->each(function ($department) {
                $leaderId = DB::table('teachers')
                    ->where('department_id', $department->id)
                    ->orderBy('teacher_code')
                    ->value('id');

                if ($leaderId) {
                    DB::table('teacher_departments')
                        ->where('id', $department->id)
                        ->update([
                            'leader_teacher_id' => $leaderId,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function findSubject(array $aliases): ?object
    {
        foreach ($aliases as $alias) {
            $subject = DB::table('subjects')
                ->where('name', $alias)
                ->orWhere('code', $alias)
                ->first();

            if ($subject) {
                return $subject;
            }
        }

        foreach ($aliases as $alias) {
            $subject = DB::table('subjects')
                ->where('name', 'like', '%' . $alias . '%')
                ->first();

            if ($subject) {
                return $subject;
            }
        }

        return null;
    }
};
