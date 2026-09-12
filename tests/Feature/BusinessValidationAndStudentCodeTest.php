<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\SystemRegulationController;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\User;
use App\Rules\BusinessText;
use App\Rules\PhoneNumber;
use App\Support\StudentCodeGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BusinessValidationAndStudentCodeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        view()->share('errors', new ViewErrorBag());
    }

    public function test_business_text_rule_blocks_invalid_special_characters_without_blocking_vietnamese_text(): void
    {
        $valid = Validator::make([
            'name' => 'Nguyễn Văn An - Lớp 10A1',
        ], [
            'name' => ['required', new BusinessText('Tên')],
        ]);

        $invalid = Validator::make([
            'name' => 'Nguyễn Văn An @#$',
        ], [
            'name' => ['required', new BusinessText('Tên')],
        ]);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
        $this->assertStringContainsString('ký tự đặc biệt', $invalid->errors()->first('name'));
    }

    public function test_phone_rule_accepts_digits_only(): void
    {
        $valid = Validator::make(['phone' => '0901234567'], ['phone' => [new PhoneNumber()]]);
        $invalidWithSpace = Validator::make(['phone' => '090 1234567'], ['phone' => [new PhoneNumber()]]);
        $invalidWithDash = Validator::make(['phone' => '090-1234567'], ['phone' => [new PhoneNumber()]]);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalidWithSpace->fails());
        $this->assertTrue($invalidWithDash->fails());
    }

    public function test_student_code_prefix_uses_grade_10_admission_year_for_existing_grade_level(): void
    {
        $year = $this->makeSchoolYear('2032 - 2033');
        $grade10 = $this->makeClass($year, '10A code ' . Str::random(4), 10);
        $grade11 = $this->makeClass($year, '11A code ' . Str::random(4), 11);
        $grade12 = $this->makeClass($year, '12A code ' . Str::random(4), 12);

        $this->assertStringStartsWith('HS2032', StudentCodeGenerator::nextForClass($grade10));
        $this->assertStringStartsWith('HS2031', StudentCodeGenerator::nextForClass($grade11));
        $this->assertStringStartsWith('HS2030', StudentCodeGenerator::nextForClass($grade12));
    }

    public function test_student_store_generates_code_from_class_school_year_and_grade_not_enrollment_date(): void
    {
        $admin = User::create([
            'username' => 'admin_student_code_' . Str::lower(Str::random(8)),
            'full_name' => 'Admin Student Code',
            'role' => 'admin',
            'is_active' => true,
            'password_hash' => bcrypt('secret'),
        ]);
        $year = $this->makeSchoolYear('2034 - 2035');
        $class = $this->makeClass($year, '11A store ' . Str::random(4), 11);

        $this->actingAs($admin);

        $request = Request::create('/students', 'POST', [
            'name' => 'Học sinh kiểm thử mã',
            'gender' => Student::GENDER_NAM,
            'dob' => '2018-01-01',
            'address' => 'Cần Thơ',
            'place_of_birth' => 'Cần Thơ',
            'ethnicity_choice' => 'Kinh',
            'religion_choice' => 'Không',
            'parent_phone' => '090' . random_int(1000000, 9999999),
            'parent_name' => 'Phụ huynh kiểm thử',
            'parent_relation' => ParentProfile::RELATION_GUARDIAN,
            'parent_address' => 'Cần Thơ',
            'enrollment_date' => '2034-09-01',
            'admission_type' => Student::ADMISSION_TRANSFER,
            'transfer_grade_level' => 11,
            'previous_school' => 'THCS Kiểm Thử',
            'previous_class' => '10A',
            'class_id' => $class->getKey(),
            'school_year_id' => $year->getKey(),
            'status' => Student::STATUS_STUDYING,
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $admin);

        app(StudentController::class)->store($request);

        $student = Student::where('name', 'Học sinh kiểm thử mã')->firstOrFail();

        $this->assertStringStartsWith('HS2033', $student->student_code);
    }

    public function test_student_store_forces_new_student_status_to_studying(): void
    {
        $admin = $this->makeUser('admin');
        $year = $this->makeSchoolYear('2035 - 2036');
        $class = $this->makeClass($year, '10A status ' . Str::random(4), 10);

        $this->actingAs($admin);

        $request = Request::create('/students', 'POST', [
            'name' => 'Hoc sinh trang thai moi',
            'gender' => Student::GENDER_NAM,
            'dob' => '2019-01-01',
            'address' => 'Can Tho',
            'place_of_birth' => 'Can Tho',
            'ethnicity_choice' => 'Kinh',
            'religion_choice' => 'Không',
            'parent_phone' => '091' . random_int(1000000, 9999999),
            'parent_name' => 'Phu huynh trang thai',
            'parent_relation' => ParentProfile::RELATION_GUARDIAN,
            'parent_address' => 'Can Tho',
            'enrollment_date' => '2035-09-01',
            'admission_type' => Student::ADMISSION_NEW,
            'class_id' => $class->getKey(),
            'school_year_id' => $year->getKey(),
            'status' => Student::STATUS_GRADUATED,
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $admin);

        app(StudentController::class)->store($request);

        $student = Student::where('name', 'Hoc sinh trang thai moi')->firstOrFail();
        $this->assertSame(Student::STATUS_STUDYING, $student->status);
    }

    public function test_student_without_business_data_can_be_deleted_with_parent_link_cleanup(): void
    {
        $admin = $this->makeUser('admin');
        $year = $this->makeSchoolYear('2036 - 2037');
        $class = $this->makeClass($year, '10A delete ' . Str::random(4), 10);
        $student = $this->makeStudent($class, 'DELETE');
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Phu huynh delete',
            'phone' => '092' . random_int(1000000, 9999999),
        ]);
        DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => ParentProfile::RELATION_GUARDIAN,
        ]);
        DB::table('student_movements')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'transfer',
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'to_class_id' => $class->id,
            'movement_date' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        app(StudentController::class)->destroy($student);

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('parent_student', ['student_id' => $student->id]);
        $this->assertDatabaseMissing('student_movements', ['student_id' => $student->id]);
    }

    public function test_tuition_fee_config_cannot_remove_used_fee_item(): void
    {
        $year = $this->makeSchoolYear('2037 - 2038');
        $semester = Semester::create([
            'name' => 'Hoc ky 1',
            'order' => 1,
            'school_year_id' => $year->id,
            'status' => Semester::STATUS_ACTIVE,
        ]);
        $class = $this->makeClass($year, '10A fee ' . Str::random(4), 10);
        $student = $this->makeStudent($class, 'FEE');
        Setting::putValue('tuition_fee_items', json_encode([
            ['key' => 'used_fee', 'label' => 'Khoan thu da dung', 'amount' => 100000],
            ['key' => 'kept_fee', 'label' => 'Khoan thu giu lai', 'amount' => 50000],
        ], JSON_UNESCAPED_UNICODE), 'tuition_rules');
        TuitionFee::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'semester_id' => $semester->id,
            'school_year_id' => $year->id,
            'amount' => 100000,
            'fee_items' => [
                ['key' => 'used_fee', 'label' => 'Khoan thu da dung', 'amount' => 100000],
            ],
            'status' => TuitionFee::STATUS_UNPAID,
        ]);

        $request = Request::create('/system/tuition-levels', 'PUT', [
            'fee_items' => [
                ['key' => 'kept_fee', 'label' => 'Khoan thu giu lai', 'amount' => 50000],
            ],
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $this->expectException(ValidationException::class);

        app(SystemRegulationController::class)->updateTuitionLevels($request);
    }

    public function test_parent_without_business_data_can_be_deleted_with_student_link_cleanup(): void
    {
        $year = $this->makeSchoolYear('2038 - 2039');
        $class = $this->makeClass($year, '10A parent delete ' . Str::random(4), 10);
        $student = $this->makeStudent($class, 'PARENT_DELETE');
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Phu huynh tao nham',
            'phone' => '093' . random_int(1000000, 9999999),
        ]);
        DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => ParentProfile::RELATION_GUARDIAN,
        ]);

        app(\App\Http\Controllers\ParentController::class)->destroy($parent);

        $this->assertDatabaseMissing('users', ['id' => $parent->id]);
        $this->assertDatabaseMissing('parent_student', ['parent_id' => $parent->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    private function makeSchoolYear(string $name): SchoolYear
    {
        [$start, $end] = array_map('intval', explode(' - ', $name));

        return SchoolYear::create([
            'name' => $name,
            'start_date' => sprintf('%04d-08-01', $start),
            'end_date' => sprintf('%04d-05-31', $end),
            'is_active' => true,
        ]);
    }

    private function makeClass(SchoolYear $year, string $name, int $gradeLevel): SchoolClass
    {
        return SchoolClass::create([
            'name' => $name,
            'grade_level' => $gradeLevel,
            'school_year_id' => $year->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
    }

    private function makeStudent(SchoolClass $class, string $suffix): Student
    {
        return Student::create([
            'student_code' => 'HS' . Str::upper(Str::random(10)),
            'name' => 'Hoc sinh ' . $suffix,
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'enrollment_date' => now()->toDateString(),
            'admission_type' => Student::ADMISSION_NEW,
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
            'status' => Student::STATUS_STUDYING,
        ]);
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'username' => $role . '_' . Str::lower(Str::random(10)),
            'full_name' => ucfirst($role) . ' test',
            'role' => $role,
            'role_type' => $role,
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
            'login_status' => true,
        ]);
    }
}
