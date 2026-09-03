<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatbotDataService
{
    public function __construct(private readonly UserScopeService $scope)
    {
    }

    public function schoolStatistics(User $user, array $arguments = []): array
    {
        return [
            'scope' => 'school',
            'statistics' => [
                'students' => $this->countUsersByRole('student'),
                'teachers' => $this->teacherQuery()->count(),
                'parents' => $this->countUsersByRole('parent'),
                'classes' => $this->tableCount('classes'),
                'subjects' => $this->subjectQuery()->count(),
                'rooms' => $this->tableCount('rooms'),
                'locked_accounts' => $this->lockedUserCount(),
                'unpaid_tuition_students' => Schema::hasTable('tuition_fees')
                    ? DB::table('tuition_fees')->where('status', 'unpaid')->distinct('student_id')->count('student_id')
                    : 0,
            ],
        ];
    }

    public function classStudentCount(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('classes') || ! Schema::hasTable('users')) {
            return $this->unsupported('Thiếu bảng lớp học hoặc người dùng.');
        }

        $mode = (string) ($arguments['mode'] ?? 'all');
        $class = $this->resolveClassForUser($user, $arguments);

        if ($class) {
            if (! $this->scope->canAccessClass($user, $class->id)) {
                return $this->forbidden('Bạn không có quyền xem sĩ số lớp này.');
            }

            return [
                'mode' => 'single',
                'class' => $this->classRow($class),
                'student_count' => $this->studentQuery()->where('class_id', $class->id)->count(),
            ];
        }

        $classIds = $this->scope->classIdsInScope($user);

        if ($classIds->isEmpty()) {
            return $this->forbidden('Không tìm thấy lớp trong phạm vi được phép.');
        }

        $rows = DB::table('classes')
            ->whereIn('classes.id', $classIds)
            ->leftJoin('users', function ($join) {
                $join->on('users.class_id', '=', 'classes.id');
                if (Schema::hasColumn('users', 'role_type')) {
                    $join->where('users.role_type', '=', 'student');
                } else {
                    $join->where('users.role', '=', 'student');
                }
            })
            ->select('classes.id', 'classes.name', DB::raw('COUNT(users.id) as student_count'))
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        if ($mode === 'largest') {
            return ['mode' => 'largest', 'classes' => $rows->sortByDesc('student_count')->take(3)->values()->all()];
        }

        if ($mode === 'smallest') {
            return ['mode' => 'smallest', 'classes' => $rows->sortBy('student_count')->take(3)->values()->all()];
        }

        return ['mode' => 'all', 'classes' => $rows->values()->all()];
    }

    public function teacherClasses(User $user, array $arguments = []): array
    {
        $teacher = $this->resolveTeacher($user, $arguments);

        if (! $teacher) {
            return $this->notFound('Không tìm thấy giáo viên cần tra cứu.');
        }

        if (! $this->canInspectTeacher($user, $teacher)) {
            return $this->forbidden('Bạn không có quyền xem phân công của giáo viên này.');
        }

        $teacherIds = $this->teacherIdCandidatesFromRow($teacher);
        $assignments = $this->assignmentQuery()
            ->whereIn('teaching_assignments.teacher_id', $teacherIds)
            ->leftJoin('classes', 'classes.id', '=', 'teaching_assignments.class_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'teaching_assignments.subject_id')
            ->select(
                'classes.name as class_name',
                'subjects.name as subject_name',
                'teaching_assignments.role',
                'teaching_assignments.weekly_periods',
                'teaching_assignments.status'
            )
            ->orderBy('classes.name')
            ->get();

        $homerooms = Schema::hasTable('classes')
            ? DB::table('classes')
                ->whereIn('homeroom_teacher_id', $teacherIds)
                ->when(Schema::hasColumn('classes', 'status'), function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNull('status')->orWhereIn('status', ['active', 'draft']);
                    });
                })
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'updated_at'])
            : collect();

        return [
            'teacher' => $this->teacherRow($teacher),
            'teaching_assignments' => $assignments,
            'homeroom_class' => $homerooms->first() ? $this->classRow($homerooms->first()) : null,
            'homeroom_data_warning' => $homerooms->count() > 1
                ? 'Dữ liệu lớp chủ nhiệm đang có nhiều hơn một lớp, hệ thống chọn bản ghi cập nhật mới nhất để trả lời.'
                : null,
        ];
    }

    public function teacherHomeroomClass(User $user, array $arguments = []): array
    {
        $teacher = $this->resolveTeacher($user, $arguments);

        if (! $teacher) {
            return $this->notFound('Không tìm thấy giáo viên cần tra cứu.');
        }

        if (! $this->canInspectTeacher($user, $teacher)) {
            return $this->forbidden('Bạn không có quyền xem phân công chủ nhiệm của giáo viên này.');
        }

        $homerooms = Schema::hasTable('classes')
            ? DB::table('classes')
                ->whereIn('homeroom_teacher_id', $this->teacherIdCandidatesFromRow($teacher))
                ->when(Schema::hasColumn('classes', 'status'), function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNull('status')->orWhereIn('status', ['active', 'draft']);
                    });
                })
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'grade_level', 'homeroom_teacher_id'])
            : collect();

        return [
            'teacher' => $this->teacherRow($teacher),
            'homeroom_class' => $homerooms->first() ? $this->classRow($homerooms->first()) : null,
            'data_warning' => $homerooms->count() > 1
                ? 'Dữ liệu có nhiều lớp gắn cùng giáo viên chủ nhiệm, cần kiểm tra lại phân công lớp.'
                : null,
        ];
    }

    public function teacherSchedule(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('timetable_entries')) {
            return $this->unsupported('Thiếu bảng thời khóa biểu.');
        }

        $teacher = $this->resolveTeacher($user, $arguments);

        if (! $teacher) {
            return $this->notFound('Không tìm thấy giáo viên cần tra cứu.');
        }

        if (! $this->canInspectTeacher($user, $teacher)) {
            return $this->forbidden('Bạn không có quyền xem lịch dạy của giáo viên này.');
        }

        $dayOfWeek = $this->resolveDayOfWeek($arguments);
        $entries = $this->timetableEntryBaseQuery()
            ->where(function ($query) use ($teacher) {
                $ids = $this->teacherIdCandidatesFromRow($teacher);
                $query->whereIn('te.teacher_id', $ids)
                    ->orWhereIn('ta.teacher_id', $ids);
            })
            ->when($dayOfWeek, fn ($query) => $query->where('te.day_of_week', $dayOfWeek))
            ->orderBy('te.day_of_week')
            ->orderBy('te.period')
            ->limit(40)
            ->get();

        return [
            'teacher' => $this->teacherRow($teacher),
            'date' => $arguments['date'] ?? null,
            'day_of_week' => $dayOfWeek,
            'schedule' => $entries->map(fn ($entry) => $this->timetableEntryRow($entry))->values()->all(),
        ];
    }

    public function classSubjectTeachers(User $user, array $arguments = []): array
    {
        $class = $this->resolveClassForUser($user, $arguments);

        if (! $class) {
            return $this->notFound('Không tìm thấy lớp cần tra cứu.');
        }

        if (! $this->scope->canAccessClass($user, $class->id)) {
            return $this->forbidden('Bạn không có quyền xem giáo viên của lớp này.');
        }

        $subject = $this->resolveSubject($arguments);
        $rows = $this->assignmentQuery()
            ->where('teaching_assignments.class_id', $class->id)
            ->when($subject, fn ($query) => $query->where('teaching_assignments.subject_id', $subject->id))
            ->leftJoin('users as teachers', function ($join) {
                $join->on('teachers.id', '=', 'teaching_assignments.teacher_id');
                $join->orOn('teachers.teacher_id', '=', 'teaching_assignments.teacher_id');
                $join->orOn('teachers.source_profile_id', '=', 'teaching_assignments.teacher_id');
            })
            ->leftJoin('subjects', 'subjects.id', '=', 'teaching_assignments.subject_id')
            ->select('teachers.id as teacher_id', 'teachers.name as teacher_name', 'teachers.full_name as teacher_full_name', 'subjects.name as subject_name', 'teaching_assignments.role')
            ->orderBy('subjects.name')
            ->get();

        $assignments = $rows
            ->unique(fn ($row) => (string) ($row->teacher_id ?: ($row->teacher_name ?: $row->teacher_full_name)) . '|' . (string) $row->subject_name)
            ->map(fn ($row) => [
                'teacher' => $row->teacher_name ?: $row->teacher_full_name,
                'subject' => $row->subject_name,
                'role' => $row->role,
            ])
            ->values();

        return [
            'class' => $this->classRow($class),
            'subject_filter' => $subject?->name,
            'distinct_teacher_count' => $rows->pluck('teacher_id')->filter()->unique()->count()
                ?: $assignments->pluck('teacher')->filter()->unique()->count(),
            'teachers' => $assignments,
        ];
    }

    public function classHomeroomTeacher(User $user, array $arguments = []): array
    {
        $class = $this->resolveClassForUser($user, $arguments);

        if (! $class) {
            return $this->notFound('Không tìm thấy lớp cần tra cứu giáo viên chủ nhiệm.');
        }

        if (! $this->scope->canAccessClass($user, $class->id)) {
            return $this->forbidden('Bạn không có quyền xem giáo viên chủ nhiệm của lớp này.');
        }

        $teacher = $class->homeroom_teacher_id
            ? $this->teacherQuery()
                ->where(function ($query) use ($class) {
                    $query->where('id', $class->homeroom_teacher_id)
                        ->orWhere('teacher_id', $class->homeroom_teacher_id)
                        ->orWhere('source_profile_id', $class->homeroom_teacher_id);
                })
                ->first()
            : null;

        return [
            'class' => $this->classRow($class),
            'homeroom_teacher' => $teacher ? $this->teacherRow($teacher) : null,
        ];
    }

    public function classStudents(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('classes') || ! Schema::hasTable('users')) {
            return $this->unsupported('Thiếu bảng lớp học hoặc học sinh.');
        }

        $class = $this->resolveClassForUser($user, $arguments);

        if (! $class) {
            if ($user->isTeacher()) {
                $classes = DB::table('classes')
                    ->whereIn('id', $this->scope->teacherTeachingClassIds($user))
                    ->orderBy('name')
                    ->get(['id', 'name', 'grade_level']);

                if ($classes->count() > 1) {
                    return $this->notFound('Thầy/cô muốn xem danh sách học sinh lớp nào? ' . $classes->pluck('name')->join(', '));
                }

                $class = $classes->first();
            }
        }

        if (! $class) {
            return $this->notFound('Không tìm thấy lớp cần xem danh sách học sinh.');
        }

        if (! $this->scope->canAccessClass($user, $class->id)) {
            return $this->forbidden('Bạn không có quyền xem danh sách học sinh của lớp này.');
        }

        $students = $this->studentQuery()
            ->where('class_id', $class->id)
            ->orderBy('student_code')
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'full_name', 'class_id'])
            ->map(fn ($student) => $this->studentRow($student))
            ->values();

        return [
            'class' => $this->classRow($class),
            'student_count' => $students->count(),
            'students' => $students,
        ];
    }

    public function childSubjectTeachers(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        $student = $resolved['student'];

        return $this->classSubjectTeachers($user, [
            'class_id' => $student->class_id,
            'subject_name' => $arguments['subject_name'] ?? $arguments['subject'] ?? null,
        ]) + [
            'student' => $this->studentRow($student),
        ];
    }

    public function studentScores(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        if (! Schema::hasTable('student_scores')) {
            return $this->unsupported('Thiếu bảng điểm số.');
        }

        $student = $resolved['student'];
        $subject = $this->resolveSubject($arguments);
        $headers = DB::table('student_scores as sh')
            ->leftJoin('subjects', 'subjects.id', '=', 'sh.subject_id')
            ->where('sh.student_id', $student->id)
            ->when(Schema::hasColumn('student_scores', 'score_record_type'), fn ($query) => $query->where('sh.score_record_type', 'header'))
            ->when($subject, fn ($query) => $query->where('sh.subject_id', $subject->id))
            ->select('sh.id', 'sh.subject_id', 'subjects.name as subject_name', 'sh.average', 'sh.semester_id', 'sh.school_year_id')
            ->orderBy('subjects.name')
            ->get();

        $details = collect();
        if ($headers->isNotEmpty()) {
            $details = DB::table('student_scores')
                ->whereIn('score_header_id', $headers->pluck('id'))
                ->when(Schema::hasColumn('student_scores', 'score_record_type'), fn ($query) => $query->where('score_record_type', 'detail'))
                ->select('score_header_id', 'score_name', 'name', 'score_type', 'type', 'score_value', 'value')
                ->get()
                ->groupBy('score_header_id');
        }

        return [
            'student' => $this->studentRow($student),
            'subject_filter' => $subject?->name,
            'scores' => $headers->map(fn ($row) => [
                'subject' => $row->subject_name,
                'average' => is_null($row->average) ? null : round((float) $row->average, 2),
                'details' => ($details->get($row->id) ?: collect())->map(fn ($detail) => [
                    'name' => $detail->score_name ?: $detail->name ?: $detail->score_type ?: $detail->type,
                    'value' => is_null($detail->score_value ?? $detail->value) ? null : round((float) ($detail->score_value ?? $detail->value), 2),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public function studentTimetable(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        $student = $resolved['student'];

        return [
            'student' => $this->studentRow($student),
            'date' => $arguments['date'] ?? null,
            'day_of_week' => $this->resolveDayOfWeek($arguments),
            'schedule' => $this->timetableRowsForClass((string) $student->class_id, $arguments),
        ];
    }

    public function studentAttendance(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        if (! Schema::hasTable('attendance_records')) {
            return $this->unsupported('Thiếu bảng điểm danh.');
        }

        $student = $resolved['student'];
        $date = $this->resolveDate($arguments['date'] ?? null);
        $query = DB::table('attendance_records')->where('student_id', $student->id);
        $dateQuery = $date ? (clone $query)->whereDate('attendance_date', $date) : null;

        return [
            'student' => $this->studentRow($student),
            'date' => $date,
            'summary' => [
                'total_records' => (clone $query)->count(),
                'late' => (clone $query)->where('status', 'late')->count(),
                'permitted_absent' => (clone $query)->whereIn('status', ['excused', 'permitted_absent'])->count(),
                'unexcused_absent' => (clone $query)->whereIn('status', ['absent', 'unexcused_absent'])->count(),
            ],
            'records' => ($dateQuery ?: $query)
                ->orderByDesc('attendance_date')
                ->orderBy('session_order')
                ->limit(30)
                ->get(['attendance_date', 'session_type', 'session_label', 'session_order', 'status', 'note'])
                ->map(fn ($row) => [
                    'date' => (string) $row->attendance_date,
                    'session' => $row->session_label ?: $row->session_type,
                    'slot' => $row->session_order,
                    'status' => $row->status,
                    'note' => $row->note,
                ])
                ->values()
                ->all(),
        ];
    }

    public function studentConduct(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        if (! Schema::hasTable('conducts')) {
            return $this->unsupported('Thiếu bảng hạnh kiểm.');
        }

        $student = $resolved['student'];
        $records = DB::table('conducts')
            ->where('student_id', $student->id)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['conduct_level', 'comment', 'semester_id', 'school_year_id', 'updated_at']);

        return [
            'student' => $this->studentRow($student),
            'conducts' => $records,
        ];
    }

    public function childTuition(User $user, array $arguments = []): array
    {
        $resolved = $this->scope->resolveScopedStudent($user, $arguments);

        if (! $resolved['student']) {
            return $this->forbidden($resolved['error'] ?: 'Không tìm thấy học sinh.');
        }

        if (! Schema::hasTable('tuition_fees')) {
            return $this->unsupported('Thiếu bảng học phí.');
        }

        $student = $resolved['student'];
        $fees = DB::table('tuition_fees')
            ->where('student_id', $student->id)
            ->orderByDesc('updated_at')
            ->get();

        return [
            'student' => $this->studentRow($student),
            'total_amount' => $this->money((float) $fees->sum('amount')),
            'paid_records' => $fees->where('status', 'paid')->count(),
            'unpaid_records' => $fees->where('status', 'unpaid')->count(),
            'qr_image_url' => $this->tuitionQrImageUrl(),
            'fees' => $fees->map(fn ($fee) => [
                'amount' => $this->money((float) $fee->amount),
                'status' => $fee->status,
                'payment_method' => $fee->payment_method ?? null,
                'exemption_type' => $fee->exemption_type ?? null,
                'items' => $this->normalizeFeeItems($fee),
            ])->values()->all(),
        ];
    }

    public function studentExamSchedule(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('exam_schedules')) {
            return $this->unsupported('Thiếu bảng lịch kiểm tra.');
        }

        $class = $this->resolveClassForUser($user, $arguments);

        if (! $class) {
            $resolved = $this->scope->resolveScopedStudent($user, $arguments);
            if ($resolved['student']) {
                $class = $this->resolveClass(['class_id' => $resolved['student']->class_id]);
            }
        }

        if (! $class) {
            return $this->notFound('Không tìm thấy lớp cần xem lịch kiểm tra.');
        }

        if (! $this->scope->canAccessClass($user, $class->id)) {
            return $this->forbidden('Bạn không có quyền xem lịch kiểm tra của lớp này.');
        }

        $subject = $this->resolveSubject($arguments);
        $exams = DB::table('exam_schedules')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_schedules.subject_id')
            ->where('exam_schedules.class_id', $class->id)
            ->when($subject, fn ($query) => $query->where('exam_schedules.subject_id', $subject->id))
            ->select('exam_schedules.title', 'exam_schedules.display_name', 'exam_schedules.type', 'exam_schedules.exam_date', 'exam_schedules.start_time', 'exam_schedules.end_time', 'exam_schedules.room', 'subjects.name as subject_name')
            ->orderBy('exam_schedules.exam_date')
            ->limit(30)
            ->get();

        return [
            'class' => $this->classRow($class),
            'subject_filter' => $subject?->name,
            'exams' => $exams,
        ];
    }

    public function homeroomLeaveRequests(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('parent_leave_requests')) {
            return $this->unsupported('Thiếu bảng đơn xin nghỉ.');
        }

        $class = $this->resolveClassForUser($user, $arguments);
        $status = (string) ($arguments['status'] ?? 'all');

        if ($user->isTeacher() && ! $class) {
            $classIds = $this->scope->teacherClassIds($user);
            $classes = Schema::hasTable('classes') ? DB::table('classes')->whereIn('id', $classIds)->whereIn('homeroom_teacher_id', $this->scope->teacherIdentityCandidates($user))->get() : collect();
            if ($classes->count() === 1) {
                $class = $classes->first();
            }
        }

        if (! $class) {
            return $this->scope->isAdminLike($user)
                ? $this->leaveRequestsPayload(null, $status)
                : $this->notFound('Không tìm thấy lớp chủ nhiệm cần xem đơn xin nghỉ.');
        }

        if (! $this->scope->canAccessClass($user, $class->id)) {
            return $this->forbidden('Bạn không có quyền xem đơn xin nghỉ của lớp này.');
        }

        return $this->leaveRequestsPayload($class, $status);
    }

    public function teacherSubstituteSchedule(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('substitute_teachings')) {
            return $this->unsupported('Thiếu bảng lịch dạy thay.');
        }

        $teacher = $this->resolveTeacher($user, $arguments);

        if (! $teacher && ! $this->scope->isAdminLike($user)) {
            return $this->notFound('Không tìm thấy giáo viên cần xem lịch dạy thay.');
        }

        if ($teacher && ! $this->canInspectTeacher($user, $teacher)) {
            return $this->forbidden('Bạn không có quyền xem lịch dạy thay của giáo viên này.');
        }

        $from = $this->resolveDate($arguments['from_date'] ?? $arguments['date'] ?? null);
        $to = $this->resolveDate($arguments['to_date'] ?? $arguments['date'] ?? null);

        $rows = DB::table('substitute_teachings as st')
            ->leftJoin('classes', 'classes.id', '=', 'st.class_id')
            ->leftJoin('users as original_teachers', 'original_teachers.id', '=', 'st.original_teacher_id')
            ->leftJoin('users as substitute_teachers', 'substitute_teachers.id', '=', 'st.substitute_teacher_id')
            ->leftJoin('timetable_entries as te', 'te.id', '=', 'st.timetable_entry_id')
            ->when($teacher, function ($query) use ($teacher) {
                $ids = $this->teacherIdCandidatesFromRow($teacher);
                $query->where(function ($inner) use ($ids) {
                    $inner->whereIn('st.original_teacher_id', $ids)
                        ->orWhereIn('st.substitute_teacher_id', $ids);
                });
            })
            ->when($from, fn ($query) => $query->whereDate('st.substitute_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('st.substitute_date', '<=', $to))
            ->select(
                'st.substitute_date',
                'st.from_date',
                'st.to_date',
                'st.scope_type',
                'st.status',
                'classes.name as class_name',
                'te.period',
                'original_teachers.name as original_teacher_name',
                'original_teachers.full_name as original_teacher_full_name',
                'substitute_teachers.name as substitute_teacher_name',
                'substitute_teachers.full_name as substitute_teacher_full_name'
            )
            ->orderByDesc('st.substitute_date')
            ->limit(40)
            ->get();

        return [
            'teacher' => $teacher ? $this->teacherRow($teacher) : null,
            'from_date' => $from,
            'to_date' => $to,
            'substitute_schedules' => $rows->map(fn ($row) => [
                'date' => (string) ($row->substitute_date ?: $row->from_date),
                'to_date' => (string) $row->to_date,
                'scope_type' => $row->scope_type,
                'class' => $row->class_name,
                'period' => $row->period,
                'original_teacher' => $row->original_teacher_name ?: $row->original_teacher_full_name,
                'substitute_teacher' => $row->substitute_teacher_name ?: $row->substitute_teacher_full_name,
                'status' => $row->status,
            ])->values()->all(),
        ];
    }

    public function announcementsDocuments(User $user, array $arguments = []): array
    {
        if (! Schema::hasTable('school_posts')) {
            return $this->unsupported('Thiếu bảng nội dung trường học.');
        }

        $type = (string) ($arguments['type'] ?? 'all');
        $postTypes = match ($type) {
            'announcements' => ['post'],
            'events' => ['event'],
            'documents' => ['document'],
            default => ['post', 'event', 'document'],
        };

        $role = $user->role === 'staff' ? 'admin' : $user->role;
        $rows = DB::table('school_posts')
            ->when(Schema::hasColumn('school_posts', 'post_type'), fn ($query) => $query->whereIn('post_type', $postTypes))
            ->when(Schema::hasColumn('school_posts', 'is_published'), fn ($query) => $query->where('is_published', true))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->filter(fn ($row) => $this->postVisibleToRole($row, $role, $user));

        return [
            'type' => $type,
            'items' => $rows->map(fn ($row) => [
                'title' => $row->title,
                'summary' => $row->summary ?? $row->description ?? null,
                'category' => $row->category ?? $row->type ?? $row->post_type ?? null,
                'published_at' => (string) ($row->published_at ?? $row->starts_at ?? $row->created_at ?? ''),
                'file_url' => $this->publicFileUrl($row->file_url ?? null),
            ])->values()->all(),
        ];
    }

    public function scoreCalculationRules(User $user, array $arguments = []): array
    {
        $setting = Schema::hasTable('score_settings')
            ? DB::table('score_settings')->first()
            : null;

        $weights = [
            'regular' => (int) ($setting->weight_gdtx ?? 1),
            'midterm' => (int) ($setting->weight_dggk ?? 2),
            'final' => (int) ($setting->weight_dgck ?? 3),
        ];

        $columns = Schema::hasTable('score_columns')
            ? DB::table('score_columns')
                ->leftJoin('subjects', 'subjects.id', '=', 'score_columns.subject_id')
                ->when(Schema::hasColumn('score_columns', 'score_record_type'), fn ($query) => $query->where('score_columns.score_record_type', 'column'))
                ->when(Schema::hasColumn('score_columns', 'is_active'), fn ($query) => $query->where('score_columns.is_active', true))
                ->select('score_columns.name', 'score_columns.type', 'score_columns.weight_group', 'score_columns.grade_level', 'subjects.name as subject_name')
                ->orderBy('score_columns.grade_level')
                ->orderBy('subjects.name')
                ->orderBy('score_columns.sort_order')
                ->limit(40)
                ->get()
            : collect();

        return [
            'weights' => [
                'Đánh giá thường xuyên' => $weights['regular'],
                'Đánh giá giữa kỳ' => $weights['midterm'],
                'Đánh giá cuối kỳ' => $weights['final'],
            ],
            'formula' => '(Tổng điểm thành phần nhân trọng số) / (Tổng số cột điểm nhân trọng số), làm tròn 1 chữ số thập phân.',
            'configured_columns' => $columns->map(fn ($column) => [
                'name' => $column->name,
                'type' => $column->type,
                'weight' => (int) ($column->weight_group ?: ($weights[$column->type] ?? $weights['regular'])),
                'grade_level' => $column->grade_level,
                'subject' => $column->subject_name,
            ])->values()->all(),
        ];
    }

    public function systemNavigation(User $user, array $arguments = []): array
    {
        $role = $this->scope->roleLabel($user);
        $query = $this->scope->normalize((string) ($arguments['query'] ?? $arguments['feature'] ?? ''));

        $items = collect([
            ['label' => 'Lớp học', 'section' => 'Học vụ', 'group' => 'Tổ chức giảng dạy', 'route' => 'classes.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Phân công giảng dạy', 'section' => 'Học vụ', 'group' => 'Tổ chức giảng dạy', 'route' => 'assignments.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Thời khóa biểu', 'section' => 'Học vụ', 'group' => 'Tổ chức giảng dạy', 'route' => 'timetable.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Lịch kiểm tra', 'section' => 'Học vụ', 'group' => 'Tổ chức giảng dạy', 'route' => 'exam-schedules.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Điểm danh', 'section' => 'Học vụ', 'group' => 'Quản lý kết quả', 'route' => 'attendance.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Điểm số', 'section' => 'Học vụ', 'group' => 'Quản lý kết quả', 'route' => 'scores.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Hạnh kiểm', 'section' => 'Học vụ', 'group' => 'Quản lý kết quả', 'route' => 'conduct.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Khen thưởng', 'section' => 'Học vụ', 'group' => 'Quản lý kết quả', 'route' => 'rewards.index', 'roles' => ['admin', 'staff', 'teacher']],
            ['label' => 'Diện mạo trường', 'section' => 'Nội dung', 'group' => 'Quản lý nội dung', 'route' => 'system.settings.edit', 'roles' => ['admin', 'staff']],
            ['label' => 'Thông báo', 'section' => 'Nội dung', 'group' => 'Quản lý nội dung', 'route' => 'announcements.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Sự kiện', 'section' => 'Nội dung', 'group' => 'Quản lý nội dung', 'route' => 'events.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Tài liệu học tập', 'section' => 'Nội dung', 'group' => 'Quản lý nội dung', 'route' => 'documents.index', 'roles' => ['admin', 'staff', 'teacher', 'student', 'parent']],
            ['label' => 'Mốc điểm học lực', 'section' => 'Hệ thống', 'group' => 'Thiết lập danh mục & Quy định', 'route' => 'system.academic-levels.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Định mức hạnh kiểm', 'section' => 'Hệ thống', 'group' => 'Thiết lập danh mục & Quy định', 'route' => 'system.conduct-levels.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Cấu hình mức thu', 'section' => 'Hệ thống', 'group' => 'Thiết lập danh mục & Quy định', 'route' => 'system.tuition-levels.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Lịch dạy thay', 'section' => 'Hệ thống', 'group' => 'Thiết lập danh mục & Quy định', 'route' => 'substitute-teachings.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Quản lý học phí', 'section' => 'Hệ thống', 'group' => 'Vận hành & Bảo mật', 'route' => 'tuition-fees.index', 'roles' => ['admin', 'staff']],
            ['label' => 'Học phí & Khoản thu', 'section' => 'Học phí', 'group' => 'Phụ huynh', 'route' => 'parent.tuition-fees.index', 'roles' => ['parent']],
            ['label' => 'Học phí lớp chủ nhiệm', 'section' => 'Học vụ', 'group' => 'Giáo viên chủ nhiệm', 'route' => 'teacher.tuition-fees.homeroom', 'roles' => ['teacher']],
        ]);

        $allowed = $items->filter(fn ($item) => in_array($role, $item['roles'], true) || $this->scope->isAdminLike($user));

        if ($query !== '') {
            $allowed = $allowed->filter(function ($item) use ($query) {
                return str_contains($this->scope->normalize($item['label']), $query)
                    || str_contains($this->scope->normalize($item['section']), $query)
                    || str_contains($this->scope->normalize($item['group']), $query);
            });
        }

        return [
            'role' => $role,
            'items' => $allowed->values()->map(fn ($item) => [
                'label' => $item['label'],
                'section' => $item['section'],
                'group' => $item['group'],
                'route' => $item['route'],
                'available' => Route::has($item['route']),
            ])->all(),
        ];
    }

    private function leaveRequestsPayload(?object $class, string $status): array
    {
        $rows = DB::table('parent_leave_requests as plr')
            ->leftJoin('users as students', 'students.id', '=', 'plr.student_id')
            ->leftJoin('classes', 'classes.id', '=', 'plr.class_id')
            ->when($class, fn ($query) => $query->where('plr.class_id', $class->id))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('plr.status', $status))
            ->select('plr.leave_date', 'plr.reason', 'plr.status', 'plr.homeroom_note', 'students.name as student_name', 'students.full_name as student_full_name', 'students.student_code', 'classes.name as class_name')
            ->orderByDesc('plr.created_at')
            ->limit(30)
            ->get();

        return [
            'class' => $class ? $this->classRow($class) : null,
            'status_filter' => $status,
            'summary' => [
                'total' => $rows->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'approved' => $rows->where('status', 'approved')->count(),
                'rejected' => $rows->where('status', 'rejected')->count(),
            ],
            'requests' => $rows->map(fn ($row) => [
                'student' => trim((string) ($row->student_code ? $row->student_code . ' - ' : '') . ($row->student_name ?: $row->student_full_name)),
                'class' => $row->class_name,
                'leave_date' => (string) $row->leave_date,
                'reason' => $row->reason,
                'status' => $row->status,
                'homeroom_note' => $row->homeroom_note,
            ])->values()->all(),
        ];
    }

    private function resolveClassForUser(User $user, array $arguments): ?object
    {
        if ($this->scope->isAdminLike($user)) {
            return $this->resolveClass($arguments);
        }

        if ($this->hasClassIdentifier($arguments)) {
            $scopedClass = $this->resolveClass($arguments, $this->scope->classIdsInScope($user));

            if ($scopedClass) {
                return $scopedClass;
            }

            return $this->resolveClass($arguments);
        }

        if ($user->isStudent()) {
            $student = $this->scope->currentStudent($user);
            return $student ? $this->resolveClass(['class_id' => $student->class_id]) : null;
        }

        if ($user->isParent()) {
            $resolved = $this->scope->resolveScopedStudent($user, $arguments);
            return $resolved['student'] ? $this->resolveClass(['class_id' => $resolved['student']->class_id]) : null;
        }

        return null;
    }

    private function resolveClass(array $arguments, ?Collection $allowedClassIds = null): ?object
    {
        if (! Schema::hasTable('classes')) {
            return null;
        }

        $classId = trim((string) ($arguments['class_id'] ?? ''));
        $className = trim((string) ($arguments['class_name'] ?? $arguments['class'] ?? ''));

        if ($classId !== '') {
            $query = DB::table('classes')->where('id', $classId);

            if ($allowedClassIds !== null) {
                $query->whereIn('id', $allowedClassIds->map(fn ($id) => (string) $id)->all());
            }

            return $query->first();
        }

        if ($className === '') {
            return null;
        }

        $normalized = $this->scope->normalize($className);
        $code = $this->scope->normalizeCode($className);

        return DB::table('classes')
            ->when($allowedClassIds !== null, fn ($query) => $query->whereIn('id', $allowedClassIds->map(fn ($id) => (string) $id)->all()))
            ->get(['id', 'name', 'grade_level', 'homeroom_teacher_id'])
            ->first(function ($class) use ($normalized, $code) {
                $className = $this->scope->normalize((string) $class->name);
                $classCode = $this->scope->normalizeCode((string) $class->name);

                return $className === $normalized
                    || $classCode === $code
                    || ($normalized !== '' && str_contains($className, $normalized))
                    || ($code !== '' && str_contains($classCode, $code));
            });
    }

    private function hasClassIdentifier(array $arguments): bool
    {
        return trim((string) ($arguments['class_id'] ?? '')) !== ''
            || trim((string) ($arguments['class_name'] ?? $arguments['class'] ?? '')) !== '';
    }

    private function resolveSubject(array $arguments): ?object
    {
        if (! Schema::hasTable('subjects')) {
            return null;
        }

        $subjectId = trim((string) ($arguments['subject_id'] ?? ''));
        $subjectName = trim((string) ($arguments['subject_name'] ?? $arguments['subject'] ?? ''));

        if ($subjectId !== '') {
            return $this->subjectQuery()->where('id', $subjectId)->first();
        }

        if ($subjectName === '') {
            return null;
        }

        $normalized = $this->scope->normalize($subjectName);

        return $this->subjectQuery()
            ->get(['id', 'name', 'code'])
            ->first(function ($subject) use ($normalized) {
                $name = $this->scope->normalize((string) $subject->name);
                $code = $this->scope->normalize((string) ($subject->code ?? ''));

                return $name === $normalized
                    || ($name !== '' && str_contains($name, $normalized))
                    || ($code !== '' && str_contains($normalized, $code));
            });
    }

    private function resolveTeacher(User $user, array $arguments): ?object
    {
        if ($user->isTeacher() && ! $this->scope->isAdminLike($user) && empty($arguments['teacher_name'])) {
            return $this->scope->currentTeacher($user);
        }

        if (! Schema::hasTable('users')) {
            return null;
        }

        $teacherName = trim((string) ($arguments['teacher_name'] ?? $arguments['teacher'] ?? ''));
        $teacherId = trim((string) ($arguments['teacher_id'] ?? ''));

        if ($teacherId !== '') {
            return $this->teacherQuery()->where('id', $teacherId)->first();
        }

        if ($teacherName !== '') {
            $normalized = $this->scope->normalize($teacherName);

            return $this->teacherQuery()
                ->get(['id', 'name', 'full_name', 'teacher_code', 'teacher_id', 'source_profile_id'])
                ->first(function ($teacher) use ($normalized) {
                    $name = $this->scope->normalize((string) ($teacher->name ?? $teacher->full_name ?? ''));
                    $code = $this->scope->normalize((string) ($teacher->teacher_code ?? ''));

                    return $name === $normalized
                        || ($name !== '' && str_contains($name, $normalized))
                        || ($code !== '' && str_contains($normalized, $code));
                });
        }

        return $user->isTeacher() ? $this->scope->currentTeacher($user) : null;
    }

    private function canInspectTeacher(User $user, object $teacher): bool
    {
        if ($this->scope->isAdminLike($user)) {
            return true;
        }

        if (! $user->isTeacher()) {
            return false;
        }

        return in_array((string) $teacher->id, $this->scope->teacherIdentityCandidates($user), true);
    }

    private function assignmentQuery()
    {
        $query = DB::table('teaching_assignments');

        if (Schema::hasColumn('teaching_assignments', 'status')) {
            $query->where(function ($inner) {
                $inner->whereNull('teaching_assignments.status')
                    ->orWhere('teaching_assignments.status', 'active');
            });
        }

        return $query;
    }

    private function timetableEntryBaseQuery()
    {
        return DB::table('timetable_entries as te')
            ->leftJoin('teaching_assignments as ta', 'ta.id', '=', 'te.assignment_id')
            ->leftJoin('classes', 'classes.id', '=', 'ta.class_id')
            ->leftJoin('subjects', function ($join) {
                $join->on('subjects.id', '=', 'te.subject_id')
                    ->orOn('subjects.id', '=', 'ta.subject_id');
            })
            ->leftJoin('rooms', 'rooms.id', '=', 'te.room_id')
            ->select(
                'te.id',
                'te.day_of_week',
                'te.period',
                'te.room',
                'te.note',
                'classes.name as class_name',
                'subjects.name as subject_name',
                'rooms.name as room_name'
            );
    }

    private function timetableRowsForClass(string $classId, array $arguments = []): array
    {
        if (! Schema::hasTable('timetable_entries')) {
            return [];
        }

        $dayOfWeek = $this->resolveDayOfWeek($arguments);

        return $this->timetableEntryBaseQuery()
            ->where('ta.class_id', $classId)
            ->when($dayOfWeek, fn ($query) => $query->where('te.day_of_week', $dayOfWeek))
            ->orderBy('te.day_of_week')
            ->orderBy('te.period')
            ->limit(40)
            ->get()
            ->map(fn ($entry) => $this->timetableEntryRow($entry))
            ->values()
            ->all();
    }

    private function timetableEntryRow(object $entry): array
    {
        return [
            'day_of_week' => (int) $entry->day_of_week,
            'day_label' => $this->dayLabel((int) $entry->day_of_week),
            'period' => (int) $entry->period,
            'class' => $entry->class_name,
            'subject' => $entry->subject_name,
            'room' => $entry->room_name ?: $entry->room,
            'note' => $entry->note,
        ];
    }

    private function studentQuery()
    {
        return $this->scope->studentQuery();
    }

    private function teacherQuery()
    {
        return $this->scope->teacherQuery();
    }

    private function subjectQuery()
    {
        $query = DB::table('subjects');

        if (Schema::hasColumn('subjects', 'subject_record_type')) {
            $query->where('subject_record_type', 'subject');
        }

        return $query;
    }

    private function tableCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function countUsersByRole(string $roleType): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return DB::table('users')
            ->where(function ($query) use ($roleType) {
                $query->where('role_type', $roleType)
                    ->orWhere('role', $roleType);
            })
            ->count();
    }

    private function lockedUserCount(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return DB::table('users')
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'is_active')) {
                    $query->orWhere('is_active', false);
                }

                if (Schema::hasColumn('users', 'login_status')) {
                    $query->orWhere('login_status', false);
                }
            })
            ->count();
    }

    private function resolveDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === 'today') {
            return now()->format('Y-m-d');
        }

        if ($value === 'tomorrow') {
            return now()->addDay()->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveDayOfWeek(array $arguments): ?int
    {
        $day = (int) ($arguments['day_of_week'] ?? 0);

        if ($day >= 1 && $day <= 6) {
            return $day;
        }

        $date = $this->resolveDate($arguments['date'] ?? null);

        if (! $date) {
            return null;
        }

        $day = (int) Carbon::parse($date)->dayOfWeekIso;

        return $day <= 6 ? $day : null;
    }

    private function dayLabel(int $day): string
    {
        return [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7'][$day] ?? 'Không rõ';
    }

    private function classRow(object $class): array
    {
        return [
            'id' => (string) $class->id,
            'name' => (string) $class->name,
            'grade_level' => $class->grade_level ?? null,
        ];
    }

    private function studentRow(object $student): array
    {
        return [
            'id' => (string) $student->id,
            'code' => (string) ($student->student_code ?? ''),
            'name' => (string) ($student->name ?? $student->full_name ?? ''),
            'class_id' => (string) ($student->class_id ?? ''),
        ];
    }

    private function teacherRow(object $teacher): array
    {
        return [
            'id' => (string) $teacher->id,
            'code' => (string) ($teacher->teacher_code ?? ''),
            'name' => (string) ($teacher->name ?? $teacher->full_name ?? ''),
        ];
    }

    private function teacherIdCandidatesFromRow(object $teacher): array
    {
        return collect([$teacher->id ?? null, $teacher->teacher_id ?? null, $teacher->source_profile_id ?? null])
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeFeeItems(object $fee): array
    {
        $items = json_decode((string) ($fee->fee_items ?? ''), true);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)->map(fn ($item) => [
            'label' => (string) ($item['label'] ?? 'Khoản thu'),
            'amount' => $this->money((float) ($item['amount'] ?? 0)),
            'status' => (string) ($item['status'] ?? $fee->status ?? 'unpaid'),
            'exemption_label' => (string) ($item['exemption_label'] ?? ''),
        ])->values()->all();
    }

    private function tuitionQrImageUrl(): string
    {
        if (! Schema::hasTable('system_settings') || ! Schema::hasColumn('system_settings', 'key')) {
            return '';
        }

        $path = (string) DB::table('system_settings')->where('key', 'tuition_qr_image')->value('value');

        return $this->publicFileUrl($path);
    }

    private function publicFileUrl(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $normalized = Str::after(ltrim(str_replace('\\', '/', $path), '/'), 'storage/');

        return asset('storage/' . $normalized);
    }

    private function postVisibleToRole(object $row, string $role, User $user): bool
    {
        $source = (string) (($row->content ?? '') . "\n" . ($row->description ?? ''));

        if (! preg_match('/<!--school_manager_meta:(.*?)-->/s', $source, $matches)) {
            return true;
        }

        $meta = json_decode($matches[1], true);
        $roles = is_array($meta) ? ($meta['target_roles'] ?? ['all']) : ['all'];

        if (! is_array($roles) || in_array('all', $roles, true)) {
            return true;
        }

        $userRoles = [$role];
        if ($user->isTeacher()) {
            $userRoles[] = 'teacher';
        }
        if ($user->isHomeroom()) {
            $userRoles[] = 'homeroom';
        }

        return (bool) array_intersect($roles, $userRoles);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . 'đ';
    }

    private function unsupported(string $message): array
    {
        return ['success' => false, 'error_type' => 'unsupported_schema', 'message' => $message];
    }

    private function forbidden(string $message): array
    {
        return ['success' => false, 'error_type' => 'forbidden', 'message' => $message];
    }

    private function notFound(string $message): array
    {
        return ['success' => false, 'error_type' => 'not_found', 'message' => $message];
    }
}
