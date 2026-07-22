<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherDepartment;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherDepartmentController extends Controller
{
    private const DEPARTMENT_SUBJECT_TYPES = [
        Subject::TYPE_OFFICIAL,
        Subject::TYPE_REQUIRED,
        Subject::TYPE_ELECTIVE,
        Subject::TYPE_REMEDIAL,
    ];

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status', 'all'),
        ];
        $readOnly = $this->isHistoricalReadOnly();

        $departments = TeacherDepartment::with([
                'subjects' => fn ($query) => $query->orderBy('name'),
                'leader',
                'teachers.primarySubject',
            ])
            ->withCount(['subjects', 'teachers'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('code', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('subjects', fn ($subject) => $subject->where('name', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('leader', fn ($teacher) => $teacher->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('code')
            ->get();

        return view('departments.index', [
            'departments' => $departments,
            'filters' => $filters,
            'statusFilters' => ['all' => 'Tất cả'] + TeacherDepartment::STATUSES,
            'readOnly' => $readOnly,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('departments.index')->withErrors([
                'department' => 'Đang xem dữ liệu lịch sử, không thể thêm tổ chuyên môn.',
            ]);
        }

        return view('departments.create', [
            'subjects' => $this->availableSubjects(),
        ]);
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();

        $data = $this->validatedData($request);
        $subjectIds = $data['subject_ids'];
        unset($data['subject_ids']);

        $department = DB::transaction(function () use ($data, $subjectIds) {
            $department = TeacherDepartment::create($data);
            $department->subjects()->sync($subjectIds);
            $this->syncTeachersBySubjects($department, $subjectIds);

            return $department;
        });

        AuditLogger::log('teacher_department_created', TeacherDepartment::class, (string) $department->getKey(), 'Tạo tổ chuyên môn ' . $department->name);

        return redirect()->route('departments.index')->with('success', 'Đã thêm tổ chuyên môn.');
    }

    public function edit(TeacherDepartment $department)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('departments.index')->withErrors([
                'department' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa tổ chuyên môn.',
            ]);
        }

        $department->load(['subjects', 'leader']);

        return view('departments.edit', [
            'department' => $department,
            'subjects' => $this->availableSubjects($department),
            'teachers' => Teacher::where('department_id', $department->getKey())->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TeacherDepartment $department)
    {
        $this->denyHistoricalWrite();

        $data = $this->validatedData($request, $department);
        $subjectIds = $data['subject_ids'];
        unset($data['subject_ids']);

        $oldLeaderId = $department->leader_teacher_id;
        $oldStatus = $department->status;

        DB::transaction(function () use ($department, $data, $subjectIds) {
            $department->update($data);
            $department->subjects()->sync($subjectIds);
            $this->syncTeachersBySubjects($department, $subjectIds);
        });

        $department->refresh();

        AuditLogger::log('teacher_department_updated', TeacherDepartment::class, (string) $department->getKey(), 'Cập nhật tổ chuyên môn ' . $department->name);

        if ((string) $oldLeaderId !== (string) $department->leader_teacher_id) {
            AuditLogger::log('teacher_department_leader_changed', TeacherDepartment::class, (string) $department->getKey(), 'Đổi tổ trưởng tổ ' . $department->name);
        }

        if ($oldStatus !== $department->status) {
            AuditLogger::log('teacher_department_status_changed', TeacherDepartment::class, (string) $department->getKey(), 'Đổi trạng thái tổ ' . $department->name . ' sang ' . $department->statusLabel());
        }

        return redirect()->route('departments.index')->with('success', 'Đã cập nhật tổ chuyên môn.');
    }

    public function destroy(TeacherDepartment $department)
    {
        $this->denyHistoricalWrite();

        if ($department->teachers()->exists()) {
            return back()->withErrors([
                'department' => 'Không thể xóa tổ chuyên môn vì vẫn còn giáo viên thuộc tổ. Vui lòng chuyển giáo viên sang tổ khác trước.',
            ]);
        }

        $departmentName = $department->name;
        $departmentId = (string) $department->getKey();

        DB::transaction(function () use ($department) {
            $department->subjects()->detach();
            $department->delete();
        });

        AuditLogger::log('teacher_department_deleted', TeacherDepartment::class, $departmentId, 'Xóa tổ chuyên môn ' . $departmentName);

        return redirect()->route('departments.index')->with('success', 'Đã xóa tổ chuyên môn.');
    }

    private function validatedData(Request $request, ?TeacherDepartment $department = null): array
    {
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('teacher_departments', 'code')->ignore($department?->getKey()),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teacher_departments', 'name')->ignore($department?->getKey()),
            ],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'distinct', 'exists:subjects,id'],
            'leader_teacher_id' => ['nullable', 'exists:teachers,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(TeacherDepartment::STATUSES))],
        ], [
            'code.regex' => 'Mã tổ chỉ gồm chữ in hoa, số hoặc dấu gạch dưới.',
            'code.unique' => 'Mã tổ đã tồn tại.',
            'name.unique' => 'Tên tổ đã tồn tại.',
            'subject_ids.required' => 'Vui lòng chọn ít nhất một môn phụ trách.',
            'subject_ids.*.distinct' => 'Môn phụ trách bị chọn trùng.',
        ]);

        $invalidSubjectNames = Subject::whereIn('id', $validated['subject_ids'])
            ->whereNotIn('type', self::DEPARTMENT_SUBJECT_TYPES)
            ->pluck('name');

        if ($invalidSubjectNames->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subject_ids' => 'Các môn không thuộc loại Chính khóa nên không cần tổ chuyên môn: ' . $invalidSubjectNames->join(', ') . '.',
            ]);
        }

        $conflictSubjectNames = Subject::whereIn('id', $validated['subject_ids'])
            ->whereHas('departments', function ($query) use ($department) {
                $query->when($department, fn ($inner) => $inner->whereKeyNot($department->getKey()));
            })
            ->pluck('name');

        if ($conflictSubjectNames->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subject_ids' => 'Các môn đã có tổ phụ trách: ' . $conflictSubjectNames->join(', ') . '.',
            ]);
        }

        if ($department && ! empty($validated['leader_teacher_id'])) {
            $leaderBelongsToDepartment = Teacher::whereKey($validated['leader_teacher_id'])
                ->where('department_id', $department->getKey())
                ->exists();

            if (! $leaderBelongsToDepartment) {
                throw ValidationException::withMessages([
                    'leader_teacher_id' => 'Tổ trưởng phải là giáo viên thuộc chính tổ chuyên môn này.',
                ]);
            }
        }

        if (! $department) {
            $validated['leader_teacher_id'] = null;
        }

        return $validated;
    }

    private function availableSubjects(?TeacherDepartment $department = null)
    {
        return Subject::query()
            ->where('status', Subject::STATUS_ACTIVE)
            ->whereIn('type', self::DEPARTMENT_SUBJECT_TYPES)
            ->where(function ($query) use ($department) {
                $query->whereDoesntHave('departments')
                    ->when($department, fn ($inner) => $inner->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery->whereKey($department->getKey())));
            })
            ->orderBy('name')
            ->get();
    }

    private function syncTeachersBySubjects(TeacherDepartment $department, array $subjectIds): int
    {
        $protectedLeaderIds = TeacherDepartment::whereNotNull('leader_teacher_id')
            ->whereKeyNot($department->getKey())
            ->pluck('leader_teacher_id')
            ->filter()
            ->values();

        return Teacher::whereIn('primary_subject_id', $subjectIds)
            ->when($protectedLeaderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $protectedLeaderIds))
            ->where(function ($query) use ($department) {
                $query->whereNull('department_id')
                    ->orWhere('department_id', '!=', $department->getKey());
            })
            ->update([
                'department_id' => $department->getKey(),
                'updated_at' => now(),
            ]);
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi tổ chuyên môn.',
            ]);
        }
    }
}
