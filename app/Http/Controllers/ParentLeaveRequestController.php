<?php

namespace App\Http\Controllers;

use App\Models\ParentLeaveRequest;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParentLeaveRequestController extends Controller
{
    public function manage(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->isHomeroom() && $user->teacher, 403);

        $classIds = $user->teacher->homeroomClasses()->pluck('id');
        $status = $request->query('status', 'pending');

        $leaveRequests = ParentLeaveRequest::with(['parent', 'student', 'classRoom', 'reviewer'])
            ->whereIn('class_id', $classIds)
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->latest('leave_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('parent_leave_requests.manage', compact('leaveRequests', 'status'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->isParent() && $user->parentProfile, 403);

        $children = $user->parentProfile->students()
            ->with('classRoom.homeroomTeacher')
            ->orderBy('student_code')
            ->get();
        $selectedStudent = $this->selectedStudent($children);

        $leaveRequests = Schema::hasTable('parent_leave_requests') && $selectedStudent
            ? ParentLeaveRequest::with(['student.classRoom', 'classRoom', 'reviewer'])
                ->where('parent_id', $user->parentProfile->id)
                ->where('student_id', $selectedStudent->id)
                ->latest('leave_date')
                ->latest()
                ->paginate(10)
                ->withQueryString()
            : collect();

        return view('parent_leave_requests.index', compact('children', 'selectedStudent', 'leaveRequests'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->isParent() && $user->parentProfile, 403);

        if (! Schema::hasTable('parent_leave_requests')) {
            return back()->with('error', 'Chưa có bảng lưu đơn xin nghỉ học. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'leave_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ], [], [
            'student_id' => 'học sinh',
            'leave_date' => 'ngày nghỉ',
            'reason' => 'lý do nghỉ học',
        ]);

        $student = $user->parentProfile->students()
            ->with('classRoom')
            ->where('students.id', $data['student_id'])
            ->firstOrFail();

        $existingRequest = ParentLeaveRequest::where('student_id', $student->id)
            ->whereDate('leave_date', $data['leave_date'])
            ->whereIn('status', [ParentLeaveRequest::STATUS_PENDING, ParentLeaveRequest::STATUS_APPROVED])
            ->first();

        if ($existingRequest) {
            return back()
                ->withInput()
                ->with('error', 'Học sinh đã có đơn xin nghỉ trong ngày này. Vui lòng theo dõi trạng thái đơn hiện có.');
        }

        DB::transaction(function () use ($user, $student, $data) {
            $leaveRequest = ParentLeaveRequest::create([
                'parent_id' => $user->parentProfile->id,
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'leave_date' => $data['leave_date'],
                'reason' => $data['reason'],
                'status' => ParentLeaveRequest::STATUS_PENDING,
            ]);

            AuditLogger::log(
                'parent_leave_request_created',
                ParentLeaveRequest::class,
                (string) $leaveRequest->getKey(),
                'Phụ huynh gửi đơn xin nghỉ học cho ' . $student->name
            );
        });

        session(['selected_parent_student_id' => $student->id]);

        $redirectRoute = $request->input('return_to') === 'attendance'
            ? 'attendance.index'
            : 'parent.leave-requests.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Đã gửi đơn xin nghỉ học. Đơn đang chờ giáo viên chủ nhiệm phê duyệt.');
    }

    public function approve(Request $request, ParentLeaveRequest $leaveRequest)
    {
        $this->authorizeHomeroomReview($request, $leaveRequest);

        $data = $request->validate([
            'homeroom_note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'homeroom_note' => 'ghi chú của giáo viên chủ nhiệm',
        ]);

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $leaveRequest->update([
                'status' => ParentLeaveRequest::STATUS_APPROVED,
                'homeroom_note' => $data['homeroom_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->syncApprovedLeaveToAttendance($leaveRequest, $request->user()->id);

            AuditLogger::log(
                'parent_leave_request_approved',
                ParentLeaveRequest::class,
                (string) $leaveRequest->getKey(),
                'GVCN duyệt đơn xin nghỉ học của ' . ($leaveRequest->student?->name ?? 'học sinh')
            );
        });

        return back()->with('success', 'Đã phê duyệt đơn và cập nhật điểm danh vắng có phép.');
    }

    public function reject(Request $request, ParentLeaveRequest $leaveRequest)
    {
        $this->authorizeHomeroomReview($request, $leaveRequest);

        $data = $request->validate([
            'homeroom_note' => ['required', 'string', 'max:1000'],
        ], [], [
            'homeroom_note' => 'lý do không duyệt',
        ]);

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $leaveRequest->update([
                'status' => ParentLeaveRequest::STATUS_REJECTED,
                'homeroom_note' => $data['homeroom_note'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            AuditLogger::log(
                'parent_leave_request_rejected',
                ParentLeaveRequest::class,
                (string) $leaveRequest->getKey(),
                'GVCN không duyệt đơn xin nghỉ học của ' . ($leaveRequest->student?->name ?? 'học sinh')
            );
        });

        return back()->with('success', 'Đã cập nhật trạng thái đơn xin nghỉ học.');
    }

    private function selectedStudent($children): ?Student
    {
        if ($children->isEmpty()) {
            return null;
        }

        $selectedId = session('selected_parent_student_id');

        return $children->firstWhere('id', $selectedId) ?: $children->first();
    }

    private function authorizeHomeroomReview(Request $request, ParentLeaveRequest $leaveRequest): void
    {
        $user = $request->user();
        $class = $leaveRequest->classRoom;

        abort_unless($user?->isHomeroom() && $user->teacher && $class, 403);
        abort_unless((string) $class->homeroom_teacher_id === (string) $user->teacher->id, 403);
    }

    private function syncApprovedLeaveToAttendance(ParentLeaveRequest $leaveRequest, string $recordedBy): void
    {
        $leaveRequest->loadMissing(['student.classRoom', 'classRoom']);
        $student = $leaveRequest->student;
        $class = $leaveRequest->classRoom ?: $student?->classRoom;

        if (! $student || ! $class || ! Schema::hasTable('attendance_records')) {
            return;
        }

        $semesterId = $class->semester_id ?: $this->selectedSemesterId(request());
        $semester = $semesterId ? Semester::find($semesterId) : null;
        $dayOfWeek = $leaveRequest->leave_date->isoWeekday();

        $timetableIds = $semester
            ? Timetable::where('class_id', $class->id)->where('semester_id', $semester->id)->pluck('id')
            : collect();

        $entries = $timetableIds->isNotEmpty()
            ? TimetableEntry::with(['subject', 'teacher'])
                ->whereIn('timetable_id', $timetableIds)
                ->where('day_of_week', $dayOfWeek)
                ->where('status', TimetableEntry::STATUS_ACTIVE)
                ->get()
            : collect();

        foreach ([
            \App\Models\AttendanceRecord::SESSION_MORNING => ['label' => 'Điểm danh Buổi Sáng', 'order' => 1],
            \App\Models\AttendanceRecord::SESSION_AFTERNOON => ['label' => 'Điểm danh Buổi Chiều', 'order' => 2],
        ] as $sessionType => $sessionMeta) {
            \App\Models\AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_date' => $leaveRequest->leave_date->toDateString(),
                    'session_key' => $sessionType,
                ],
                [
                    'class_id' => $class->id,
                    'semester_id' => $semester?->id,
                    'session_type' => $sessionType,
                    'timetable_entry_id' => null,
                    'session_label' => $sessionMeta['label'],
                    'session_order' => $sessionMeta['order'],
                    'status' => 'excused',
                    'note' => 'Đã duyệt đơn xin nghỉ học của phụ huynh. Lý do: ' . $leaveRequest->reason,
                    'recorded_by' => $recordedBy,
                ]
            );
        }

        if ($entries->isEmpty()) {
            return;
        }

        foreach ($entries as $entry) {
            \App\Models\AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_date' => $leaveRequest->leave_date->toDateString(),
                    'session_key' => 'period:' . $entry->id,
                ],
                [
                    'class_id' => $class->id,
                    'semester_id' => $semester?->id,
                    'session_type' => \App\Models\AttendanceRecord::SESSION_PERIOD,
                    'timetable_entry_id' => $entry->id,
                    'session_label' => implode(' - ', array_filter([
                        $entry->displayPeriod(),
                        $entry->subject?->name ?: 'Môn học',
                        $entry->teacher?->name,
                    ])),
                    'session_order' => (int) $entry->period,
                    'status' => 'excused',
                    'note' => 'Đã duyệt đơn xin nghỉ học của phụ huynh. Lý do: ' . $leaveRequest->reason,
                    'recorded_by' => $recordedBy,
                ]
            );
        }
    }
}
