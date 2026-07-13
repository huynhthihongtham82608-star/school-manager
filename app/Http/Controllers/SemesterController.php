<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Conduct;
use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use App\Support\CurrentAcademicContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderByDesc('school_year_id')
            ->orderBy('name')
            ->get();
        $years = $this->availableSchoolYears();
        $deleteChecks = $semesters->mapWithKeys(fn (Semester $semester) => [
            (string) $semester->getKey() => $this->deleteCheck($semester),
        ]);
        $readOnly = $this->isHistoricalReadOnly();

        return view('semesters.index', compact('semesters', 'years', 'selectedYearId', 'deleteChecks', 'readOnly'));
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('semesters.index')->withErrors([
                'semester' => 'Đang xem dữ liệu lịch sử, không thể thêm học kỳ.',
            ]);
        }

        return view('semesters.create', [
            'years' => $this->availableSchoolYears(),
            'termOptions' => Semester::termOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();
        $data = $this->validatedData($request);

        $semester = Semester::create($data + [
            'status' => Semester::STATUS_DRAFT,
            'is_score_input_open' => false,
        ]);

        AuditLogger::log('semester_created', Semester::class, (string) $semester->getKey(), 'Tạo học kỳ ' . $semester->name);

        return redirect()
            ->route('semesters.index', ['school_year_id' => $semester->school_year_id])
            ->with('success', 'Đã tạo học kỳ.');
    }

    public function show(Semester $semester)
    {
        $semester->load('schoolYear');

        return view('semesters.show', [
            'semester' => $semester,
            'logs' => $this->semesterLogs($semester),
            'deleteCheck' => $this->deleteCheck($semester),
            'readOnly' => $this->isHistoricalReadOnly(),
        ]);
    }

    public function edit(Semester $semester)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('semesters.index')->withErrors([
                'semester' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa học kỳ.',
            ]);
        }

        if (! $semester->canEdit()) {
            return redirect()
                ->route('semesters.show', $semester)
                ->withErrors(['semester' => 'Chỉ học kỳ ở trạng thái Bản nháp hoặc Chưa hoạt động mới được chỉnh sửa.']);
        }

        return view('semesters.edit', [
            'semester' => $semester,
            'years' => $this->availableSchoolYears(),
            'termOptions' => Semester::termOptions(),
        ]);
    }

    public function update(Request $request, Semester $semester)
    {
        $this->denyHistoricalWrite();

        if (! $semester->canEdit()) {
            return back()->withErrors([
                'semester' => 'Chỉ học kỳ ở trạng thái Bản nháp hoặc Chưa hoạt động mới được chỉnh sửa.',
            ]);
        }

        $semester->update($this->validatedData($request, $semester));
        AuditLogger::log('semester_updated', Semester::class, (string) $semester->getKey(), 'Chỉnh sửa học kỳ ' . $semester->name);

        return redirect()
            ->route('semesters.index', ['school_year_id' => $semester->school_year_id])
            ->with('success', 'Đã cập nhật học kỳ.');
    }

    public function markInactive(Semester $semester)
    {
        $this->denyHistoricalWrite();

        if (! $semester->canMoveToInactive()) {
            return back()->withErrors(['semester' => 'Chỉ học kỳ Bản nháp mới được chuyển sang Chưa hoạt động.']);
        }

        $semester->update([
            'status' => Semester::STATUS_INACTIVE,
            'is_score_input_open' => false,
        ]);
        AuditLogger::log('semester_marked_inactive', Semester::class, (string) $semester->getKey(), 'Chuyển học kỳ sang Chưa hoạt động');

        return back()->with('success', 'Đã chuyển học kỳ sang Chưa hoạt động.');
    }

    public function activate(Semester $semester)
    {
        $this->denyHistoricalWrite();

        if (! $semester->canActivate()) {
            return back()->withErrors(['semester' => 'Chỉ học kỳ Chưa hoạt động mới được đặt làm hiện hành.']);
        }

        if ($semester->schoolYear?->isArchived()) {
            return back()->withErrors(['semester' => 'Không thể đặt học kỳ thuộc năm học đã lưu trữ làm hiện hành.']);
        }

        $currentYear = app(CurrentAcademicContext::class)->schoolYear();
        if (! $currentYear || (string) $semester->school_year_id !== (string) $currentYear->getKey()) {
            return back()->withErrors(['semester' => 'Học kỳ hiện hành phải thuộc năm học hiện hành.']);
        }

        app(CurrentAcademicContext::class)->setCurrentSemester($semester);
        AuditLogger::log('semester_activated', Semester::class, (string) $semester->getKey(), 'Đặt học kỳ hiện hành ' . $semester->name);

        return back()->with('success', 'Đã đặt học kỳ hiện hành.');
    }

    public function lock(Semester $semester)
    {
        $this->denyHistoricalWrite();

        if (! $semester->canLock()) {
            return back()->withErrors(['semester' => 'Chỉ học kỳ Hoạt động mới được khóa.']);
        }

        $semester->update([
            'status' => Semester::STATUS_LOCKED,
            'is_score_input_open' => false,
            'locked_at' => now(),
        ]);
        AuditLogger::log('semester_locked', Semester::class, (string) $semester->getKey(), 'Khóa học kỳ ' . $semester->name);

        return back()->with('success', 'Đã khóa học kỳ.');
    }

    public function archive(Semester $semester)
    {
        $this->denyHistoricalWrite();

        if (! $semester->canArchive()) {
            return back()->withErrors(['semester' => 'Chỉ học kỳ đã Khóa mới được lưu trữ.']);
        }

        $this->archiveTimetableEntriesForSemester($semester);
        $this->archiveAssignmentsForSemester($semester);
        $this->archiveClassesForSemester($semester);

        $semester->update([
            'status' => Semester::STATUS_ARCHIVED,
            'is_score_input_open' => false,
            'archived_at' => now(),
        ]);
        AuditLogger::log('semester_archived', Semester::class, (string) $semester->getKey(), 'Lưu trữ học kỳ ' . $semester->name);

        return back()->with('success', 'Đã lưu trữ học kỳ.');
    }

    public function destroy(Semester $semester)
    {
        $this->denyHistoricalWrite();
        $deleteCheck = $this->deleteCheck($semester);

        if (! $deleteCheck['allowed']) {
            return back()->withErrors(['semester' => $deleteCheck['message']]);
        }

        $semesterName = $semester->name;
        $semesterId = (string) $semester->getKey();
        $semester->delete();

        AuditLogger::log('semester_deleted', Semester::class, $semesterId, 'Xóa học kỳ ' . $semesterName);

        return redirect()
            ->route('semesters.index')
            ->with('success', 'Đã xóa học kỳ.');
    }

    private function validatedData(Request $request, ?Semester $semester = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::in(array_keys(Semester::termOptions()))],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        $year = SchoolYear::findOrFail($validated['school_year_id']);

        if ($year->isArchived()) {
            throw ValidationException::withMessages([
                'school_year_id' => 'Không thể thêm học kỳ vào năm học đã lưu trữ.',
            ]);
        }

        if ($this->semesterExists($validated['school_year_id'], $validated['name'], $semester)) {
            throw ValidationException::withMessages([
                'name' => 'Năm học này đã có ' . $validated['name'] . '.',
            ]);
        }

        return [
            'name' => $validated['name'],
            'school_year_id' => $validated['school_year_id'],
            'order' => $this->termOrder($validated['name']),
        ];
    }

    private function availableSchoolYears()
    {
        return SchoolYear::query()
            ->whereNull('archived_at')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();
    }

    private function semesterExists(string $schoolYearId, string $name, ?Semester $except = null): bool
    {
        $query = Semester::where('school_year_id', $schoolYearId)
            ->whereIn('name', $this->nameAliases($name));

        if ($except) {
            $query->whereKeyNot($except->getKey());
        }

        return $query->exists();
    }

    private function nameAliases(string $name): array
    {
        return str_contains($name, '1')
            ? ['Học kỳ 1', 'Học kì 1', 'HK1', 'Hoc ky 1']
            : ['Học kỳ 2', 'Học kì 2', 'HK2', 'Hoc ky 2'];

        return $name === 'Học kỳ 1'
            ? ['Học kỳ 1', 'Học kì 1', 'HK1', 'Hoc ky 1']
            : ['Học kỳ 2', 'Học kì 2', 'HK2', 'Hoc ky 2'];
    }

    private function termOrder(string $name): int
    {
        return str_contains($name, '1') ? 1 : 2;

        return $name === 'Học kỳ 1' ? 1 : 2;
    }

    private function deleteCheck(Semester $semester): array
    {
        if (! $semester->isDraft() && ! $semester->isInactive()) {
            return [
                'allowed' => false,
                'message' => 'Chỉ học kỳ Bản nháp hoặc Chưa hoạt động mới được xóa.',
            ];
        }

        if ($reason = $this->realBusinessDataBlockReason($semester)) {
            return [
                'allowed' => false,
                'message' => 'Không thể xóa học kỳ vì đã phát sinh dữ liệu nghiệp vụ: ' . $reason . '.',
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    private function realBusinessDataBlockReason(Semester $semester): ?string
    {
        $id = (string) $semester->getKey();

        $checks = [
            'Điểm số' => fn () => $this->modelHasRows(ScoreHeader::class, 'semester_id', $id),
            'Điểm danh' => fn () => $this->modelHasRows(AttendanceRecord::class, 'semester_id', $id),
            'Hạnh kiểm' => fn () => $this->modelHasRows(Conduct::class, 'semester_id', $id),
            'Lịch kiểm tra' => fn () => $this->modelHasRows(ExamSchedule::class, 'semester_id', $id),
            'Thời khóa biểu' => fn () => $this->modelHasRows(Timetable::class, 'semester_id', $id),
        ];

        foreach ($checks as $label => $exists) {
            if ($exists()) {
                return $label;
            }
        }

        return null;
    }

    private function modelHasRows(string $model, string $column, string $value): bool
    {
        $instance = new $model();

        return Schema::hasTable($instance->getTable())
            && Schema::hasColumn($instance->getTable(), $column)
            && $model::where($column, $value)->exists();
    }

    private function semesterLogs(Semester $semester)
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where('entity_type', Semester::class)
            ->where('entity_id', (string) $semester->getKey())
            ->latest('created_at')
            ->get();
    }

    private function archiveClassesForSemester(Semester $semester): void
    {
        SchoolClass::where('semester_id', $semester->getKey())
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', SchoolClass::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (SchoolClass $class) use ($semester) {
                $class->update([
                    'status' => SchoolClass::STATUS_ARCHIVED,
                    'archived_at' => $class->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'class_auto_archived_by_semester_archive',
                    SchoolClass::class,
                    (string) $class->getKey(),
                    'Tự động lưu trữ lớp ' . $class->name . ' khi lưu trữ học kỳ ' . $semester->name
                );
            });
    }

    private function archiveTimetableEntriesForSemester(Semester $semester): void
    {
        if (! Schema::hasTable('timetables') || ! Schema::hasTable('timetable_entries')) {
            return;
        }

        $timetableIds = Timetable::where('semester_id', $semester->getKey())->pluck('id');

        if ($timetableIds->isEmpty()) {
            return;
        }

        TimetableEntry::whereIn('timetable_id', $timetableIds)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TimetableEntry::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TimetableEntry $entry) use ($semester) {
                $entry->update([
                    'status' => TimetableEntry::STATUS_ARCHIVED,
                    'archived_at' => $entry->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'timetable_entry_auto_archived_by_semester_archive',
                    TimetableEntry::class,
                    (string) $entry->getKey(),
                    'Tự động lưu trữ tiết học khi lưu trữ học kỳ ' . $semester->name
                );
            });
    }

    private function archiveAssignmentsForSemester(Semester $semester): void
    {
        TeachingAssignment::where('semester_id', $semester->getKey())
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TeachingAssignment::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TeachingAssignment $assignment) use ($semester) {
                $assignment->update([
                    'status' => TeachingAssignment::STATUS_ARCHIVED,
                    'archived_at' => $assignment->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'teaching_assignment_auto_archived_by_semester_archive',
                    TeachingAssignment::class,
                    (string) $assignment->getKey(),
                    'Tự động lưu trữ phân công khi lưu trữ học kỳ ' . $semester->name
                );
            });
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi học kỳ.',
            ]);
        }
    }
}
