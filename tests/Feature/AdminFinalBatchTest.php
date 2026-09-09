<?php

namespace Tests\Feature;

use App\Http\Controllers\MessageController;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
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
}
