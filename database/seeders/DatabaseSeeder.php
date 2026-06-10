<?php

namespace Database\Seeders;

use App\Models\Conduct;
use App\Models\GradeWindow;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\ParentProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // School year + semesters
        $year = SchoolYear::updateOrCreate(
            ['name' => '2024-2025'],
            ['start_date' => '2024-08-01', 'end_date' => '2025-05-31', 'is_active' => 1]
        );

        $hk1 = Semester::updateOrCreate(
            ['name' => 'HK1', 'school_year_id' => $year->id],
            ['order' => 1, 'is_score_input_open' => 1]
        );
        $hk2 = Semester::updateOrCreate(
            ['name' => 'HK2', 'school_year_id' => $year->id],
            ['order' => 2, 'is_score_input_open' => 1]
        );

        // Subjects
        $toan = Subject::updateOrCreate(['name' => 'Toan'], ['credit' => 1, 'is_weighted' => 0]);
        $van = Subject::updateOrCreate(['name' => 'Ngu van'], ['credit' => 1, 'is_weighted' => 0]);
        $anh = Subject::updateOrCreate(['name' => 'Tieng Anh'], ['credit' => 1, 'is_weighted' => 0]);

        // Teachers
        $teacherToan = Teacher::updateOrCreate(
            ['teacher_code' => 'GV001'],
            ['name' => 'Nguyen Van Toan', 'phone' => '0901234567', 'email' => 'gvtoan@example.com', 'qualification' => 'DH', 'main_subject' => 'Toan', 'is_homeroom' => 0]
        );
        $teacherGvcn = Teacher::updateOrCreate(
            ['teacher_code' => 'GV002'],
            ['name' => 'Tran Thi Chu Nhiem', 'phone' => '0908888888', 'email' => 'gvcn@example.com', 'qualification' => 'DH', 'main_subject' => 'Ngu van', 'is_homeroom' => 1]
        );

        // Classes
        $class10a1 = SchoolClass::updateOrCreate(
            ['name' => '10A1', 'school_year_id' => $year->id],
            ['grade_level' => 10, 'homeroom_teacher_id' => $teacherGvcn->id, 'capacity' => 45]
        );
        $class10a2 = SchoolClass::updateOrCreate(
            ['name' => '10A2', 'school_year_id' => $year->id],
            ['grade_level' => 10, 'homeroom_teacher_id' => null, 'capacity' => 45]
        );

        // Students
        $student1 = Student::updateOrCreate(
            ['student_code' => 'HS001'],
            ['name' => 'Le Minh Anh', 'gender' => 'male', 'dob' => '2009-09-20', 'parent_phone' => '0911222333', 'class_id' => $class10a1->id, 'school_year_id' => $year->id, 'status' => 'studying']
        );
        $student2 = Student::updateOrCreate(
            ['student_code' => 'HS002'],
            ['name' => 'Pham Thu Ha', 'gender' => 'female', 'dob' => '2009-06-15', 'parent_phone' => '0944555666', 'class_id' => $class10a1->id, 'school_year_id' => $year->id, 'status' => 'studying']
        );

        // Users
        User::updateOrCreate(
            ['username' => 'admin'],
            ['role' => 'admin', 'password_hash' => Hash::make('admin123'), 'is_active' => 1]
        );

        User::updateOrCreate(
            ['username' => 'gvtoan'],
            ['role' => 'teacher', 'teacher_id' => $teacherToan->id, 'password_hash' => Hash::make('gv123'), 'is_active' => 1]
        );

        User::updateOrCreate(
            ['username' => 'gvcn10a1'],
            ['role' => 'homeroom', 'teacher_id' => $teacherGvcn->id, 'password_hash' => Hash::make('gv123'), 'is_active' => 1]
        );

        User::updateOrCreate(
            ['username' => 'hs001'],
            ['role' => 'student', 'student_id' => $student1->id, 'password_hash' => Hash::make('hs123'), 'is_active' => 1]
        );

        // Parent sample
        $parent = ParentProfile::updateOrCreate(
            ['phone' => '0909999999'],
            ['name' => 'Phu huynh HS001', 'email' => 'ph001@example.com', 'address' => '']
        );
        $parent->students()->syncWithoutDetaching([$student1->id => ['relation' => 'PH']]);
        User::updateOrCreate(
            ['username' => 'ph001'],
            ['role' => 'parent', 'parent_id' => $parent->id, 'password_hash' => Hash::make('ph123'), 'is_active' => 1]
        );

        // Teaching assignments
        TeachingAssignment::updateOrCreate(
            ['teacher_id' => $teacherToan->id, 'class_id' => $class10a1->id, 'subject_id' => $toan->id, 'school_year_id' => $year->id],
            []
        );
        TeachingAssignment::updateOrCreate(
            ['teacher_id' => $teacherGvcn->id, 'class_id' => $class10a1->id, 'subject_id' => $van->id, 'school_year_id' => $year->id],
            []
        );

        // Grade window: open for 10A1-Toan-HK1
        GradeWindow::updateOrCreate(
            ['class_id' => $class10a1->id, 'subject_id' => $toan->id, 'semester_id' => $hk1->id, 'school_year_id' => $year->id],
            ['is_open' => 1]
        );

        // Conduct sample
        Conduct::updateOrCreate(
            ['student_id' => $student1->id, 'semester_id' => $hk1->id, 'school_year_id' => $year->id],
            ['class_id' => $class10a1->id, 'conduct_level' => 'excellent', 'comment' => 'Cham ngoan']
        );

        // Score sample
        $header = ScoreHeader::updateOrCreate(
            ['student_id' => $student1->id, 'subject_id' => $toan->id, 'semester_id' => $hk1->id, 'school_year_id' => $year->id],
            ['average' => 8.50]
        );

        // Keep it simple: ensure at least one score detail.
        ScoreDetail::updateOrCreate(
            ['score_header_id' => $header->id, 'type' => 'final', 'value' => 9.00, 'weight_group' => 3],
            []
        );
    }
}
