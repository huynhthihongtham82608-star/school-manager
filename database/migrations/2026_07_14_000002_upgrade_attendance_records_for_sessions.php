<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('student_id', 50)->index();
                $table->string('class_id', 50)->index();
                $table->string('semester_id', 50)->nullable()->index();
                $table->date('attendance_date')->index();
                $table->string('session_type', 20)->default('daily')->index();
                $table->string('timetable_entry_id', 50)->nullable()->index();
                $table->string('session_label')->nullable();
                $table->unsignedSmallInteger('session_order')->default(0)->index();
                $table->string('session_key', 80)->default('daily')->index();
                $table->string('status')->default('present')->index();
                $table->text('note')->nullable();
                $table->string('recorded_by', 50)->nullable()->index();
                $table->timestamps();

                $table->unique(['student_id', 'attendance_date', 'session_key'], 'attendance_records_student_date_session_unique');
            });

            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'session_type')) {
                $table->string('session_type', 20)->default('daily')->after('attendance_date')->index();
            }

            if (! Schema::hasColumn('attendance_records', 'timetable_entry_id')) {
                $table->string('timetable_entry_id', 50)->nullable()->after('session_type')->index();
            }

            if (! Schema::hasColumn('attendance_records', 'session_label')) {
                $table->string('session_label')->nullable()->after('timetable_entry_id');
            }

            if (! Schema::hasColumn('attendance_records', 'session_order')) {
                $table->unsignedSmallInteger('session_order')->default(0)->after('session_label')->index();
            }

            if (! Schema::hasColumn('attendance_records', 'session_key')) {
                $table->string('session_key', 80)->nullable()->after('session_order')->index();
            }
        });

        DB::table('attendance_records')
            ->whereNull('session_key')
            ->update([
                'session_type' => 'daily',
                'session_key' => 'daily',
                'session_label' => 'Điểm danh theo ngày',
                'session_order' => 0,
            ]);

        try {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropUnique('attendance_records_student_id_attendance_date_unique');
            });
        } catch (Throwable $exception) {
            // The index may have already been removed in older local databases.
        }

        try {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->unique(['student_id', 'attendance_date', 'session_key'], 'attendance_records_student_date_session_unique');
            });
        } catch (Throwable $exception) {
            // Avoid blocking existing installations where the index already exists.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropUnique('attendance_records_student_date_session_unique');
            });
        } catch (Throwable $exception) {
            //
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            foreach (['session_key', 'session_order', 'session_label', 'timetable_entry_id', 'session_type'] as $column) {
                if (Schema::hasColumn('attendance_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
