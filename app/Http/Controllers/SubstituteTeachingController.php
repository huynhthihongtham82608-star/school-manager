<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\SubstituteTeaching;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubstituteTeachingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $selectedClassId = $request->query('class_id', 'all');
        $selectedStatus = $request->query('status', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $classes = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if (! $selectedSemesterId && $semesters->isNotEmpty()) {
            $selectedSemesterId = optional($semesters->first(fn ($semester) => $semester->isActive()))->id
                ?? $semesters->first()->id;
        }

        $entries = TimetableEntry::with(['timetable.classRoom', 'assignment.subject', 'assignment.teacher', 'subject', 'teacher', 'roomInfo'])
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable', function ($query) use ($selectedYearId, $selectedSemesterId, $selectedClassId) {
                $query->when($selectedYearId, fn ($inner) => $inner->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($inner) => $inner->where('semester_id', $selectedSemesterId))
                    ->when($selectedClassId !== 'all', fn ($inner) => $inner->where('class_id', $selectedClassId));
            })
            ->get()
            ->sortBy(fn (TimetableEntry $entry) => sprintf(
                '%s|%s|%02d|%02d',
                $entry->timetable?->classRoom?->name ?? '',
                $entry->day_of_week,
                $entry->period,
                $entry->id
            ))
            ->values();

        $substitutes = SubstituteTeaching::with(['classRoom', 'semester.schoolYear', 'timetableEntry.assignment.subject', 'timetableEntry.subject', 'originalTeacher', 'substituteTeacher'])
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->when($selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($selectedStatus !== 'all', fn ($query) => $query->where('status', $selectedStatus))
            ->orderByDesc('substitute_date')
            ->latest()
            ->get();

        $teachers = Teacher::where('work_status', Teacher::STATUS_WORKING)
            ->orderBy('name')
            ->get();

        return view('substitute_teachings.index', [
            'substitutes' => $substitutes,
            'classes' => $classes,
            'semesters' => $semesters,
            'entries' => $entries,
            'teachers' => $teachers,
            'statusLabels' => SubstituteTeaching::statusLabels(),
            'scopeLabels' => SubstituteTeaching::scopeLabels(),
            'selectedSemesterId' => $selectedSemesterId,
            'selectedClassId' => $selectedClassId,
            'selectedStatus' => $selectedStatus,
            'readOnly' => $readOnly,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $this->denyHistoricalWrite();

        $payload = $this->payload($request);
        $created = DB::transaction(fn () => collect($payload['dates'])->map(function (string $date) use ($payload) {
            $substitute = SubstituteTeaching::firstOrNew([
                'substitute_date' => $date,
                'timetable_entry_id' => $payload['entry']->id,
            ]);

            $this->ensureSubstituteTeacherAvailable(
                $payload['entry'],
                $payload['substitute_teacher_id'],
                $date,
                $substitute->exists ? (string) $substitute->getKey() : null
            );

            if ($substitute->exists) {
                $this->restoreTimetableTeacher($substitute);
            }

            $substitute->fill($payload['attributes'] + [
                'substitute_date' => $date,
                'from_date' => $payload['from_date'],
                'to_date' => $payload['to_date'],
                'updated_by' => Auth::id(),
            ]);

            if (! $substitute->exists) {
                $substitute->created_by = Auth::id();
            }

            $substitute->save();

            $this->syncTimetableTeacher($substitute);

            return $substitute;
        }));

        AuditLogger::log('substitute_teaching_created', SubstituteTeaching::class, (string) $created->first()?->getKey(), 'Tạo lịch dạy thay');

        return back()->with('success', 'Đã lưu lịch dạy thay.');
    }

    public function update(Request $request, SubstituteTeaching $substituteTeaching)
    {
        $this->authorizeAdmin();
        $this->denyHistoricalWrite();

        $payload = $this->payload($request, $substituteTeaching);
        $date = $payload['dates'][0] ?? $substituteTeaching->substitute_date?->format('Y-m-d');

        $this->ensureSubstituteTeacherAvailable(
            $payload['entry'],
            $payload['substitute_teacher_id'],
            $date,
            (string) $substituteTeaching->getKey()
        );

        $this->restoreTimetableTeacher($substituteTeaching);
        $substituteTeaching->update($payload['attributes'] + [
            'substitute_date' => $date,
            'from_date' => $payload['from_date'],
            'to_date' => $payload['to_date'],
            'updated_by' => Auth::id(),
        ]);
        $this->syncTimetableTeacher($substituteTeaching->fresh('timetableEntry'));

        AuditLogger::log('substitute_teaching_updated', SubstituteTeaching::class, (string) $substituteTeaching->getKey(), 'Cập nhật lịch dạy thay');

        return back()->with('success', 'Đã cập nhật lịch dạy thay.');
    }

    public function destroy(SubstituteTeaching $substituteTeaching)
    {
        $this->authorizeAdmin();
        $this->denyHistoricalWrite();

        $this->restoreTimetableTeacher($substituteTeaching);
        $id = (string) $substituteTeaching->getKey();
        $substituteTeaching->delete();
        AuditLogger::log('substitute_teaching_deleted', SubstituteTeaching::class, $id, 'Xóa lịch dạy thay');

        return back()->with('success', 'Đã xóa lịch dạy thay.');
    }

    public function recommendations(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'scope_type' => ['required', Rule::in(array_keys(SubstituteTeaching::scopeLabels()))],
            'substitute_date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'timetable_entry_id' => ['required', 'string', 'exists:timetable_entries,id'],
            'substitute_teacher_id' => ['nullable', 'string', 'exists:teachers,id'],
            'ignore_substitute_id' => ['nullable', 'string'],
        ]);

        $entry = TimetableEntry::with(['timetable.classRoom', 'assignment.subject', 'assignment.teacher.department', 'subject', 'teacher.department'])
            ->findOrFail($data['timetable_entry_id']);
        $dates = $this->resolveSubstituteDates($data, $entry);
        $teacherId = $data['substitute_teacher_id'] ?? null;
        $ignoreSubstituteId = $data['ignore_substitute_id'] ?? null;
        $busy = $teacherId
            ? collect($dates)->contains(fn (string $date) => $this->isTeacherBusyAtSlot($entry, $teacherId, $date, $ignoreSubstituteId))
            : false;

        return response()->json([
            'busy' => $busy,
            'message' => $busy ? '⚠️ Giáo viên này đang có tiết dạy tại lớp khác, vui lòng chọn giáo viên khác' : null,
            'teachers' => $busy ? $this->availableReplacementTeachers($entry, $dates, $ignoreSubstituteId)->values() : [],
        ]);
    }

    private function payload(Request $request, ?SubstituteTeaching $current = null): array
    {
        $data = $request->validate([
            'scope_type' => ['required', Rule::in(array_keys(SubstituteTeaching::scopeLabels()))],
            'substitute_date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'timetable_entry_id' => ['required', 'string', 'exists:timetable_entries,id'],
            'substitute_teacher_id' => ['required', 'string', 'exists:teachers,id'],
            'status' => ['required', Rule::in(array_keys(SubstituteTeaching::statusLabels()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = TimetableEntry::with(['timetable.classRoom', 'assignment.teacher'])->findOrFail($data['timetable_entry_id']);
        $originalTeacherId = $entry->assignment?->teacher_id ?: $entry->teacher_id;
        $dates = $this->resolveSubstituteDates($data, $entry, $current);

        return [
            'entry' => $entry,
            'dates' => $dates,
            'from_date' => $data['scope_type'] === SubstituteTeaching::SCOPE_DATE_RANGE ? $data['from_date'] : null,
            'to_date' => $data['scope_type'] === SubstituteTeaching::SCOPE_DATE_RANGE ? $data['to_date'] : null,
            'substitute_teacher_id' => $data['substitute_teacher_id'],
            'attributes' => [
                'scope_type' => $data['scope_type'],
                'timetable_entry_id' => $entry->id,
                'class_id' => $entry->timetable?->class_id,
                'semester_id' => $entry->timetable?->semester_id,
                'school_year_id' => $entry->timetable?->school_year_id,
                'original_teacher_id' => $originalTeacherId,
                'substitute_teacher_id' => $data['substitute_teacher_id'],
                'status' => $data['status'],
                'note' => trim((string) ($data['note'] ?? '')),
            ],
        ];
    }

    private function resolveSubstituteDates(array $data, TimetableEntry $entry, ?SubstituteTeaching $current = null): array
    {
        if ($data['scope_type'] === SubstituteTeaching::SCOPE_DATE_RANGE) {
            if (empty($data['from_date']) || empty($data['to_date'])) {
                throw ValidationException::withMessages([
                    'from_date' => 'Vui lòng chọn đầy đủ từ ngày và đến ngày.',
                ]);
            }

            $dates = collect(CarbonPeriod::create($data['from_date'], $data['to_date']))
                ->filter(fn (Carbon $date) => (int) $date->isoWeekday() === (int) $entry->day_of_week)
                ->map(fn (Carbon $date) => $date->format('Y-m-d'))
                ->values()
                ->all();

            if (empty($dates)) {
                throw ValidationException::withMessages([
                    'from_date' => 'Khoảng ngày đã chọn không có tiết học khớp với thứ trong thời khóa biểu.',
                ]);
            }

            return $current ? [reset($dates)] : $dates;
        }

        if (empty($data['substitute_date'])) {
            throw ValidationException::withMessages([
                'substitute_date' => 'Vui lòng chọn ngày đổi tiết.',
            ]);
        }

        return [$data['substitute_date']];
    }

    private function ensureSubstituteTeacherAvailable(TimetableEntry $entry, string $teacherId, string $date, ?string $ignoreSubstituteId = null): void
    {
        if ($this->isTeacherBusyAtSlot($entry, $teacherId, $date, $ignoreSubstituteId)) {
            throw ValidationException::withMessages([
                'substitute_teacher_id' => '⚠️ Giáo viên này đang có tiết dạy tại lớp khác, vui lòng chọn giáo viên khác',
            ]);
        }
    }

    private function isTeacherBusyAtSlot(TimetableEntry $entry, string $teacherId, string $date, ?string $ignoreSubstituteId = null): bool
    {
        $period = (int) $entry->period;
        $dayOfWeek = Carbon::parse($date)->isoWeekday();

        $hasBaseBusySlot = TimetableEntry::where('status', TimetableEntry::STATUS_ACTIVE)
            ->where(function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)
                    ->orWhereHas('assignment', fn ($assignmentQuery) => $assignmentQuery->where('teacher_id', $teacherId));
            })
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period)
            ->where('id', '!=', $entry->id)
            ->whereHas('timetable', fn ($query) => $query->where('semester_id', $entry->timetable?->semester_id))
            ->exists();

        $hasSubstituteBusySlot = SubstituteTeaching::where('substitute_teacher_id', $teacherId)
            ->where('substitute_date', $date)
            ->where('status', SubstituteTeaching::STATUS_APPROVED)
            ->when($ignoreSubstituteId, fn ($query) => $query->where('id', '!=', $ignoreSubstituteId))
            ->whereHas('timetableEntry', function ($query) use ($period, $dayOfWeek, $entry) {
                $query->where('day_of_week', $dayOfWeek)
                    ->where('period', $period)
                    ->where('id', '!=', $entry->id);
            })
            ->exists();

        return $hasBaseBusySlot || $hasSubstituteBusySlot;
    }

    private function availableReplacementTeachers(TimetableEntry $entry, array $dates, ?string $ignoreSubstituteId = null)
    {
        $entry->loadMissing(['timetable', 'assignment.subject', 'assignment.teacher.department', 'subject', 'teacher.department']);

        $subjectId = $entry->assignment?->subject_id ?: $entry->subject_id;
        $originalTeacher = $entry->assignment?->teacher ?: $entry->teacher;
        $departmentId = $originalTeacher?->department_id;

        if (! $subjectId && ! $departmentId) {
            return collect();
        }

        $sameSubjectTeacherIds = $subjectId
            ? TeachingAssignment::where('subject_id', $subjectId)
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->when($entry->timetable?->school_year_id, fn ($query) => $query->where('school_year_id', $entry->timetable->school_year_id))
                ->when($entry->timetable?->semester_id, fn ($query) => $query->where('semester_id', $entry->timetable->semester_id))
                ->pluck('teacher_id')
                ->filter()
                ->unique()
                ->values()
            : collect();

        return Teacher::with(['primarySubject', 'department'])
            ->where('work_status', Teacher::STATUS_WORKING)
            ->when($originalTeacher?->id, fn ($query) => $query->where('id', '!=', $originalTeacher->id))
            ->where(function ($query) use ($subjectId, $departmentId, $sameSubjectTeacherIds) {
                if ($subjectId) {
                    $query->where('primary_subject_id', $subjectId)
                        ->orWhereIn('id', $sameSubjectTeacherIds)
                        ->orWhereHas('department.subjects', fn ($subjectQuery) => $subjectQuery->where('subjects.id', $subjectId));
                }

                if ($departmentId) {
                    $query->orWhere('department_id', $departmentId);
                }
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (Teacher $teacher) => collect($dates)->every(
                fn (string $date) => ! $this->isTeacherBusyAtSlot($entry, (string) $teacher->id, $date, $ignoreSubstituteId)
            ))
            ->map(fn (Teacher $teacher) => [
                'id' => (string) $teacher->id,
                'name' => (string) $teacher->name,
                'teacher_code' => (string) ($teacher->teacher_code ?? ''),
                'subject' => $teacher->primarySubject?->name ?: $teacher->main_subject,
                'department' => $teacher->department?->name,
            ]);
    }

    private function syncTimetableTeacher(SubstituteTeaching $substitute): void
    {
        if ($substitute->status !== SubstituteTeaching::STATUS_APPROVED) {
            return;
        }

        $entry = $substitute->timetableEntry;

        if (! $entry) {
            return;
        }

        $note = (string) $entry->note;

        $entry->update([
            'teacher_id' => $substitute->substitute_teacher_id,
            'note' => str_contains($note, 'Dạy thay') ? $note : trim(($note ? $note . ' | ' : '') . 'Dạy thay'),
        ]);
    }

    private function restoreTimetableTeacher(SubstituteTeaching $substitute): void
    {
        $entry = $substitute->timetableEntry;

        if (! $entry || (string) $entry->teacher_id !== (string) $substitute->substitute_teacher_id) {
            return;
        }

        $hasApprovedSibling = SubstituteTeaching::where('timetable_entry_id', $entry->id)
            ->where('status', SubstituteTeaching::STATUS_APPROVED)
            ->where('id', '!=', $substitute->id)
            ->exists();

        if ($hasApprovedSibling) {
            return;
        }

        $entry->update([
            'teacher_id' => $substitute->original_teacher_id,
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        if ($user?->isAdmin() || $user?->isStaff()) {
            return;
        }

        abort(403, 'Chỉ Admin được quản lý lịch dạy thay.');
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi lịch dạy thay.',
            ]);
        }
    }
}
