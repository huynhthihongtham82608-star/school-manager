<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    private const TARGET_INDIVIDUAL = 'individual';
    private const TARGET_TEACHERS = 'all_teachers';
    private const TARGET_HOMEROOMS = 'all_homerooms';
    private const TARGET_STUDENTS = 'all_students';
    private const TARGET_PARENTS = 'all_parents';
    private const TARGET_CLASS = 'class';
    private const TARGET_GRADE = 'grade';
    private const TARGET_SCHOOL = 'school';

    public function inbox(Request $request)
    {
        $filters = $this->filters($request);

        $messages = MessageRecipient::query()
            ->with(['message.sender.teacher', 'message.sender.student', 'message.sender.parentProfile', 'message.attachments', 'message.recipients.receiver'])
            ->where('receiver_user_id', Auth::id())
            ->whereNull('deleted_at')
            ->whereNull('permanently_deleted_at')
            ->whereHas('message', fn (Builder $query) => $this->applyMessageSearch($query, $filters))
            ->when($filters['status'] === 'read', fn (Builder $query) => $query->where('is_read', true))
            ->when($filters['status'] === 'unread', fn (Builder $query) => $query->where('is_read', false))
            ->when($filters['attachment'] === '1', fn (Builder $query) => $query->whereHas('message.attachments'))
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('messages.id', 'message_recipients.message_id')
                    ->limit(1)
            )
            ->paginate(15)
            ->withQueryString();

        return view('messages.inbox', compact('messages', 'filters'));
    }

    public function sent(Request $request)
    {
        $filters = $this->filters($request);

        $messages = Message::query()
            ->with(['recipients.receiver.teacher', 'recipients.receiver.student', 'recipients.receiver.parentProfile', 'attachments'])
            ->where('sender_user_id', Auth::id())
            ->whereNull('sender_deleted_at')
            ->whereNull('sender_permanently_deleted_at')
            ->when($filters['attachment'] === '1', fn (Builder $query) => $query->whereHas('attachments'))
            ->tap(fn (Builder $query) => $this->applyMessageSearch($query, $filters))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('messages.sent', compact('messages', 'filters'));
    }

    public function trash(Request $request)
    {
        $filters = $this->filters($request);
        $userId = Auth::id();

        $received = MessageRecipient::query()
            ->with(['message.sender.teacher', 'message.sender.student', 'message.sender.parentProfile', 'message.attachments'])
            ->where('receiver_user_id', $userId)
            ->whereNotNull('deleted_at')
            ->whereNull('permanently_deleted_at')
            ->whereHas('message', fn (Builder $query) => $this->applyMessageSearch($query, $filters))
            ->when($filters['attachment'] === '1', fn (Builder $query) => $query->whereHas('message.attachments'))
            ->get()
            ->map(fn (MessageRecipient $recipient) => [
                'type' => 'received',
                'message' => $recipient->message,
                'recipient' => $recipient,
                'deleted_at' => $recipient->deleted_at,
            ]);

        $sent = Message::query()
            ->with(['recipients.receiver.teacher', 'recipients.receiver.student', 'recipients.receiver.parentProfile', 'attachments'])
            ->where('sender_user_id', $userId)
            ->whereNotNull('sender_deleted_at')
            ->whereNull('sender_permanently_deleted_at')
            ->when($filters['attachment'] === '1', fn (Builder $query) => $query->whereHas('attachments'))
            ->tap(fn (Builder $query) => $this->applyMessageSearch($query, $filters))
            ->get()
            ->map(fn (Message $message) => [
                'type' => 'sent',
                'message' => $message,
                'recipient' => null,
                'deleted_at' => $message->sender_deleted_at,
            ]);

        $messages = $received
            ->merge($sent)
            ->sortByDesc(fn (array $item) => $item['deleted_at']?->timestamp ?? 0)
            ->values();

        return view('messages.trash', compact('messages', 'filters'));
    }

    public function create()
    {
        $user = Auth::user();
        $users = $this->availableRecipients($user)
            ->map(fn (User $recipient) => [
                'id' => $recipient->id,
                'label' => $this->recipientLabel($recipient),
                'role' => $recipient->isHomeroom() ? 'GVCN' : $this->roleLabel($recipient),
            ])
            ->values();

        $classes = SchoolClass::query()
            ->with('schoolYear')
            ->where('status', SchoolClass::STATUS_ACTIVE)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $targetTypes = $this->targetTypes($user);

        return view('messages.create', compact('users', 'classes', 'targetTypes'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'target_type' => ['required', Rule::in(array_keys($this->targetTypes($user)))],
            'recipient_user_ids' => ['array'],
            'recipient_user_ids.*' => ['exists:users,id'],
            'class_ids' => ['array'],
            'class_ids.*' => ['exists:classes,id'],
            'grade_levels' => ['array'],
            'grade_levels.*' => ['integer', Rule::in([10, 11, 12])],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ], [], [
            'target_type' => 'kiểu người nhận',
            'recipient_user_ids' => 'người nhận',
            'class_ids' => 'lớp',
            'grade_levels' => 'khối',
            'title' => 'tiêu đề',
            'content' => 'nội dung',
            'attachments.*' => 'tệp đính kèm',
        ]);

        $recipients = $this->resolveRecipients($user, $data);

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'Không tìm thấy người nhận phù hợp.');
        }

        DB::transaction(function () use ($request, $user, $data, $recipients) {
            $message = Message::create([
                'sender_user_id' => $user->id,
                'receiver_user_id' => $recipients->first()->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'target_type' => $data['target_type'],
                'recipient_summary' => $this->recipientSummary($data['target_type'], $recipients),
                'is_read' => false,
                'created_at' => Carbon::now(),
            ]);

            $message->update(['conversation_id' => $message->id]);

            $message->recipients()->createMany(
                $recipients->map(fn (User $recipient) => [
                    'receiver_user_id' => $recipient->id,
                    'is_read' => false,
                ])->all()
            );

            $this->storeAttachments($request, $message);
        });

        return redirect()->route('messages.sent')->with('success', 'Đã gửi tin nhắn.');
    }

    public function show(Message $message)
    {
        $userId = Auth::id();
        $message->load(['sender.teacher', 'sender.student', 'sender.parentProfile', 'recipients.receiver.teacher', 'recipients.receiver.student', 'recipients.receiver.parentProfile', 'attachments']);
        $recipient = $message->recipients->firstWhere('receiver_user_id', $userId);

        if ($message->sender_user_id !== $userId && ! $recipient) {
            abort(403);
        }

        $conversationId = $message->conversationKey();
        $threadMessages = Message::query()
            ->with(['sender.teacher', 'sender.student', 'sender.parentProfile', 'recipients.receiver.teacher', 'recipients.receiver.student', 'recipients.receiver.parentProfile', 'attachments'])
            ->where(fn (Builder $query) => $query
                ->where('conversation_id', $conversationId)
                ->orWhere('id', $conversationId))
            ->where(fn (Builder $query) => $query
                ->where('sender_user_id', $userId)
                ->orWhereHas('recipients', fn (Builder $recipientQuery) => $recipientQuery
                    ->where('receiver_user_id', $userId)
                    ->whereNull('permanently_deleted_at')))
            ->orderBy('created_at')
            ->get();

        MessageRecipient::query()
            ->where('receiver_user_id', $userId)
            ->where('is_read', false)
            ->whereHas('message', fn (Builder $query) => $query
                ->where(fn (Builder $conversationQuery) => $conversationQuery
                    ->where('conversation_id', $conversationId)
                    ->orWhere('id', $conversationId)))
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $canReply = $this->canReplyToMessage(Auth::user(), $message);

        return view('messages.show', compact('message', 'recipient', 'threadMessages', 'canReply'));
    }

    public function reply(Request $request, Message $message)
    {
        $this->assertMessageAccess($message);
        $sender = $request->user();
        abort_unless($this->canReplyToMessage($sender, $message), 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ], [], [
            'content' => 'nội dung phản hồi',
            'attachments.*' => 'tệp đính kèm',
        ]);

        $recipient = $this->replyRecipient($sender, $message);

        if (! $recipient) {
            return back()->with('error', 'Không xác định được người nhận phản hồi.');
        }

        $reply = DB::transaction(function () use ($request, $message, $sender, $recipient, $data) {
            $reply = Message::create([
                'sender_user_id' => $sender->id,
                'receiver_user_id' => $recipient->id,
                'conversation_id' => $message->conversationKey(),
                'parent_message_id' => $message->id,
                'title' => str_starts_with($message->title ?? '', 'Re:') ? $message->title : 'Re: ' . ($message->title ?: 'Tin nhắn'),
                'content' => $data['content'],
                'target_type' => self::TARGET_INDIVIDUAL,
                'recipient_summary' => $this->recipientLabel($recipient),
                'is_read' => false,
                'created_at' => Carbon::now(),
            ]);

            $reply->recipients()->create([
                'receiver_user_id' => $recipient->id,
                'is_read' => false,
            ]);

            $this->storeAttachments($request, $reply);

            return $reply;
        });

        return redirect()->route('messages.show', $reply)->with('success', 'Đã gửi phản hồi.');
    }

    public function destroy(Request $request, Message $message)
    {
        $this->assertMessageAccess($message);
        $context = $this->messageContext($request, $message);

        if ($context === 'sent') {
            $message->update(['sender_deleted_at' => now()]);
        } else {
            $message->recipients()
                ->where('receiver_user_id', Auth::id())
                ->update(['deleted_at' => now()]);
        }

        return back()->with('success', 'Đã chuyển tin nhắn vào thùng rác.');
    }

    public function restore(Request $request, Message $message)
    {
        $this->assertMessageAccess($message, true);
        $context = $this->messageContext($request, $message);

        if ($context === 'sent') {
            $message->update(['sender_deleted_at' => null]);
        } else {
            $message->recipients()
                ->where('receiver_user_id', Auth::id())
                ->update(['deleted_at' => null]);
        }

        return back()->with('success', 'Đã khôi phục tin nhắn.');
    }

    public function forceDestroy(Request $request, Message $message)
    {
        $this->assertMessageAccess($message, true);
        $context = $this->messageContext($request, $message);

        if ($context === 'sent') {
            $message->update(['sender_permanently_deleted_at' => now()]);
        } else {
            $message->recipients()
                ->where('receiver_user_id', Auth::id())
                ->update(['permanently_deleted_at' => now()]);
        }

        return back()->with('success', 'Đã xóa vĩnh viễn tin nhắn.');
    }

    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status'),
            'attachment' => $request->query('attachment'),
        ];
    }

    private function applyMessageSearch(Builder $query, array $filters): Builder
    {
        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function (Builder $subQuery) use ($keyword) {
                $subQuery->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%")
                    ->orWhereHas('sender', fn (Builder $userQuery) => $this->applyUserSearch($userQuery, $keyword))
                    ->orWhereHas('recipients.receiver', fn (Builder $userQuery) => $this->applyUserSearch($userQuery, $keyword));
            });
        }

        return $query;
    }

    private function applyUserSearch(Builder $query, string $keyword): Builder
    {
        return $query->where('username', 'like', "%{$keyword}%")
            ->orWhereHas('teacher', fn (Builder $teacherQuery) => $teacherQuery
                ->where('teacher_code', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%"))
            ->orWhereHas('student', fn (Builder $studentQuery) => $studentQuery
                ->where('student_code', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%"))
            ->orWhereHas('parentProfile', fn (Builder $parentQuery) => $parentQuery
                ->where('parent_code', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%"));
    }

    private function targetTypes(User $user): array
    {
        $targets = [
            self::TARGET_INDIVIDUAL => 'Cá nhân',
        ];

        if ($user->isAdmin() || $user->isStaff()) {
            $targets += [
                self::TARGET_TEACHERS => 'Toàn bộ Giáo viên',
                self::TARGET_HOMEROOMS => 'Toàn bộ Giáo viên chủ nhiệm',
                self::TARGET_STUDENTS => 'Toàn bộ Học sinh',
                self::TARGET_PARENTS => 'Toàn bộ Phụ huynh',
                self::TARGET_CLASS => 'Theo lớp',
                self::TARGET_GRADE => 'Theo khối',
                self::TARGET_SCHOOL => 'Toàn trường',
            ];
        }

        return $targets;
    }

    private function resolveRecipients(User $sender, array $data): Collection
    {
        $targetType = $data['target_type'];
        $baseQuery = User::query()
            ->with(['teacher', 'student.classRoom', 'parentProfile'])
            ->where('is_active', true)
            ->whereKeyNot($sender->id);

        $recipients = match ($targetType) {
            self::TARGET_INDIVIDUAL => $this->resolveIndividualRecipients($sender, $data['recipient_user_ids'] ?? []),
            self::TARGET_TEACHERS => $baseQuery->where('role', 'teacher')->get(),
            self::TARGET_HOMEROOMS => $baseQuery->where('role', 'teacher')->whereHas('teacher', fn (Builder $query) => $query->where('is_homeroom', true))->get(),
            self::TARGET_STUDENTS => $baseQuery->where('role', 'student')->get(),
            self::TARGET_PARENTS => $baseQuery->where('role', 'parent')->get(),
            self::TARGET_CLASS => $baseQuery->where('role', 'student')->whereHas('student', fn (Builder $query) => $query->whereIn('class_id', $data['class_ids'] ?? []))->get(),
            self::TARGET_GRADE => $baseQuery->where('role', 'student')->whereHas('student.classRoom', fn (Builder $query) => $query->whereIn('grade_level', $data['grade_levels'] ?? []))->get(),
            self::TARGET_SCHOOL => $baseQuery->get(),
            default => collect(),
        };

        return $recipients->unique('id')->values();
    }

    private function resolveIndividualRecipients(User $sender, array $ids): Collection
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $available = $this->availableRecipients($sender);

        return $available
            ->whereIn('id', $ids)
            ->values();
    }

    private function recipientSummary(string $targetType, Collection $recipients): string
    {
        if ($targetType === self::TARGET_INDIVIDUAL) {
            return $recipients->count() === 1
                ? $this->recipientLabel($recipients->first())
                : $recipients->count() . ' người nhận';
        }

        return ($this->targetTypes(Auth::user())[$targetType] ?? 'Nhóm người nhận') . ' - ' . $recipients->count() . ' người nhận';
    }

    private function storeAttachments(Request $request, Message $message): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('message-attachments', 'public');
            $message->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }

    private function canReplyToMessage(User $currentUser, Message $message): bool
    {
        $message->loadMissing(['sender.teacher', 'recipients']);

        if ($message->sender_user_id === $currentUser->id) {
            return false;
        }

        $isReceiver = $message->recipients->contains('receiver_user_id', $currentUser->id);

        if (! $isReceiver) {
            return false;
        }

        $sender = $message->sender;

        if (! $sender) {
            return false;
        }

        if ($currentUser->isAdmin() || $currentUser->isStaff()) {
            return true;
        }

        if ($currentUser->isTeacher()) {
            return true;
        }

        if ($currentUser->isStudent()) {
            return $sender->isAdmin() || $sender->isStaff() || $sender->isTeacher();
        }

        if ($currentUser->isParent()) {
            return $sender->isAdmin() || $sender->isStaff() || $sender->isHomeroom();
        }

        return false;
    }

    private function replyRecipient(User $currentUser, Message $message): ?User
    {
        $message->loadMissing(['sender.teacher', 'recipients.receiver.teacher']);

        if ($message->sender_user_id !== $currentUser->id) {
            return $message->sender;
        }

        if ($currentUser->isAdmin() || $currentUser->isStaff()) {
            return $message->recipients
                ->pluck('receiver')
                ->filter()
                ->firstWhere('id', '!=', $currentUser->id);
        }

        return null;
    }

    private function availableRecipients(User $user): Collection
    {
        $query = User::with(['teacher', 'student.classRoom', 'parentProfile'])
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->orderBy('username');

        if ($user->isAdmin() || $user->isStaff()) {
            return $query->get();
        }

        if ($user->isTeacher() && $user->teacher) {
            $classIds = $user->teacher->assignments()
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('class_id')
                ->merge(SchoolClass::where('homeroom_teacher_id', $user->teacher->id)->pluck('id'))
                ->filter()
                ->unique()
                ->values();

            return $query
                ->where(function (Builder $subQuery) use ($classIds) {
                    $subQuery->whereIn('role', ['admin', 'staff', 'teacher'])
                        ->orWhere(function (Builder $studentQuery) use ($classIds) {
                            $studentQuery->where('role', 'student')
                                ->whereHas('student', fn (Builder $query) => $query->whereIn('class_id', $classIds));
                        });
                })
                ->get();
        }

        return $query->whereIn('role', ['admin', 'staff', 'teacher'])->get();
    }

    private function recipientLabel(User $user): string
    {
        if ($user->teacher) {
            return trim($user->teacher->teacher_code . ' - ' . $user->teacher->name);
        }

        if ($user->student) {
            $class = $user->student->classRoom?->name;
            return trim($user->student->student_code . ' - ' . $user->student->name . ($class ? ' - ' . $class : ''));
        }

        if ($user->parentProfile) {
            return trim(($user->parentProfile->parent_code ?: $user->username) . ' - ' . $user->parentProfile->name);
        }

        return trim($user->username . ' - ' . $user->display_name);
    }

    private function roleLabel(User $user): string
    {
        return [
            'admin' => 'Admin',
            'staff' => 'Nhân viên',
            'teacher' => 'Giáo viên',
            'student' => 'Học sinh',
            'parent' => 'Phụ huynh',
        ][$user->role] ?? $user->role;
    }

    private function assertMessageAccess(Message $message, bool $allowDeleted = false): void
    {
        $userId = Auth::id();

        if ($message->sender_user_id === $userId) {
            abort_if($message->sender_permanently_deleted_at, 404);
            return;
        }

        $recipient = $message->recipients()
            ->where('receiver_user_id', $userId)
            ->when(! $allowDeleted, fn (Builder $query) => $query->whereNull('deleted_at'))
            ->whereNull('permanently_deleted_at')
            ->first();

        abort_unless($recipient, 403);
    }

    private function messageContext(Request $request, Message $message): string
    {
        $box = $request->input('box', $request->query('box'));

        if ($box === 'sent' && $message->sender_user_id === Auth::id()) {
            return 'sent';
        }

        return 'received';
    }
}
