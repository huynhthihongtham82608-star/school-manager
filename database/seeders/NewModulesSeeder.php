<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NewModulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFeeSettings();
        $this->seedTuitionFees();
        $this->seedRewards();
        $this->seedSubstituteTeachings();
    }

    private function seedFeeSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'tuition_fee_items'],
            [
                'group' => 'tuition_rules',
                'value' => json_encode([
                    ['key' => 'tuition_hk1', 'label' => 'Hoc phi', 'amount' => 1200000],
                    ['key' => 'health_insurance', 'label' => 'Bao hiem Y te', 'amount' => 680000],
                    ['key' => 'accident_insurance', 'label' => 'Bao hiem Tai nan', 'amount' => 150000],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedTuitionFees(): void
    {
        if (! Schema::hasTable('tuition_fees') || DB::table('tuition_fees')->exists()) {
            return;
        }

        $tuitionColumns = collect(['fee_items', 'payment_method', 'exemption_type'])
            ->every(fn (string $column) => Schema::hasColumn('tuition_fees', $column));

        if (! $tuitionColumns) {
            return;
        }

        $semesterId = $this->currentSemesterId();
        $schoolYearId = $this->currentSchoolYearId();

        $items = [
            ['key' => 'tuition_hk1', 'label' => 'Hoc phi', 'amount' => 1200000, 'status' => 'unpaid'],
            ['key' => 'health_insurance', 'label' => 'Bao hiem Y te', 'amount' => 680000, 'status' => 'unpaid'],
            ['key' => 'accident_insurance', 'label' => 'Bao hiem Tai nan', 'amount' => 150000, 'status' => 'unpaid'],
        ];

        Student::query()
            ->select('id', 'class_id', 'school_year_id')
            ->whereNotNull('class_id')
            ->orderBy('student_code')
            ->chunk(100, function ($students) use ($items, $semesterId, $schoolYearId) {
                foreach ($students as $student) {
                    DB::table('tuition_fees')->insert([
                        'id' => (string) Str::uuid(),
                        'student_id' => $student->id,
                        'class_id' => $student->class_id,
                        'semester_id' => $semesterId,
                        'school_year_id' => $student->school_year_id ?: $schoolYearId,
                        'amount' => collect($items)->sum('amount'),
                        'fee_items' => json_encode($items, JSON_UNESCAPED_UNICODE),
                        'status' => 'unpaid',
                        'payment_method' => null,
                        'exemption_type' => 'default',
                        'paid_at' => null,
                        'note' => 'Du lieu mau khoi tao sau khi import database cu.',
                        'updated_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function seedRewards(): void
    {
        if (
            ! Schema::hasTable('rewards') ||
            DB::table('rewards')->exists()
        ) {
            return;
        }

        $student = Student::query()
            ->select('id', 'class_id', 'school_year_id')
            ->whereNotNull('class_id')
            ->orderBy('student_code')
            ->first();

        if (! $student) {
            return;
        }

        $semesterId = $this->currentSemesterId();

        DB::table('rewards')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'semester_id' => $semesterId,
            'school_year_id' => $student->school_year_id,
            'reward_type' => 'good',
            'decision_number' => 'QD-MAU-001',
            'detail' => 'Du lieu mau khen thuong sau khi import database cu.',
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSubstituteTeachings(): void
    {
        if (
            ! Schema::hasTable('substitute_teachings') ||
            ! Schema::hasTable('timetable_entries') ||
            DB::table('substitute_teachings')->exists()
        ) {
            return;
        }

        $entry = DB::table('timetable_entries')
            ->select('id', 'teacher_id', 'timetable_id', 'period')
            ->orderBy('id')
            ->first();

        if (! $entry) {
            return;
        }

        $timetable = Schema::hasTable('timetables')
            ? DB::table('timetables')->select('class_id', 'semester_id', 'school_year_id')->where('id', $entry->timetable_id)->first()
            : null;

        if (! $timetable || ! $timetable->class_id) {
            return;
        }

        $substituteTeacherId = Teacher::query()
            ->whereKeyNot($entry->teacher_id)
            ->value('id') ?: $entry->teacher_id;

        DB::table('substitute_teachings')->insert([
            'id' => (string) Str::uuid(),
            'substitute_date' => now()->toDateString(),
            'scope_type' => 'period',
            'from_date' => null,
            'to_date' => null,
            'timetable_entry_id' => $entry->id,
            'class_id' => $timetable?->class_id,
            'semester_id' => $timetable?->semester_id,
            'school_year_id' => $timetable?->school_year_id,
            'original_teacher_id' => $entry->teacher_id,
            'substitute_teacher_id' => $substituteTeacherId,
            'status' => 'pending',
            'note' => 'Du lieu mau lich day thay sau khi import database cu.',
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function currentSemesterId(): ?string
    {
        if (! Schema::hasTable('semesters')) {
            return null;
        }

        $query = DB::table('semesters');

        if (Schema::hasColumn('semesters', 'is_active')) {
            $query->orderByDesc('is_active');
        }

        if (Schema::hasColumn('semesters', 'order')) {
            $query->orderBy('order');
        }

        return $query->value('id');
    }

    private function currentSchoolYearId(): ?string
    {
        if (! Schema::hasTable('school_years')) {
            return null;
        }

        $query = DB::table('school_years');

        if (Schema::hasColumn('school_years', 'is_active')) {
            $query->orderByDesc('is_active');
        }

        return $query->value('id');
    }
}
