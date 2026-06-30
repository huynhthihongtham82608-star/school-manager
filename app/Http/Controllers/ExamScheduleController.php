<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $query = Schema::hasTable('exam_schedules')
            ? ExamSchedule::with(['classRoom', 'subject', 'semester.schoolYear'])
            : null;

        if ($query && ! ($user->isAdmin() || $user->isStaff())) {
            $query->where(function ($query) {
                $query->where('note', 'not like', '%"status":"draft"%');
            });
        }

        if ($query && $user->isStudent() && $user->student) {
            $query->where('class_id', $user->student->class_id);
        }

        if ($query && $user->isParent() && $user->parentProfile) {
            $classIds = $user->parentProfile->students()->pluck('students.class_id')->filter()->unique();
            $query->whereIn('class_id', $classIds);
        }

        if ($query && $selectedYearId) {
            $semesterIds = Schema::hasTable('semesters')
                ? Semester::where('school_year_id', $selectedYearId)->pluck('id')
                : collect();

            $query->where(function ($yearQuery) use ($selectedYearId, $semesterIds) {
                $yearQuery->where('note', 'like', '%"school_year_id":"' . $selectedYearId . '"%');

                if ($semesterIds->isNotEmpty()) {
                    $yearQuery->orWhereIn('semester_id', $semesterIds);
                }
            });
        }

        $schedules = $query ? $query->orderBy('exam_date')->orderBy('start_time')->paginate(12) : collect();
        $classes = Schema::hasTable('classes')
            ? SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->orderBy('name')->get()
            : collect();
        $subjects = Schema::hasTable('subjects') ? Subject::orderBy('name')->get() : collect();
        $semesters = Schema::hasTable('semesters')
            ? Semester::with('schoolYear')->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->orderByDesc('created_at')->get()
            : collect();
        $years = Schema::hasTable('school_years') ? SchoolYear::orderByDesc('start_date')->get() : collect();
        $examTypes = ExamSchedule::EXAM_TYPES;

        return view('exam_schedules.index', compact('schedules', 'classes', 'subjects', 'semesters', 'years', 'examTypes', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureValidScheduleWindow($data);
        $this->ensureNoConflicts($data);

        $meta = [
            'school_year_id' => $data['school_year_id'],
            'status' => $data['status'] ?? 'draft',
        ];
        unset($data['school_year_id'], $data['status']);

        $schedule = ExamSchedule::create([
            ...$data,
            'note' => ExamSchedule::withMeta($data['note'] ?? null, $meta),
        ]);

        AuditLogger::log('exam_schedule_created', ExamSchedule::class, $schedule->id, 'Tạo lịch thi');

        return back()->with('success', 'Đã thêm lịch thi.');
    }

    public function update(Request $request, ExamSchedule $examSchedule)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureValidScheduleWindow($data);
        $this->ensureNoConflicts($data, $examSchedule);

        $meta = [
            'school_year_id' => $data['school_year_id'],
            'status' => $data['status'] ?? 'draft',
        ];
        unset($data['school_year_id'], $data['status']);

        $examSchedule->update([
            ...$data,
            'note' => ExamSchedule::withMeta($data['note'] ?? null, $meta),
        ]);

        AuditLogger::log('exam_schedule_updated', ExamSchedule::class, $examSchedule->id, 'Cập nhật lịch thi');

        return back()->with('success', 'Đã cập nhật lịch thi.');
    }

    public function destroy(Request $request, ExamSchedule $examSchedule)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $scheduleId = $examSchedule->id;
        $examSchedule->delete();

        AuditLogger::log('exam_schedule_deleted', ExamSchedule::class, $scheduleId, 'Xóa lịch thi');

        return back()->with('success', 'Đã xóa lịch thi.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', Rule::in(ExamSchedule::EXAM_TYPES)],
            'school_year_id' => ['required', 'string', 'max:50', 'exists:school_years,id'],
            'class_id' => ['required', 'string', 'max:50', 'exists:classes,id'],
            'subject_id' => ['required', 'string', 'max:50', 'exists:subjects,id'],
            'semester_id' => ['required', 'string', 'max:50', 'exists:semesters,id'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'status' => ['required', Rule::in(ExamSchedule::MANAGEMENT_STATUSES)],
        ];
    }

    private function ensureValidScheduleWindow(array $data): void
    {
        if ($this->minutes($data['end_time']) <= $this->minutes($data['start_time'])) {
            throw ValidationException::withMessages([
                'end_time' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.',
            ]);
        }
    }

    private function ensureNoConflicts(array $data, ?ExamSchedule $ignore = null): void
    {
        if (($data['status'] ?? null) === 'canceled') {
            return;
        }

        $query = ExamSchedule::query()
            ->where('exam_date', $data['exam_date'])
            ->where(function ($query) use ($data) {
                $query->where('class_id', $data['class_id'])
                    ->orWhere('room', $data['room']);
            })
            ->get();

        $start = $this->minutes($data['start_time']);
        $end = $this->minutes($data['end_time']);

        foreach ($query as $schedule) {
            if ($ignore && $schedule->id === $ignore->id) {
                continue;
            }

            if ($schedule->isCanceled()) {
                continue;
            }

            if (! $schedule->start_time || ! $schedule->end_time) {
                continue;
            }

            $overlaps = $start < $this->minutes($schedule->end_time)
                && $end > $this->minutes($schedule->start_time);

            if (! $overlaps) {
                continue;
            }

            if ($schedule->class_id === $data['class_id']) {
                throw ValidationException::withMessages([
                    'class_id' => 'Lớp này đã có lịch thi trùng thời gian.',
                ]);
            }

            if ($schedule->room === $data['room']) {
                throw ValidationException::withMessages([
                    'room' => 'Phòng thi này đã có lịch thi trùng thời gian.',
                ]);
            }
        }
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hour * 60 + $minute;
    }
}
