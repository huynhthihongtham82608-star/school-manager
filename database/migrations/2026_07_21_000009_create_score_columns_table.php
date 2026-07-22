<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_columns')) {
            Schema::create('score_columns', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('school_year_id', 50)->index();
                $table->string('subject_id', 50)->index();
                $table->unsignedTinyInteger('grade_level')->index();
                $table->string('name');
                $table->string('type', 30)->index();
                $table->unsignedTinyInteger('weight_group')->default(1);
                $table->date('input_opens_at')->nullable();
                $table->date('input_closes_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->unique(['school_year_id', 'subject_id', 'grade_level', 'name'], 'score_columns_scope_name_unique');
            });
        }

        if (Schema::hasTable('score_details')) {
            Schema::table('score_details', function (Blueprint $table) {
                if (! Schema::hasColumn('score_details', 'score_column_id')) {
                    $table->string('score_column_id', 50)->nullable()->after('exam_schedule_id')->index();
                }
            });

            DB::statement("ALTER TABLE `score_details` MODIFY `type` varchar(30) NOT NULL");
        }

        $this->seedDefaultColumns();
    }

    public function down(): void
    {
        if (Schema::hasTable('score_details') && Schema::hasColumn('score_details', 'score_column_id')) {
            Schema::table('score_details', function (Blueprint $table) {
                $table->dropColumn('score_column_id');
            });
        }

        Schema::dropIfExists('score_columns');
    }

    private function seedDefaultColumns(): void
    {
        if (! Schema::hasTable('score_columns') || ! Schema::hasTable('school_years') || ! Schema::hasTable('subjects')) {
            return;
        }

        $years = DB::table('school_years')->pluck('id');
        $subjects = DB::table('subjects')
            ->where(function ($query) {
                $query->where('type', Subject::TYPE_OFFICIAL)
                    ->orWhereIn('type', Subject::LEGACY_SCORABLE_TYPES);
            })
            ->where('status', Subject::STATUS_ACTIVE)
            ->pluck('id');

        $defaults = [
            ['name' => 'Kiểm tra miệng', 'type' => 'regular', 'weight_group' => 1, 'sort_order' => 10],
            ['name' => 'Kiểm tra 15 phút', 'type' => 'regular', 'weight_group' => 1, 'sort_order' => 20],
            ['name' => 'Kiểm tra 1 tiết', 'type' => 'regular', 'weight_group' => 1, 'sort_order' => 30],
            ['name' => 'Kiểm tra giữa kỳ', 'type' => 'midterm', 'weight_group' => 2, 'sort_order' => 40],
            ['name' => 'Kiểm tra cuối kỳ', 'type' => 'final', 'weight_group' => 3, 'sort_order' => 50],
        ];

        $now = now();
        foreach ($years as $yearId) {
            foreach ($subjects as $subjectId) {
                foreach ([10, 11, 12] as $gradeLevel) {
                    foreach ($defaults as $default) {
                        $exists = DB::table('score_columns')
                            ->where('school_year_id', $yearId)
                            ->where('subject_id', $subjectId)
                            ->where('grade_level', $gradeLevel)
                            ->where('name', $default['name'])
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table('score_columns')->insert([
                            'id' => (string) Str::uuid(),
                            'school_year_id' => $yearId,
                            'subject_id' => $subjectId,
                            'grade_level' => $gradeLevel,
                            'name' => $default['name'],
                            'type' => $default['type'],
                            'weight_group' => $default['weight_group'],
                            'sort_order' => $default['sort_order'],
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }
};
