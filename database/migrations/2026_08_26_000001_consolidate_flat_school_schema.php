<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sourceTables = [
        'students',
        'teachers',
        'parents',
        'score_headers',
        'score_details',
        'score_columns',
        'grade_windows',
        'score_settings',
        'school_events',
        'learning_documents',
        'home_page_contents',
        'settings',
        'message_recipients',
        'message_attachments',
        'timetable_entries',
        'teacher_department_subject',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->prepareTargets();
            $this->dropReferencingForeignKeys();
            $this->normalizeCoreKeyTypes();
            $this->mergeIdentities();
            $this->rewriteIdentityReferences();
            $this->mergeScores();
            $this->mergeContentAndSettings();
            $this->mergeMessages();
            $this->mergeTimetables();
            $this->mergeTeacherDepartments();
            $this->dropSourceTables();
            $this->createRoutingTriggers();
            $this->restoreForeignKeys();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This data consolidation migration is intentionally irreversible. Restore from backup if rollback is required.');
    }

    private function prepareTargets(): void
    {
        $this->prepareUsersTable();
        $this->prepareStudentScoresTable();
        $this->prepareSchoolPostsTable();
        $this->prepareSystemSettingsTable();
        $this->prepareMessagesTable();
        $this->prepareTimetablesTable();
        $this->prepareTeacherDepartmentsTable();
    }

    private function prepareUsersTable(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'role_type' => fn () => $table->string('role_type', 30)->nullable()->after('role')->index(),
                'source_profile_id' => fn () => $table->string('source_profile_id', 50)->nullable()->after('role_type')->index(),
                'name' => fn () => $table->string('name')->nullable()->after('full_name'),
                'student_code' => fn () => $table->string('student_code', 50)->nullable()->after('source_profile_id')->unique(),
                'teacher_code' => fn () => $table->string('teacher_code', 50)->nullable()->after('student_code')->unique(),
                'parent_code' => fn () => $table->string('parent_code', 50)->nullable()->after('teacher_code')->unique(),
                'gender' => fn () => $table->string('gender', 20)->nullable(),
                'dob' => fn () => $table->date('dob')->nullable(),
                'address' => fn () => $table->string('address')->nullable(),
                'place_of_birth' => fn () => $table->string('place_of_birth')->nullable(),
                'ethnicity' => fn () => $table->string('ethnicity', 100)->nullable(),
                'religion' => fn () => $table->string('religion', 100)->nullable(),
                'parent_phone' => fn () => $table->string('parent_phone', 50)->nullable(),
                'enrollment_date' => fn () => $table->date('enrollment_date')->nullable(),
                'admission_type' => fn () => $table->string('admission_type', 20)->nullable(),
                'previous_school' => fn () => $table->string('previous_school')->nullable(),
                'transfer_grade_level' => fn () => $table->unsignedTinyInteger('transfer_grade_level')->nullable(),
                'previous_class' => fn () => $table->string('previous_class', 50)->nullable(),
                'avatar' => fn () => $table->string('avatar')->nullable(),
                'note' => fn () => $table->text('note')->nullable(),
                'class_id' => fn () => $table->string('class_id', 50)->nullable()->index(),
                'school_year_id' => fn () => $table->string('school_year_id', 50)->nullable()->index(),
                'status' => fn () => $table->string('status', 30)->nullable(),
                'joined_at' => fn () => $table->date('joined_at')->nullable(),
                'work_status' => fn () => $table->string('work_status', 30)->nullable(),
                'qualification' => fn () => $table->string('qualification')->nullable(),
                'main_subject' => fn () => $table->string('main_subject')->nullable(),
                'primary_subject_id' => fn () => $table->string('primary_subject_id', 50)->nullable()->index(),
                'department_id' => fn () => $table->string('department_id', 50)->nullable()->index(),
                'is_homeroom' => fn () => $table->boolean('is_homeroom')->default(false),
            ] as $column => $callback) {
                if (! Schema::hasColumn('users', $column)) {
                    $callback();
                }
            }
        });
    }

    private function prepareStudentScoresTable(): void
    {
        if (! Schema::hasTable('student_scores')) {
            Schema::create('student_scores', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('score_record_type', 30)->default('detail')->index();
                $table->string('source_id', 50)->nullable()->index();
                $table->string('student_id', 50)->nullable()->index();
                $table->string('subject_id', 50)->nullable()->index();
                $table->string('semester_id', 50)->nullable()->index();
                $table->string('school_year_id', 50)->nullable()->index();
                $table->string('class_id', 50)->nullable()->index();
                $table->unsignedTinyInteger('grade_level')->nullable()->index();
                $table->string('score_header_id', 50)->nullable()->index();
                $table->string('exam_schedule_id', 50)->nullable()->index();
                $table->string('score_column_id', 50)->nullable()->index();
                $table->string('score_type', 50)->nullable()->index();
                $table->string('score_name')->nullable();
                $table->decimal('score_value', 5, 2)->nullable();
                $table->string('type', 30)->nullable()->index();
                $table->string('name')->nullable();
                $table->decimal('value', 5, 2)->nullable();
                $table->unsignedTinyInteger('weight_group')->nullable();
                $table->decimal('average', 5, 2)->nullable();
                $table->date('input_opens_at')->nullable();
                $table->date('input_closes_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_open')->nullable();
                $table->unsignedTinyInteger('weight_gdtx')->nullable();
                $table->unsignedTinyInteger('weight_dggk')->nullable();
                $table->unsignedTinyInteger('weight_dgck')->nullable();
                $table->boolean('is_retest')->default(false)->index();
                $table->decimal('original_value', 5, 2)->nullable();
                $table->timestamp('retest_updated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function prepareSchoolPostsTable(): void
    {
        Schema::table('school_posts', function (Blueprint $table) {
            foreach ([
                'post_type' => fn () => $table->string('post_type', 30)->default('post')->after('id')->index(),
                'description' => fn () => $table->text('description')->nullable(),
                'location' => fn () => $table->string('location')->nullable(),
                'starts_at' => fn () => $table->dateTime('starts_at')->nullable()->index(),
                'ends_at' => fn () => $table->dateTime('ends_at')->nullable(),
                'category' => fn () => $table->string('category')->nullable()->index(),
                'file_url' => fn () => $table->string('file_url')->nullable(),
                'subject_id' => fn () => $table->string('subject_id', 50)->nullable()->index(),
                'class_id' => fn () => $table->string('class_id', 50)->nullable()->index(),
                'uploaded_by' => fn () => $table->string('uploaded_by', 50)->nullable()->index(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('school_posts', $column)) {
                    $callback();
                }
            }
        });
    }

    private function prepareSystemSettingsTable(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            foreach ([
                'setting_record_type' => fn () => $table->string('setting_record_type', 30)->default('system')->after('id')->index(),
                'source_id' => fn () => $table->string('source_id', 50)->nullable()->index(),
                'key' => fn () => $table->string('key')->nullable()->index(),
                'value' => fn () => $table->text('value')->nullable(),
                'group' => fn () => $table->string('group')->nullable()->index(),
                'title' => fn () => $table->string('title')->nullable(),
                'content' => fn () => $table->longText('content')->nullable(),
                'image_url' => fn () => $table->string('image_url')->nullable(),
                'extra' => fn () => $table->longText('extra')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('system_settings', $column)) {
                    $callback();
                }
            }
        });

        DB::statement('ALTER TABLE `system_settings` MODIFY `school_name` VARCHAR(255) NULL');
    }

    private function prepareMessagesTable(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            foreach ([
                'message_record_type' => fn () => $table->string('message_record_type', 30)->default('message')->after('id')->index(),
                'message_id' => fn () => $table->string('message_id', 50)->nullable()->index(),
                'read_at' => fn () => $table->timestamp('read_at')->nullable(),
                'deleted_at' => fn () => $table->timestamp('deleted_at')->nullable(),
                'permanently_deleted_at' => fn () => $table->timestamp('permanently_deleted_at')->nullable(),
                'original_name' => fn () => $table->string('original_name')->nullable(),
                'path' => fn () => $table->string('path')->nullable(),
                'mime_type' => fn () => $table->string('mime_type')->nullable(),
                'size' => fn () => $table->unsignedBigInteger('size')->default(0),
                'updated_at' => fn () => $table->timestamp('updated_at')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('messages', $column)) {
                    $callback();
                }
            }
        });

        DB::statement('ALTER TABLE `messages` MODIFY `sender_user_id` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `messages` MODIFY `receiver_user_id` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `messages` MODIFY `content` TEXT NULL');
        DB::statement('ALTER TABLE `messages` MODIFY `target_type` VARCHAR(50) NULL');
    }

    private function prepareTimetablesTable(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            foreach ([
                'timetable_record_type' => fn () => $table->string('timetable_record_type', 30)->default('timetable')->after('id')->index(),
                'timetable_id' => fn () => $table->string('timetable_id', 50)->nullable()->index(),
                'assignment_id' => fn () => $table->string('assignment_id', 50)->nullable()->index(),
                'day_of_week' => fn () => $table->unsignedTinyInteger('day_of_week')->nullable()->index(),
                'period' => fn () => $table->unsignedTinyInteger('period')->nullable()->index(),
                'subject_id' => fn () => $table->string('subject_id', 50)->nullable()->index(),
                'teacher_id' => fn () => $table->string('teacher_id', 50)->nullable()->index(),
                'room' => fn () => $table->string('room', 50)->nullable(),
                'room_id' => fn () => $table->string('room_id', 50)->nullable()->index(),
                'note' => fn () => $table->string('note')->nullable(),
                'status' => fn () => $table->string('status', 20)->default('active')->index(),
                'archived_at' => fn () => $table->timestamp('archived_at')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('timetables', $column)) {
                    $callback();
                }
            }
        });
    }

    private function prepareTeacherDepartmentsTable(): void
    {
        Schema::table('teacher_departments', function (Blueprint $table) {
            foreach ([
                'department_record_type' => fn () => $table->string('department_record_type', 30)->default('department')->after('id')->index(),
                'department_id' => fn () => $table->string('department_id', 50)->nullable()->index(),
                'subject_id' => fn () => $table->string('subject_id', 50)->nullable()->index(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('teacher_departments', $column)) {
                    $callback();
                }
            }
        });

        DB::statement('ALTER TABLE `teacher_departments` MODIFY `code` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `teacher_departments` MODIFY `name` VARCHAR(255) NULL');
    }

    private function dropReferencingForeignKeys(): void
    {
        $tables = array_merge($this->sourceTables, ['users', 'school_years', 'semesters', 'subjects', 'classes', 'conducts', 'parent_leave_requests', 'parent_student', 'student_class_assignments', 'student_transfers', 'teacher_departments', 'teaching_assignments', 'timetable_entries']);
        $placeholders = implode(',', array_fill(0, count($tables), '?'));

        $foreignKeys = DB::select(
            "SELECT DISTINCT k.TABLE_NAME, k.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE k
             WHERE k.CONSTRAINT_SCHEMA = DATABASE()
               AND k.REFERENCED_TABLE_NAME IN ({$placeholders})",
            $tables
        );

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE `' . $foreignKey->TABLE_NAME . '` DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
        }
    }

    private function normalizeCoreKeyTypes(): void
    {
        foreach ([
            'users',
            'school_years',
            'semesters',
            'subjects',
            'classes',
            'teaching_assignments',
            'conducts',
        ] as $table) {
            $this->modifyColumn($table, 'id', 'VARCHAR(50) NOT NULL');
        }

        foreach ([
            'users' => ['teacher_id', 'student_id', 'parent_id', 'class_id', 'school_year_id', 'primary_subject_id', 'department_id'],
            'classes' => ['school_year_id', 'homeroom_teacher_id', 'semester_id'],
            'semesters' => ['school_year_id'],
            'teaching_assignments' => ['teacher_id', 'class_id', 'subject_id', 'school_year_id', 'semester_id'],
            'conducts' => ['student_id', 'class_id', 'semester_id', 'school_year_id'],
            'grade_windows' => ['class_id', 'subject_id', 'semester_id', 'school_year_id'],
            'score_headers' => ['student_id', 'subject_id', 'semester_id', 'school_year_id'],
            'score_details' => ['score_header_id'],
            'students' => ['class_id', 'school_year_id'],
            'student_class_assignments' => ['student_id', 'class_id', 'academic_year_id'],
            'timetables' => ['school_year_id', 'semester_id', 'class_id'],
            'timetable_entries' => ['timetable_id', 'assignment_id', 'subject_id', 'teacher_id', 'room_id'],
            'parent_leave_requests' => ['parent_id', 'student_id', 'class_id', 'reviewed_by'],
            'messages' => ['sender_user_id', 'receiver_user_id'],
            'system_settings' => ['default_school_year_id'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $this->modifyColumn($table, $column, 'VARCHAR(50) NULL');
            }
        }

        foreach ([
            'classes' => ['school_year_id'],
            'semesters' => ['school_year_id'],
            'teaching_assignments' => ['teacher_id', 'class_id', 'subject_id', 'school_year_id'],
            'conducts' => ['student_id', 'class_id', 'semester_id', 'school_year_id'],
            'score_headers' => ['student_id', 'subject_id', 'semester_id', 'school_year_id'],
            'students' => ['school_year_id'],
            'timetables' => ['school_year_id', 'semester_id', 'class_id'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $this->modifyColumn($table, $column, 'VARCHAR(50) NOT NULL');
            }
        }
    }

    private function modifyColumn(string $table, string $column, string $definition): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }

    private function mergeIdentities(): void
    {
        if (Schema::hasTable('students')) {
            DB::statement("
                INSERT INTO users (id, username, full_name, name, email, phone, password_hash, role, role_type, student_id, source_profile_id, student_code, gender, dob, address, place_of_birth, ethnicity, religion, parent_phone, enrollment_date, admission_type, previous_school, transfer_grade_level, previous_class, avatar, note, class_id, school_year_id, status, is_active, created_at, updated_at)
                SELECT UUID(), IF(EXISTS (SELECT 1 FROM users ux WHERE ux.username = s.student_code), CONCAT(s.student_code, '_', LEFT(REPLACE(UUID(), '-', ''), 8)), s.student_code), s.name, s.name, s.email, s.parent_phone, '\$2y\$12\$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW', 'student', 'student', s.id, s.id, s.student_code, s.gender, s.dob, s.address, s.place_of_birth, s.ethnicity, s.religion, s.parent_phone, s.enrollment_date, s.admission_type, s.previous_school, s.transfer_grade_level, s.previous_class, s.avatar, s.note, s.class_id, s.school_year_id, s.status, s.status = 'studying', s.created_at, s.updated_at
                FROM students s
                WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.student_id = s.id OR (u.username = s.student_code AND u.role = 'student'))
            ");

            DB::statement("
                UPDATE users u
                JOIN students s ON u.student_id = s.id OR (u.username = s.student_code AND u.role = 'student')
                SET u.role_type = 'student', u.role = 'student', u.source_profile_id = s.id, u.full_name = COALESCE(u.full_name, s.name), u.name = s.name, u.email = COALESCE(u.email, s.email), u.phone = COALESCE(u.phone, s.parent_phone), u.student_code = s.student_code, u.gender = s.gender, u.dob = s.dob, u.address = s.address, u.place_of_birth = s.place_of_birth, u.ethnicity = s.ethnicity, u.religion = s.religion, u.parent_phone = s.parent_phone, u.enrollment_date = s.enrollment_date, u.admission_type = s.admission_type, u.previous_school = s.previous_school, u.transfer_grade_level = s.transfer_grade_level, u.previous_class = s.previous_class, u.avatar = s.avatar, u.note = s.note, u.class_id = s.class_id, u.school_year_id = s.school_year_id, u.status = s.status, u.is_active = s.status = 'studying'
            ");
        }

        if (Schema::hasTable('teachers')) {
            DB::statement("
                INSERT INTO users (id, username, full_name, name, email, phone, password_hash, role, role_type, teacher_id, source_profile_id, teacher_code, gender, dob, address, joined_at, work_status, qualification, main_subject, primary_subject_id, department_id, is_homeroom, is_active, created_at, updated_at)
                SELECT UUID(), IF(EXISTS (SELECT 1 FROM users ux WHERE ux.username = t.teacher_code), CONCAT(t.teacher_code, '_', LEFT(REPLACE(UUID(), '-', ''), 8)), t.teacher_code), t.name, t.name, t.email, t.phone, '\$2y\$12\$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW', 'teacher', 'teacher', t.id, t.id, t.teacher_code, t.gender, t.dob, t.address, t.joined_at, t.work_status, t.qualification, t.main_subject, t.primary_subject_id, t.department_id, t.is_homeroom, t.work_status = 'working', t.created_at, t.updated_at
                FROM teachers t
                WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.teacher_id = t.id OR (u.username = t.teacher_code AND u.role IN ('teacher', 'homeroom')))
            ");

            DB::statement("
                UPDATE users u
                JOIN teachers t ON u.teacher_id = t.id OR (u.username = t.teacher_code AND u.role IN ('teacher', 'homeroom'))
                SET u.role_type = 'teacher', u.role = 'teacher', u.source_profile_id = t.id, u.full_name = COALESCE(u.full_name, t.name), u.name = t.name, u.email = COALESCE(u.email, t.email), u.phone = COALESCE(u.phone, t.phone), u.teacher_code = t.teacher_code, u.gender = t.gender, u.dob = t.dob, u.address = t.address, u.joined_at = t.joined_at, u.work_status = t.work_status, u.qualification = t.qualification, u.main_subject = t.main_subject, u.primary_subject_id = t.primary_subject_id, u.department_id = t.department_id, u.is_homeroom = t.is_homeroom, u.is_active = t.work_status = 'working'
            ");
        }

        if (Schema::hasTable('parents')) {
            DB::statement("
                INSERT INTO users (id, username, full_name, name, email, phone, password_hash, role, role_type, parent_id, source_profile_id, parent_code, address, is_active, created_at, updated_at)
                SELECT UUID(), IF(EXISTS (SELECT 1 FROM users ux WHERE ux.username = p.phone), CONCAT(COALESCE(p.phone, p.parent_code, 'PH'), '_', LEFT(REPLACE(UUID(), '-', ''), 8)), COALESCE(p.phone, p.parent_code, UUID())), p.name, p.name, p.email, p.phone, '\$2y\$12\$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW', 'parent', 'parent', p.id, p.id, p.parent_code, p.address, 1, p.created_at, p.updated_at
                FROM parents p
                WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.parent_id = p.id OR (u.username = p.phone AND u.role = 'parent'))
            ");

            DB::statement("
                UPDATE users u
                JOIN parents p ON u.parent_id = p.id OR (u.username = p.phone AND u.role = 'parent')
                SET u.role_type = 'parent', u.role = 'parent', u.source_profile_id = p.id, u.full_name = COALESCE(u.full_name, p.name), u.name = p.name, u.email = COALESCE(u.email, p.email), u.phone = COALESCE(u.phone, p.phone), u.parent_code = p.parent_code, u.address = p.address, u.is_active = 1
            ");
        }
    }

    private function rewriteIdentityReferences(): void
    {
        foreach (['attendance_records', 'conducts', 'rewards', 'score_headers', 'student_class_assignments', 'student_transfers', 'tuition_fees'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'student_id')) {
                DB::statement("UPDATE `{$table}` x JOIN users u ON u.role_type = 'student' AND u.source_profile_id = x.student_id SET x.student_id = u.id");
            }
        }

        if (Schema::hasTable('parent_leave_requests')) {
            DB::statement('ALTER TABLE `parent_leave_requests` MODIFY `student_id` VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE `parent_leave_requests` MODIFY `parent_id` VARCHAR(50) NOT NULL');
            DB::statement("UPDATE parent_leave_requests x JOIN users u ON u.role_type = 'student' AND u.source_profile_id = x.student_id SET x.student_id = u.id");
            DB::statement("UPDATE parent_leave_requests x JOIN users u ON u.role_type = 'parent' AND u.source_profile_id = x.parent_id SET x.parent_id = u.id");
        }

        if (Schema::hasTable('parent_student')) {
            DB::statement("UPDATE parent_student x JOIN users u ON u.role_type = 'student' AND u.source_profile_id = x.student_id SET x.student_id = u.id");
            DB::statement("UPDATE parent_student x JOIN users u ON u.role_type = 'parent' AND u.source_profile_id = x.parent_id SET x.parent_id = u.id");
        }

        foreach ([
            'classes' => ['homeroom_teacher_id'],
            'substitute_teachings' => ['original_teacher_id', 'substitute_teacher_id'],
            'teacher_departments' => ['leader_teacher_id'],
            'teaching_assignments' => ['teacher_id'],
            'timetable_entries' => ['teacher_id'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::statement("UPDATE `{$table}` x JOIN users u ON u.role_type = 'teacher' AND u.source_profile_id = x.`{$column}` SET x.`{$column}` = u.id");
                }
            }
        }

        DB::statement("UPDATE users SET student_id = CASE WHEN role_type = 'student' THEN id ELSE NULL END, teacher_id = CASE WHEN role_type = 'teacher' THEN id ELSE NULL END, parent_id = CASE WHEN role_type = 'parent' THEN id ELSE NULL END");
    }

    private function mergeScores(): void
    {
        if (Schema::hasTable('score_headers')) {
            DB::statement("
                INSERT IGNORE INTO student_scores (id, score_record_type, source_id, student_id, subject_id, semester_id, school_year_id, score_type, score_value, average, created_at, updated_at)
                SELECT id, 'header', id, student_id, subject_id, semester_id, school_year_id, 'average', average, average, created_at, updated_at
                FROM score_headers
            ");
        }

        if (Schema::hasTable('score_details') && Schema::hasTable('score_headers')) {
            DB::statement("
                INSERT IGNORE INTO student_scores (id, score_record_type, source_id, score_header_id, student_id, subject_id, semester_id, school_year_id, exam_schedule_id, score_column_id, score_type, score_name, score_value, type, name, value, weight_group, is_retest, original_value, retest_updated_at, created_at, updated_at)
                SELECT d.id, 'detail', d.id, d.score_header_id, h.student_id, h.subject_id, h.semester_id, h.school_year_id, d.exam_schedule_id, d.score_column_id, d.type, d.name, d.value, d.type, d.name, d.value, d.weight_group, COALESCE(d.is_retest, 0), d.original_value, d.retest_updated_at, d.created_at, d.updated_at
                FROM score_details d
                JOIN score_headers h ON h.id = d.score_header_id
            ");
        }

        if (Schema::hasTable('score_columns')) {
            DB::statement("
                INSERT IGNORE INTO student_scores (id, score_record_type, source_id, school_year_id, subject_id, grade_level, score_type, score_name, type, name, weight_group, input_opens_at, input_closes_at, sort_order, is_active, created_at, updated_at)
                SELECT id, 'column', id, school_year_id, subject_id, grade_level, type, name, type, name, weight_group, input_opens_at, input_closes_at, sort_order, is_active, created_at, updated_at
                FROM score_columns
            ");
        }

        if (Schema::hasTable('grade_windows')) {
            DB::statement("
                INSERT IGNORE INTO student_scores (id, score_record_type, source_id, class_id, subject_id, semester_id, school_year_id, is_open, created_at, updated_at)
                SELECT id, 'grade_window', id, class_id, subject_id, semester_id, school_year_id, is_open, created_at, updated_at
                FROM grade_windows
            ");
        }

        if (Schema::hasTable('score_settings')) {
            DB::statement("
                INSERT IGNORE INTO student_scores (id, score_record_type, source_id, weight_gdtx, weight_dggk, weight_dgck, created_at, updated_at)
                SELECT UUID(), 'setting', id, weight_gdtx, weight_dggk, weight_dgck, created_at, updated_at
                FROM score_settings
            ");
        }
    }

    private function mergeContentAndSettings(): void
    {
        DB::table('school_posts')->whereNull('post_type')->orWhere('post_type', '')->update(['post_type' => 'post']);

        if (Schema::hasTable('school_events')) {
            DB::statement("
                INSERT IGNORE INTO school_posts (id, post_type, type, title, summary, content, description, location, starts_at, ends_at, published_at, is_published, created_at, updated_at)
                SELECT id, 'event', 'event', title, description, description, description, location, starts_at, ends_at, starts_at, is_published, created_at, updated_at
                FROM school_events
            ");
        }

        if (Schema::hasTable('learning_documents')) {
            DB::statement("
                INSERT IGNORE INTO school_posts (id, post_type, type, title, summary, content, description, category, file_url, subject_id, class_id, uploaded_by, is_published, created_at, updated_at)
                SELECT id, 'document', 'document', title, description, description, description, category, file_url, subject_id, class_id, uploaded_by, is_published, created_at, updated_at
                FROM learning_documents
            ");
        }

        DB::table('system_settings')->whereNull('setting_record_type')->orWhere('setting_record_type', '')->update(['setting_record_type' => 'system']);

        if (Schema::hasTable('settings')) {
            DB::statement("
                INSERT IGNORE INTO system_settings (id, setting_record_type, source_id, `key`, value, `group`, created_at, updated_at)
                SELECT UUID(), 'setting', id, `key`, value, `group`, created_at, updated_at
                FROM settings
            ");
        }

        if (Schema::hasTable('home_page_contents')) {
            DB::statement("
                INSERT IGNORE INTO system_settings (id, setting_record_type, source_id, `key`, title, content, image_url, extra, created_at, updated_at)
                SELECT id, 'home_page', id, `key`, title, content, image_url, extra, created_at, updated_at
                FROM home_page_contents
            ");
        }
    }

    private function mergeMessages(): void
    {
        DB::table('messages')->whereNull('message_record_type')->orWhere('message_record_type', '')->update(['message_record_type' => 'message']);

        if (Schema::hasTable('message_recipients')) {
            DB::statement("
                INSERT IGNORE INTO messages (id, message_record_type, message_id, receiver_user_id, is_read, read_at, deleted_at, permanently_deleted_at, created_at, updated_at)
                SELECT id, 'recipient', message_id, receiver_user_id, is_read, read_at, deleted_at, permanently_deleted_at, created_at, updated_at
                FROM message_recipients
            ");
        }

        if (Schema::hasTable('message_attachments')) {
            DB::statement("
                INSERT IGNORE INTO messages (id, message_record_type, message_id, original_name, path, mime_type, size, created_at, updated_at)
                SELECT id, 'attachment', message_id, original_name, path, mime_type, size, created_at, updated_at
                FROM message_attachments
            ");
        }
    }

    private function mergeTimetables(): void
    {
        DB::table('timetables')->whereNull('timetable_record_type')->orWhere('timetable_record_type', '')->update(['timetable_record_type' => 'timetable']);

        if (Schema::hasTable('timetable_entries')) {
            DB::statement("
                INSERT IGNORE INTO timetables (id, timetable_record_type, timetable_id, assignment_id, day_of_week, period, subject_id, teacher_id, room, room_id, note, status, archived_at, school_year_id, semester_id, class_id, created_at, updated_at)
                SELECT e.id, 'entry', e.timetable_id, e.assignment_id, e.day_of_week, e.period, e.subject_id, e.teacher_id, e.room, e.room_id, e.note, e.status, e.archived_at, t.school_year_id, t.semester_id, t.class_id, NULL, NULL
                FROM timetable_entries e
                JOIN timetables t ON t.id = e.timetable_id
            ");
        }
    }

    private function mergeTeacherDepartments(): void
    {
        DB::table('teacher_departments')->whereNull('department_record_type')->orWhere('department_record_type', '')->update(['department_record_type' => 'department']);

        if (Schema::hasTable('teacher_department_subject')) {
            DB::statement("
                INSERT IGNORE INTO teacher_departments (id, department_record_type, department_id, subject_id, status, created_at, updated_at)
                SELECT UUID(), 'subject', department_id, subject_id, 'active', created_at, updated_at
                FROM teacher_department_subject
            ");
        }
    }

    private function dropSourceTables(): void
    {
        foreach ($this->sourceTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createRoutingTriggers(): void
    {
        foreach (['users_route_profile_insert', 'student_scores_route_insert', 'school_posts_route_insert', 'system_settings_route_insert', 'messages_route_insert', 'timetables_route_insert', 'teacher_departments_route_insert'] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        DB::unprepared("
            CREATE TRIGGER users_route_profile_insert BEFORE INSERT ON users
            FOR EACH ROW
            BEGIN
                IF NEW.role_type IS NULL THEN
                    IF NEW.student_code IS NOT NULL THEN SET NEW.role_type = 'student'; SET NEW.role = 'student';
                    ELSEIF NEW.teacher_code IS NOT NULL THEN SET NEW.role_type = 'teacher'; SET NEW.role = 'teacher';
                    ELSEIF NEW.parent_code IS NOT NULL THEN SET NEW.role_type = 'parent'; SET NEW.role = 'parent';
                    ELSE SET NEW.role_type = NEW.role;
                    END IF;
                END IF;
                IF NEW.password_hash IS NULL THEN SET NEW.password_hash = '\$2y\$12\$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW'; END IF;
                IF NEW.full_name IS NULL THEN SET NEW.full_name = NEW.name; END IF;
                IF NEW.username IS NULL THEN SET NEW.username = COALESCE(NEW.student_code, NEW.teacher_code, NEW.phone, NEW.parent_code, UUID()); END IF;
                IF NEW.student_id IS NULL AND NEW.role_type = 'student' THEN SET NEW.student_id = NEW.id; END IF;
                IF NEW.teacher_id IS NULL AND NEW.role_type = 'teacher' THEN SET NEW.teacher_id = NEW.id; END IF;
                IF NEW.parent_id IS NULL AND NEW.role_type = 'parent' THEN SET NEW.parent_id = NEW.id; END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER student_scores_route_insert BEFORE INSERT ON student_scores
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF;
                IF NEW.weight_gdtx IS NOT NULL THEN SET NEW.score_record_type = 'setting';
                ELSEIF NEW.is_open IS NOT NULL AND NEW.score_header_id IS NULL THEN SET NEW.score_record_type = 'grade_window';
                ELSEIF NEW.grade_level IS NOT NULL AND NEW.score_header_id IS NULL AND NEW.student_id IS NULL THEN SET NEW.score_record_type = 'column';
                ELSEIF NEW.score_header_id IS NOT NULL THEN
                    SET NEW.score_record_type = 'detail';
                    SET NEW.student_id = COALESCE(NEW.student_id, (SELECT h.student_id FROM student_scores h WHERE h.id = NEW.score_header_id LIMIT 1));
                    SET NEW.subject_id = COALESCE(NEW.subject_id, (SELECT h.subject_id FROM student_scores h WHERE h.id = NEW.score_header_id LIMIT 1));
                    SET NEW.semester_id = COALESCE(NEW.semester_id, (SELECT h.semester_id FROM student_scores h WHERE h.id = NEW.score_header_id LIMIT 1));
                    SET NEW.school_year_id = COALESCE(NEW.school_year_id, (SELECT h.school_year_id FROM student_scores h WHERE h.id = NEW.score_header_id LIMIT 1));
                ELSEIF NEW.student_id IS NOT NULL OR NEW.average IS NOT NULL THEN SET NEW.score_record_type = 'header';
                ELSEIF NEW.score_record_type IS NULL THEN SET NEW.score_record_type = 'detail';
                END IF;
                IF NEW.score_type IS NULL THEN SET NEW.score_type = NEW.type; END IF;
                IF NEW.score_name IS NULL THEN SET NEW.score_name = NEW.name; END IF;
                IF NEW.score_value IS NULL THEN SET NEW.score_value = NEW.value; END IF;
                IF NEW.type IS NULL THEN SET NEW.type = NEW.score_type; END IF;
                IF NEW.name IS NULL THEN SET NEW.name = NEW.score_name; END IF;
                IF NEW.value IS NULL THEN SET NEW.value = NEW.score_value; END IF;
            END
        ");

        DB::unprepared("CREATE TRIGGER school_posts_route_insert BEFORE INSERT ON school_posts FOR EACH ROW BEGIN IF NEW.file_url IS NOT NULL OR NEW.category IS NOT NULL OR NEW.uploaded_by IS NOT NULL THEN SET NEW.post_type = 'document'; ELSEIF NEW.starts_at IS NOT NULL OR NEW.ends_at IS NOT NULL OR NEW.location IS NOT NULL THEN SET NEW.post_type = 'event'; ELSEIF NEW.post_type IS NULL THEN SET NEW.post_type = 'post'; END IF; IF NEW.type IS NULL THEN SET NEW.type = NEW.post_type; END IF; END");
        DB::unprepared("CREATE TRIGGER system_settings_route_insert BEFORE INSERT ON system_settings FOR EACH ROW BEGIN IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF; IF NEW.key IS NOT NULL AND NEW.value IS NOT NULL THEN SET NEW.setting_record_type = 'setting'; ELSEIF NEW.key IS NOT NULL THEN SET NEW.setting_record_type = 'home_page'; ELSEIF NEW.setting_record_type IS NULL THEN SET NEW.setting_record_type = 'system'; END IF; END");
        DB::unprepared("CREATE TRIGGER messages_route_insert BEFORE INSERT ON messages FOR EACH ROW BEGIN IF NEW.path IS NOT NULL THEN SET NEW.message_record_type = 'attachment'; ELSEIF NEW.message_id IS NOT NULL THEN SET NEW.message_record_type = 'recipient'; ELSEIF NEW.message_record_type IS NULL THEN SET NEW.message_record_type = 'message'; END IF; END");
        DB::unprepared("CREATE TRIGGER timetables_route_insert BEFORE INSERT ON timetables FOR EACH ROW BEGIN IF NEW.timetable_id IS NOT NULL THEN SET NEW.timetable_record_type = 'entry'; SET NEW.school_year_id = COALESCE(NEW.school_year_id, (SELECT t.school_year_id FROM timetables t WHERE t.id = NEW.timetable_id LIMIT 1)); SET NEW.semester_id = COALESCE(NEW.semester_id, (SELECT t.semester_id FROM timetables t WHERE t.id = NEW.timetable_id LIMIT 1)); SET NEW.class_id = COALESCE(NEW.class_id, (SELECT t.class_id FROM timetables t WHERE t.id = NEW.timetable_id LIMIT 1)); ELSEIF NEW.timetable_record_type IS NULL THEN SET NEW.timetable_record_type = 'timetable'; END IF; END");
        DB::unprepared("CREATE TRIGGER teacher_departments_route_insert BEFORE INSERT ON teacher_departments FOR EACH ROW BEGIN IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF; IF NEW.department_id IS NOT NULL AND NEW.subject_id IS NOT NULL THEN SET NEW.department_record_type = 'subject'; ELSEIF NEW.department_record_type IS NULL THEN SET NEW.department_record_type = 'department'; END IF; END");
    }

    private function restoreForeignKeys(): void
    {
        $this->addForeignKey('users', 'student_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey('users', 'teacher_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey('users', 'parent_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey('users', 'class_id', 'classes', 'id', 'SET NULL');
        $this->addForeignKey('users', 'school_year_id', 'school_years', 'id', 'SET NULL');
        $this->addForeignKey('users', 'primary_subject_id', 'subjects', 'id', 'SET NULL');
        $this->addForeignKey('users', 'department_id', 'teacher_departments', 'id', 'SET NULL');

        $this->addForeignKey('classes', 'school_year_id', 'school_years', 'id', 'CASCADE');
        $this->addForeignKey('classes', 'homeroom_teacher_id', 'users', 'id', 'SET NULL');
        $this->addForeignKey('semesters', 'school_year_id', 'school_years', 'id', 'CASCADE');

        foreach ([
            ['teaching_assignments', 'class_id', 'classes', 'id', 'CASCADE'],
            ['teaching_assignments', 'subject_id', 'subjects', 'id', 'CASCADE'],
            ['teaching_assignments', 'school_year_id', 'school_years', 'id', 'CASCADE'],
            ['teaching_assignments', 'semester_id', 'semesters', 'id', 'SET NULL'],
            ['conducts', 'class_id', 'classes', 'id', 'CASCADE'],
            ['conducts', 'semester_id', 'semesters', 'id', 'CASCADE'],
            ['conducts', 'school_year_id', 'school_years', 'id', 'CASCADE'],
            ['student_class_assignments', 'class_id', 'classes', 'id', 'CASCADE'],
            ['student_class_assignments', 'academic_year_id', 'school_years', 'id', 'CASCADE'],
            ['timetables', 'class_id', 'classes', 'id', 'CASCADE'],
            ['timetables', 'school_year_id', 'school_years', 'id', 'CASCADE'],
            ['timetables', 'semester_id', 'semesters', 'id', 'CASCADE'],
            ['parent_leave_requests', 'class_id', 'classes', 'id', 'SET NULL'],
            ['parent_leave_requests', 'reviewed_by', 'users', 'id', 'SET NULL'],
            ['messages', 'sender_user_id', 'users', 'id', 'CASCADE'],
            ['messages', 'receiver_user_id', 'users', 'id', 'CASCADE'],
            ['system_settings', 'default_school_year_id', 'school_years', 'id', 'SET NULL'],
        ] as [$table, $column, $referencesTable, $referencesColumn, $deleteRule]) {
            $this->addForeignKey($table, $column, $referencesTable, $referencesColumn, $deleteRule);
        }

        foreach (['attendance_records', 'conducts', 'rewards', 'student_class_assignments', 'student_transfers', 'tuition_fees'] as $table) {
            $this->addForeignKey($table, 'student_id', 'users', 'id', 'CASCADE');
        }

        $this->addForeignKey('parent_leave_requests', 'student_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('parent_leave_requests', 'parent_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('parent_student', 'student_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('parent_student', 'parent_id', 'users', 'id', 'CASCADE');

        foreach ([
            ['substitute_teachings', 'original_teacher_id', 'SET NULL'],
            ['substitute_teachings', 'substitute_teacher_id', 'CASCADE'],
            ['teacher_departments', 'leader_teacher_id', 'SET NULL'],
            ['teaching_assignments', 'teacher_id', 'CASCADE'],
        ] as [$table, $column, $deleteRule]) {
            $this->addForeignKey($table, $column, 'users', 'id', $deleteRule);
        }
    }

    private function addForeignKey(string $table, string $column, string $referencesTable, string $referencesColumn, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $name = substr($table . '_' . $column . '_consolidated_fk', 0, 64);

        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $name]
        );

        if ($exists) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencesTable}` (`{$referencesColumn}`) ON DELETE {$onDelete}");
    }
};
