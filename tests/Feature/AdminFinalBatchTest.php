<?php

namespace Tests\Feature;

use App\Http\Controllers\MessageController;
use App\Http\Controllers\BulkExcelController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Models\AuditLog;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Tests\TestCase;

class AdminFinalBatchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        view()->share('errors', new ViewErrorBag());
    }

    public function test_message_show_marks_only_current_recipient_as_read(): void
    {
        $sender = $this->makeUser('admin');
        $firstRecipient = $this->makeUser('parent');
        $secondRecipient = $this->makeUser('parent');

        $message = Message::create([
            'sender_user_id' => $sender->id,
            'receiver_user_id' => $firstRecipient->id,
            'title' => 'Kiểm tra trạng thái đọc',
            'content' => 'Nội dung kiểm thử',
            'target_type' => 'manual',
            'recipient_summary' => 'Phụ huynh',
            'is_read' => false,
            'created_at' => now(),
        ]);
        $message->update(['conversation_id' => $message->id]);

        $firstRow = $message->recipients()->create([
            'receiver_user_id' => $firstRecipient->id,
            'is_read' => false,
        ]);
        $secondRow = $message->recipients()->create([
            'receiver_user_id' => $secondRecipient->id,
            'is_read' => false,
        ]);

        $this->actingAs($firstRecipient);
        $request = Request::create('/messages/' . $message->id, 'GET');
        $request->headers->set('Accept', 'application/json');
        $response = app(MessageController::class)->show($request, $message);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Đã đọc', $response->getData(true)['read_label'] ?? null);

        $this->assertTrue((bool) $firstRow->fresh()->is_read);
        $this->assertFalse((bool) $secondRow->fresh()->is_read);

        $this->actingAs($sender);
        $sentView = app(MessageController::class)->sent(Request::create('/messages/sent', 'GET'));
        $this->assertStringContainsString('1/2 đã đọc', $sentView->render());
    }

    public function test_sent_messages_render_recipient_read_status(): void
    {
        $sender = $this->makeUser('admin');
        $readRecipient = $this->makeUser('parent');
        $unreadRecipient = $this->makeUser('parent');

        $message = Message::create([
            'sender_user_id' => $sender->id,
            'receiver_user_id' => $readRecipient->id,
            'title' => 'Theo dõi người nhận',
            'content' => 'Nội dung kiểm thử',
            'target_type' => 'manual',
            'recipient_summary' => 'Phụ huynh',
            'is_read' => false,
            'created_at' => now(),
        ]);
        $message->update(['conversation_id' => $message->id]);
        $message->recipients()->create([
            'receiver_user_id' => $readRecipient->id,
            'is_read' => true,
            'read_at' => now(),
        ]);
        $message->recipients()->create([
            'receiver_user_id' => $unreadRecipient->id,
            'is_read' => false,
        ]);

        $this->actingAs($sender);

        $view = app(MessageController::class)->sent(Request::create('/messages/sent', 'GET'));
        $this->assertInstanceOf(View::class, $view);

        $html = $view->render();
        $this->assertStringContainsString('message-card', $html);
        $this->assertStringContainsString('_messageFloatingMenu', $html);
        $this->assertStringContainsString('1/2 đã đọc', $html);
        $this->assertStringContainsString('Xem người nhận', $html);
        $this->assertStringContainsString('Đã đọc', $html);
        $this->assertStringContainsString('Chưa đọc', $html);
    }

    public function test_message_dropdown_script_flips_near_viewport_bottom_and_audit_logs_hide_technical_modules(): void
    {
        $script = file_get_contents(resource_path('views/messages/_dropdown_positioning.blade.php'));

        $this->assertStringContainsString('opensAbove', $script);
        $this->assertStringContainsString('maxHeight', $script);
        $this->assertStringContainsString('overflowY', $script);

        $tuitionLog = new AuditLog(['module' => 'TuitionFee', 'action' => 'tuition_fee_updated']);
        $substituteLog = new AuditLog(['module' => 'SubstituteTeaching', 'action' => 'substitute_teaching_created']);

        $this->assertSame(AuditLog::moduleLabelFor(\App\Models\TuitionFee::class, 'tuition_fee_updated'), $tuitionLog->moduleLabel());
        $this->assertSame(AuditLog::moduleLabelFor(\App\Models\SubstituteTeaching::class, 'substitute_teaching_created'), $substituteLog->moduleLabel());
    }

    public function test_parent_store_reuses_existing_phone_instead_of_creating_duplicate_parent(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        $phone = '09' . random_int(10000000, 99999999);
        $parent = ParentProfile::create([
            'parent_code' => 'TP' . Str::upper(Str::random(6)),
            'name' => 'Phụ huynh kiểm thử',
            'phone' => $phone,
        ]);
        $student = Student::create([
            'student_code' => 'TS' . Str::upper(Str::random(8)),
            'name' => 'Học sinh kiểm thử',
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'enrollment_date' => now()->toDateString(),
            'admission_type' => Student::ADMISSION_NEW,
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
            'status' => Student::STATUS_STUDYING,
        ]);

        $this->actingAs($admin);
        $request = Request::create('/parents', 'POST', [
            'name' => 'Phụ huynh kiểm thử cập nhật',
            'relation' => ParentProfile::RELATION_GUARDIAN,
            'phone' => $phone,
            'student_ids' => [$student->id],
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = app(\App\Http\Controllers\ParentController::class)->store($request);

        $this->assertSame(route('parents.index'), $response->getTargetUrl());

        $this->assertSame(1, ParentProfile::where('phone', $phone)->count());
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_student_parent_update_reuses_parent_and_preserves_sibling_links(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        $oldPhone = '08' . random_int(10000000, 99999999);
        $newPhone = '07' . random_int(10000000, 99999999);
        $oldParent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Phu huynh cu',
            'phone' => $oldPhone,
        ]);
        $newParent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Phu huynh moi',
            'phone' => $newPhone,
        ]);
        $studentA = $this->makeStudent($class, 'A');
        $studentB = $this->makeStudent($class, 'B');

        DB::table('parent_student')->insert([
            ['parent_id' => $oldParent->id, 'student_id' => $studentA->id, 'relation' => ParentProfile::RELATION_GUARDIAN],
            ['parent_id' => $oldParent->id, 'student_id' => $studentB->id, 'relation' => ParentProfile::RELATION_GUARDIAN],
        ]);

        $this->actingAs($admin);
        app(StudentController::class)->update($this->studentUpdateRequest($admin, $studentA, [
            'parent_phone' => '',
        ]), $studentA);

        $this->assertSame(1, ParentProfile::where('phone', $oldPhone)->count());
        $this->assertDatabaseHas('parent_student', ['parent_id' => $oldParent->id, 'student_id' => $studentA->id]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $oldParent->id, 'student_id' => $studentB->id]);

        app(StudentController::class)->update($this->studentUpdateRequest($admin, $studentA->fresh(), [
            'parent_name' => $newParent->name,
            'parent_relation' => ParentProfile::RELATION_GUARDIAN,
            'parent_phone' => $newPhone,
        ]), $studentA->fresh());

        $this->assertSame(1, ParentProfile::where('phone', $newPhone)->count());
        $this->assertDatabaseHas('parent_student', ['parent_id' => $newParent->id, 'student_id' => $studentA->id]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $oldParent->id, 'student_id' => $studentB->id]);
        $this->assertDatabaseMissing('parent_student', ['parent_id' => $oldParent->id, 'student_id' => $studentA->id]);
    }

    public function test_student_edit_updates_current_parent_phone_without_creating_duplicate(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        $oldPhone = '05' . random_int(10000000, 99999999);
        $newPhone = '04' . random_int(10000000, 99999999);
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Parent same guardian',
            'phone' => $oldPhone,
        ]);
        $studentA = $this->makeStudent($class, 'SGA');
        $studentB = $this->makeStudent($class, 'SGB');
        DB::table('parent_student')->insert([
            ['parent_id' => $parent->id, 'student_id' => $studentA->id, 'relation' => ParentProfile::RELATION_GUARDIAN],
            ['parent_id' => $parent->id, 'student_id' => $studentB->id, 'relation' => ParentProfile::RELATION_GUARDIAN],
        ]);
        $parentCountBefore = ParentProfile::count();

        $this->actingAs($admin);
        app(StudentController::class)->update($this->studentUpdateRequest($admin, $studentA, [
            'parent_id' => $parent->id,
            'parent_name' => 'Parent same guardian updated',
            'parent_relation' => ParentProfile::RELATION_GUARDIAN,
            'parent_phone' => $newPhone,
        ]), $studentA);

        $this->assertSame($parentCountBefore, ParentProfile::count());
        $this->assertSame($newPhone, $parent->fresh()->phone);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentA->id]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentB->id]);
        $this->assertSame($newPhone, $studentA->fresh()->parents()->first()?->phone);
        $this->assertSame($newPhone, $studentB->fresh()->parents()->first()?->phone);
        $this->assertSame($newPhone, $studentB->fresh()->parent_phone);
    }

    public function test_import_parent_sync_reuses_phone_without_duplicate_parent(): void
    {
        $class = SchoolClass::firstOrFail();
        $phone = '06' . random_int(10000000, 99999999);
        $parentCode = 'PH' . Str::upper(Str::random(6));
        $studentA = $this->makeStudent($class, 'IA');
        $studentB = $this->makeStudent($class, 'IB');
        $row = [
            'ma_phu_huynh' => $parentCode,
            'sdt_phu_huynh' => $phone,
            'ho_ten_phu_huynh' => 'Phu huynh import',
            'email_phu_huynh' => 'parent_' . Str::lower(Str::random(8)) . '@example.test',
            'dia_chi_phu_huynh' => 'Dia chi import',
        ];
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'syncImportedParentForStudent');
        $method->setAccessible(true);

        $method->invoke($controller, $studentA, $row);
        $method->invoke($controller, $studentB, $row);

        $this->assertSame(1, ParentProfile::where('phone', $phone)->count());
        $parent = ParentProfile::where('phone', $phone)->firstOrFail();
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentA->id]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentB->id]);
    }

    public function test_import_existing_student_updates_current_parent_contact_and_rejects_different_parent(): void
    {
        $class = SchoolClass::firstOrFail();
        $oldPhone = '03' . random_int(10000000, 99999999);
        $changedPhone = '02' . random_int(10000000, 99999999);
        $conflictPhone = '01' . random_int(10000000, 99999999);
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Import parent current',
            'phone' => $oldPhone,
        ]);
        $otherParent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Import parent other',
            'phone' => $conflictPhone,
        ]);
        $student = $this->makeStudent($class, 'IPC');
        DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => ParentProfile::RELATION_GUARDIAN,
        ]);
        $parentCountBefore = ParentProfile::count();
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'syncImportedParentForStudent');
        $method->setAccessible(true);

        $method->invoke($controller, $student, [
            'sdt_phu_huynh' => $changedPhone,
            'ho_ten_phu_huynh' => 'Import parent current updated',
        ]);

        $this->assertSame($parentCountBefore, ParentProfile::count());
        $this->assertSame($changedPhone, $parent->fresh()->phone);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $student->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, $student->fresh(), [
            'sdt_phu_huynh' => $otherParent->phone,
            'ho_ten_phu_huynh' => $otherParent->name,
        ]);
    }

    public function test_admin_parent_update_and_student_parent_column_use_canonical_phone(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        $oldPhone = '09' . random_int(10000000, 99999999);
        $newPhone = '09' . random_int(10000000, 99999999);
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Canonical parent',
            'phone' => $oldPhone,
        ]);
        $student = $this->makeStudent($class, 'CAN');
        DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => ParentProfile::RELATION_GUARDIAN,
        ]);

        $this->actingAs($admin);
        $request = Request::create('/parents/' . $parent->id, 'PUT', [
            'name' => $parent->name,
            'relation' => ParentProfile::RELATION_GUARDIAN,
            'phone' => $newPhone,
            'student_ids' => [$student->id],
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);
        app(\App\Http\Controllers\ParentController::class)->update($request, $parent);

        $studentHtml = app(StudentController::class)->index(Request::create('/students', 'GET', ['q' => $newPhone]))->render();
        $parentHtml = app(\App\Http\Controllers\ParentController::class)->index(Request::create('/parents', 'GET', ['q' => $newPhone]))->render();

        $this->assertStringContainsString('Canonical parent', $studentHtml);
        $this->assertStringContainsString($newPhone, $studentHtml);
        $this->assertStringContainsString($newPhone, $parentHtml);
    }

    public function test_search_students_parents_and_teachers_by_phone(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        session(['working_school_year_id' => $class->school_year_id]);
        $parentPhone = '07' . random_int(10000000, 99999999);
        $teacherPhone = '08' . random_int(10000000, 99999999);
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Search phone parent',
            'phone' => $parentPhone,
        ]);
        $student = $this->makeStudent($class, 'SEA');
        DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => ParentProfile::RELATION_GUARDIAN,
        ]);
        \App\Models\Teacher::create([
            'teacher_code' => 'GV' . random_int(100000, 999999),
            'name' => 'Search phone teacher',
            'phone' => $teacherPhone,
            'work_status' => \App\Models\Teacher::STATUS_WORKING,
            'primary_subject_id' => \App\Models\Subject::firstOrFail()->id,
        ]);

        $this->actingAs($admin);
        $studentHtml = app(StudentController::class)->index(Request::create('/students', 'GET', ['q' => $parentPhone]))->render();
        $parentHtml = app(\App\Http\Controllers\ParentController::class)->index(Request::create('/parents', 'GET', ['q' => $parentPhone]))->render();
        $teacherHtml = app(\App\Http\Controllers\TeacherController::class)->index(Request::create('/teachers', 'GET', ['q' => $teacherPhone]))->render();

        $this->assertStringContainsString($student->student_code, $studentHtml);
        $this->assertStringContainsString('Search phone parent', $parentHtml);
        $this->assertStringContainsString('Search phone teacher', $teacherHtml);
    }

    public function test_bulk_student_preview_classifies_business_identity_rows(): void
    {
        $class = SchoolClass::firstOrFail();
        $existing = $this->makeStudent($class, 'BUP');
        $similar = $this->makeStudent($class, 'SIM');
        $similar->update(['name' => 'Similar Student', 'dob' => '2010-05-05']);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'validateStudentRows');
        $method->setAccessible(true);

        $result = $method->invoke($controller, [
            [
                'ma_hs' => $existing->student_code,
                'ho_ten' => $existing->name,
                'ngay_sinh' => $existing->dob->format('Y-m-d'),
                'gioi_tinh' => 'Nam',
            ],
            [
                'ma_hs' => 'HSNEW' . Str::upper(Str::random(5)),
                'ho_ten' => 'New Student',
                'ngay_sinh' => '2011-01-01',
                'gioi_tinh' => 'Nam',
            ],
            [
                'ma_hs' => '',
                'ho_ten' => 'Similar Student',
                'ngay_sinh' => '2010-05-05',
                'gioi_tinh' => 'Nam',
            ],
            [
                'ma_hs' => $existing->student_code,
                'ho_ten' => 'Conflicting Name',
                'ngay_sinh' => $existing->dob->format('Y-m-d'),
                'gioi_tinh' => 'Nam',
            ],
        ], ['class_id' => $class->id]);

        $this->assertSame('update', $result['rows'][0]['status']);
        $this->assertSame('new', $result['rows'][1]['status']);
        $this->assertSame('need_confirmation', $result['rows'][2]['status']);
        $this->assertFalse((bool) $result['rows'][2]['blocking']);
        $this->assertTrue((bool) $result['rows'][2]['requires_decision']);
        $this->assertCount(1, $result['rows'][2]['candidates']);
        $this->assertSame('need_confirmation', $result['rows'][3]['status']);
        $this->assertTrue((bool) $result['rows'][3]['blocking']);
        $this->assertFalse($result['valid']);
    }

    public function test_bulk_student_import_updates_by_student_code_and_creates_separate_missing_code_match(): void
    {
        $class = SchoolClass::firstOrFail();
        $existing = $this->makeStudent($class, 'EXC');
        $similar = $this->makeStudent($class, 'SNC');
        $similar->update(['name' => 'Same Name Dob', 'dob' => '2010-06-06']);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitStudents');
        $method->setAccessible(true);
        $studentCountBefore = Student::count();

        $result = $method->invoke($controller, [
            [
                '__bulk_position' => 0,
                'ma_hs' => $existing->student_code,
                'ho_ten' => $existing->name,
                'ngay_sinh' => $existing->dob->format('Y-m-d'),
                'gioi_tinh' => 'Nam',
                'dia_chi' => 'Updated address by code',
            ],
            [
                '__bulk_position' => 1,
                'ma_hs' => 'HSNEW' . Str::upper(Str::random(6)),
                'ho_ten' => 'Brand New Student',
                'ngay_sinh' => '2011-02-02',
                'gioi_tinh' => 'Nam',
            ],
            [
                '__bulk_position' => 2,
                'ma_hs' => '',
                'ho_ten' => 'Same Name Dob',
                'ngay_sinh' => '2010-06-06',
                'gioi_tinh' => 'Nam',
            ],
        ], ['class_id' => $class->id], [
            2 => ['action' => 'create_new', 'student_id' => null],
        ]);

        $this->assertSame(3, $result['affected']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(2, $result['created']);
        $this->assertSame('Updated address by code', $existing->fresh()->address);
        $this->assertSame(1, Student::where('student_code', $existing->student_code)->count());
        $this->assertSame($studentCountBefore + 2, Student::count());
        $this->assertSame(2, Student::where('name', 'Same Name Dob')->whereDate('dob', '2010-06-06')->count());
    }

    public function test_bulk_student_import_missing_code_can_update_confirmed_existing_student(): void
    {
        $class = SchoolClass::firstOrFail();
        $existing = $this->makeStudent($class, 'MCE');
        $existing->update(['name' => 'Confirmed Existing', 'dob' => '2010-07-07', 'address' => 'Old address']);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitStudents');
        $method->setAccessible(true);
        $studentCountBefore = Student::count();

        $result = $method->invoke($controller, [[
            '__bulk_position' => 4,
            'ma_hs' => '',
            'ho_ten' => 'Confirmed Existing',
            'ngay_sinh' => '2010-07-07',
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Updated via confirmed import',
        ]], ['class_id' => $class->id], [
            4 => ['action' => 'update_existing', 'student_id' => $existing->id],
        ]);

        $this->assertSame(1, $result['affected']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['created']);
        $this->assertSame($studentCountBefore, Student::count());
        $this->assertSame('Updated via confirmed import', $existing->fresh()->address);
    }

    public function test_bulk_student_import_missing_code_can_create_new_when_candidate_exists(): void
    {
        $class = SchoolClass::firstOrFail();
        $existing = $this->makeStudent($class, 'MCN');
        $existing->update(['name' => 'Create Despite Similar', 'dob' => '2010-08-08']);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitStudents');
        $method->setAccessible(true);
        $studentCountBefore = Student::count();

        $result = $method->invoke($controller, [[
            '__bulk_position' => 5,
            'ma_hs' => '',
            'ho_ten' => 'Create Despite Similar',
            'ngay_sinh' => '2010-08-08',
            'gioi_tinh' => 'Nam',
        ]], ['class_id' => $class->id], [
            5 => ['action' => 'create_new', 'student_id' => null],
        ]);

        $this->assertSame(1, $result['affected']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['created']);
        $this->assertSame($studentCountBefore + 1, Student::count());
        $this->assertSame(2, Student::where('name', 'Create Despite Similar')->whereDate('dob', '2010-08-08')->count());
    }

    public function test_bulk_student_import_missing_code_with_multiple_candidates_requires_exact_choice(): void
    {
        $class = SchoolClass::firstOrFail();
        $first = $this->makeStudent($class, 'MCA');
        $second = $this->makeStudent($class, 'MCB');
        $first->update(['name' => 'Duplicate Candidate', 'dob' => '2010-09-09', 'address' => 'First']);
        $second->update(['name' => 'Duplicate Candidate', 'dob' => '2010-09-09', 'address' => 'Second']);
        $controller = app(BulkExcelController::class);
        $validate = new \ReflectionMethod($controller, 'validateStudentRows');
        $validate->setAccessible(true);
        $commit = new \ReflectionMethod($controller, 'commitStudents');
        $commit->setAccessible(true);

        $preview = $validate->invoke($controller, [[
            'ma_hs' => '',
            'ho_ten' => 'Duplicate Candidate',
            'ngay_sinh' => '2010-09-09',
            'gioi_tinh' => 'Nam',
        ]], ['class_id' => $class->id]);

        $this->assertTrue((bool) $preview['rows'][0]['requires_decision']);
        $this->assertCount(2, $preview['rows'][0]['candidates']);

        try {
            $commit->invoke($controller, [[
                '__bulk_position' => 60,
                'ma_hs' => '',
                'ho_ten' => 'Duplicate Candidate',
                'ngay_sinh' => '2010-09-09',
                'gioi_tinh' => 'Nam',
            ]], ['class_id' => $class->id], []);
            $this->fail('Missing student_code with candidates must not auto-select a candidate.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertStringContainsString('Cập nhật học sinh hiện có', $exception->errors()['file'][0] ?? '');
        }

        $commit->invoke($controller, [[
            '__bulk_position' => 6,
            'ma_hs' => '',
            'ho_ten' => 'Duplicate Candidate',
            'ngay_sinh' => '2010-09-09',
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Selected second candidate',
        ]], ['class_id' => $class->id], [
            6 => ['action' => 'update_existing', 'student_id' => $second->id],
        ]);

        $this->assertSame('First', $first->fresh()->address);
        $this->assertSame('Selected second candidate', $second->fresh()->address);
    }

    public function test_bulk_student_import_missing_code_skip_and_tamper_do_not_write(): void
    {
        $class = SchoolClass::firstOrFail();
        $candidate = $this->makeStudent($class, 'SKP');
        $candidate->update(['name' => 'Skip Candidate', 'dob' => '2010-10-10', 'address' => 'Original']);
        $unrelated = $this->makeStudent($class, 'TMP');
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitStudents');
        $method->setAccessible(true);
        $studentCountBefore = Student::count();

        $skipResult = $method->invoke($controller, [[
            '__bulk_position' => 7,
            'ma_hs' => '',
            'ho_ten' => 'Skip Candidate',
            'ngay_sinh' => '2010-10-10',
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Should not write',
        ]], ['class_id' => $class->id], [
            7 => ['action' => 'skip', 'student_id' => null],
        ]);

        $this->assertSame(0, $skipResult['affected']);
        $this->assertSame($studentCountBefore, Student::count());
        $this->assertSame('Original', $candidate->fresh()->address);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, [[
            '__bulk_position' => 8,
            'ma_hs' => '',
            'ho_ten' => 'Skip Candidate',
            'ngay_sinh' => '2010-10-10',
            'gioi_tinh' => 'Nam',
        ]], ['class_id' => $class->id], [
            8 => ['action' => 'update_existing', 'student_id' => $unrelated->id],
        ]);
    }

    public function test_bulk_student_import_rejects_student_code_identity_mismatch(): void
    {
        $class = SchoolClass::firstOrFail();
        $existing = $this->makeStudent($class, 'MM');
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitStudents');
        $method->setAccessible(true);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, [[
            'ma_hs' => $existing->student_code,
            'ho_ten' => 'Wrong Person',
            'ngay_sinh' => $existing->dob->format('Y-m-d'),
            'gioi_tinh' => 'Nam',
        ]], ['class_id' => $class->id]);
    }

    public function test_bulk_student_parent_code_updates_contact_and_missing_code_phone_conflicts(): void
    {
        $class = SchoolClass::firstOrFail();
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Parent coded',
            'phone' => '031' . random_int(1000000, 9999999),
        ]);
        $student = $this->makeStudent($class, 'PC');
        $newPhone = '032' . random_int(1000000, 9999999);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'syncImportedParentForStudent');
        $method->setAccessible(true);

        $method->invoke($controller, $student, [
            'ma_phu_huynh' => $parent->parent_code,
            'sdt_phu_huynh' => $newPhone,
            'ho_ten_phu_huynh' => $parent->name,
        ]);

        $this->assertSame($newPhone, $parent->fresh()->phone);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $student->id]);

        $anotherStudent = $this->makeStudent($class, 'MPC');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, $anotherStudent, [
            'sdt_phu_huynh' => $newPhone,
            'ho_ten_phu_huynh' => $parent->name,
        ]);
    }

    public function test_bulk_parent_import_rejects_same_phone_with_missing_or_different_parent_code(): void
    {
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Existing parent import',
            'phone' => '033' . random_int(1000000, 9999999),
        ]);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitParents');
        $method->setAccessible(true);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, [[
            'ma_phu_huynh' => 'PH' . Str::upper(Str::random(6)),
            'ho_ten' => 'Other parent',
            'sdt' => $parent->phone,
        ]]);
    }

    public function test_parent_student_pivot_relation_is_independent_per_student(): void
    {
        $admin = $this->makeUser('admin');
        $class = SchoolClass::firstOrFail();
        $parent = ParentProfile::create([
            'parent_code' => 'PH' . Str::upper(Str::random(6)),
            'name' => 'Pivot parent',
            'phone' => '034' . random_int(1000000, 9999999),
        ]);
        $studentA = $this->makeStudent($class, 'PVA');
        $studentB = $this->makeStudent($class, 'PVB');
        DB::table('parent_student')->insert([
            ['parent_id' => $parent->id, 'student_id' => $studentA->id, 'relation' => ParentProfile::RELATION_FATHER],
            ['parent_id' => $parent->id, 'student_id' => $studentB->id, 'relation' => ParentProfile::RELATION_GUARDIAN],
        ]);

        $newPhone = '035' . random_int(1000000, 9999999);
        $this->actingAs($admin);
        app(StudentController::class)->update($this->studentUpdateRequest($admin, $studentA, [
            'parent_id' => $parent->id,
            'parent_name' => $parent->name,
            'parent_relation' => ParentProfile::RELATION_MOTHER,
            'parent_phone' => $newPhone,
        ]), $studentA);

        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentA->id, 'relation' => ParentProfile::RELATION_MOTHER]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent->id, 'student_id' => $studentB->id, 'relation' => ParentProfile::RELATION_GUARDIAN]);
        $this->assertSame($newPhone, $studentA->fresh()->parents()->first()?->phone);
        $this->assertSame($newPhone, $studentB->fresh()->parents()->first()?->phone);
    }

    public function test_bulk_teacher_import_uses_teacher_code_without_duplicate(): void
    {
        $subject = \App\Models\Subject::firstOrFail();
        $teacher = \App\Models\Teacher::create([
            'teacher_code' => 'GV' . random_int(100000, 999999),
            'name' => 'Teacher coded import',
            'phone' => '036' . random_int(1000000, 9999999),
            'work_status' => \App\Models\Teacher::STATUS_WORKING,
            'primary_subject_id' => $subject->id,
        ]);
        $controller = app(BulkExcelController::class);
        $method = new \ReflectionMethod($controller, 'commitTeachers');
        $method->setAccessible(true);

        $method->invoke($controller, [[
            'ma_gv' => $teacher->teacher_code,
            'ho_ten' => 'Teacher coded import updated',
            'gioi_tinh' => 'Nam',
            'mon_chinh' => $subject->name,
            'sdt' => $teacher->phone,
        ]]);

        $this->assertSame(1, \App\Models\Teacher::where('teacher_code', $teacher->teacher_code)->count());
        $this->assertSame('Teacher coded import updated', $teacher->fresh()->name);
    }

    public function test_report_tables_render_drilldown_modal_and_graduation_tab_from_student_status(): void
    {
        $admin = $this->makeUser('admin');
        $year = SchoolYear::firstOrFail();
        $class = SchoolClass::create([
            'name' => '12 Drill ' . Str::upper(Str::random(4)),
            'grade_level' => 12,
            'cohort' => '2024-2027',
            'school_year_id' => $year->id,
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
        $student = $this->makeStudent($class, 'GRAD');
        $student->update(['status' => Student::STATUS_GRADUATED]);

        $this->actingAs($admin);
        $request = Request::create('/reports', 'GET', [
            'report_type' => 'school_year',
            'school_year_id' => $year->id,
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $view = app(ReportController::class)->classSummary($request);
        $html = $view->render();

        $this->assertStringContainsString('report-drill-link', $html);
        $this->assertStringContainsString('data-report-detail-template', $html);
        $this->assertStringContainsString('id="reportDetailModal"', $html);
        $this->assertStringContainsString('report-student-overview', $html);
        $this->assertStringContainsString('report-student-info-item', $html);
        $this->assertStringContainsString('id="report-tab-graduation"', $html);
        $this->assertStringContainsString('data-graduation-year-filter', $html);
        $this->assertStringContainsString($student->student_code, $html);
        $this->assertStringNotContainsString('reportGraduationList', $html);
        $this->assertStringNotContainsString('href="' . route('reports.index', ['report_type' => 'student', 'student_id' => $student->id]), $html);
    }

    public function test_report_table_tab_keeps_multi_year_report_mode(): void
    {
        $admin = $this->makeUser('admin');
        $years = SchoolYear::orderBy('start_date')->take(2)->get();
        $this->assertGreaterThanOrEqual(2, $years->count());

        $this->actingAs($admin);
        $request = Request::create('/reports', 'GET', [
            'report_type' => 'multi_year',
            'from_year_id' => $years->first()->id,
            'to_year_id' => $years->last()->id,
            'table_tab' => 'class',
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $html = app(ReportController::class)->classSummary($request)->render();

        $this->assertMatchesRegularExpression('/<option value="multi_year" selected>/', $html);
        $this->assertStringContainsString('data-report-table-tab="class"', $html);
        $this->assertStringContainsString('aria-selected="true">Lớp', $html);
        $this->assertStringNotContainsString('href="' . route('reports.index', ['report_type' => 'class']), $html);
    }

    public function test_graduation_tab_filters_by_school_year_without_changing_student_status(): void
    {
        $admin = $this->makeUser('admin');
        $firstYear = SchoolYear::create([
            'name' => 'Nam hoc drill ' . Str::upper(Str::random(4)),
            'start_date' => '2030-08-01',
            'end_date' => '2031-05-31',
            'is_active' => false,
        ]);
        $secondYear = SchoolYear::create([
            'name' => 'Nam hoc trong ' . Str::upper(Str::random(4)),
            'start_date' => '2031-08-01',
            'end_date' => '2032-05-31',
            'is_active' => false,
        ]);
        $graduatedClass = SchoolClass::create([
            'name' => '12 Grad ' . Str::upper(Str::random(4)),
            'grade_level' => 12,
            'cohort' => '2028-2031',
            'school_year_id' => $firstYear->id,
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
        $emptyGraduationClass = SchoolClass::create([
            'name' => '12 Empty ' . Str::upper(Str::random(4)),
            'grade_level' => 12,
            'cohort' => '2029-2032',
            'school_year_id' => $secondYear->id,
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
        $graduated = $this->makeStudent($graduatedClass, 'G1');
        $graduated->update(['status' => Student::STATUS_GRADUATED]);
        $notGraduated = $this->makeStudent($emptyGraduationClass, 'NG1');
        $notGraduated->update(['status' => Student::STATUS_STUDYING]);
        $graduatedCountBefore = Student::where('status', Student::STATUS_GRADUATED)->count();

        $this->actingAs($admin);
        $request = Request::create('/reports', 'GET', [
            'report_type' => 'school_year',
            'school_year_id' => $secondYear->id,
            'table_tab' => 'graduation',
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $html = app(ReportController::class)->classSummary($request)->render();

        $this->assertStringContainsString('data-report-table-tab="graduation"', $html);
        $this->assertStringContainsString('data-graduation-year="' . $firstYear->id . '"', $html);
        $this->assertStringContainsString('Chưa có học sinh tốt nghiệp trong năm học này.', $html);
        $this->assertStringContainsString($graduated->student_code, $html);
        $this->assertDoesNotMatchRegularExpression('/data-graduation-year="' . preg_quote((string) $secondYear->id, '/') . '"[^>]*>[\s\S]*?' . preg_quote($notGraduated->student_code, '/') . '/', $html);
        $this->assertSame($graduatedCountBefore, Student::where('status', Student::STATUS_GRADUATED)->count());
    }

    private function makeUser(string $role): User
    {
        $token = Str::lower(Str::random(10));

        return User::create([
            'username' => $role . '_' . $token,
            'full_name' => ucfirst($role) . ' kiểm thử',
            'email' => $role . '_' . $token . '@example.test',
            'role' => $role,
            'role_type' => $role,
            'password_hash' => Hash::make('12345678'),
            'is_active' => true,
            'login_status' => true,
        ]);
    }

    private function makeStudent(SchoolClass $class, string $suffix): Student
    {
        return Student::create([
            'student_code' => 'ST' . $suffix . Str::upper(Str::random(8)),
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

    private function studentUpdateRequest(User $admin, Student $student, array $overrides = []): Request
    {
        $payload = array_merge([
            'name' => $student->name,
            'gender' => $student->gender,
            'dob' => optional($student->dob)->format('Y-m-d'),
            'address' => $student->address,
            'place_of_birth' => $student->place_of_birth,
            'enrollment_date' => optional($student->enrollment_date)->format('Y-m-d') ?: now()->toDateString(),
            'admission_type' => $student->admission_type ?: Student::ADMISSION_NEW,
            'previous_school' => null,
            'transfer_grade_level' => null,
            'previous_class' => null,
            'note' => $student->note,
            'class_id' => $student->class_id,
            'school_year_id' => $student->school_year_id,
            'status' => $student->status,
            'parent_id' => $student->parents()->first()?->id,
            'parent_name' => '',
            'parent_relation' => ParentProfile::RELATION_GUARDIAN,
            'parent_phone' => '',
            'parent_address' => '',
        ], $overrides);

        $request = Request::create('/students/' . $student->id, 'PUT', $payload);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        return $request;
    }
}
