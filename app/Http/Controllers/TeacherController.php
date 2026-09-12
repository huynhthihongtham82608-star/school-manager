<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherDepartment;
use App\Models\User;
use App\Rules\BusinessText;
use App\Rules\PhoneNumber;
use App\Services\AdminProtectionService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $filters = [
            'q' => trim((string) $request->query('q')),
            'department_id' => $request->query('department_id', 'all'),
        ];
        $teachers = Teacher::with([
            'user',
            'primarySubject',
            'department',
            'assignments.classRoom',
            'assignments.subject',
            'assignments.schoolYear',
            'assignments.semester',
            'homeroomClasses.schoolYear',
        ])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('teacher_code', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhereHas('primarySubject', fn ($subject) => $subject->where('name', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($filters['department_id'] !== 'all', fn ($query) => $query->where('department_id', $filters['department_id']))
            ->orderBy('name')
            ->get();
        $departments = TeacherDepartment::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $nextTeacherCode = $this->nextTeacherCode();

        return view('teachers.index', compact('teachers', 'selectedYearId', 'departments', 'subjects', 'filters', 'nextTeacherCode'));
    }

    public function create()
    {
        return view('teachers.create', [
            'subjects' => Subject::where('status', Subject::STATUS_ACTIVE)->orderBy('name')->get(),
            'departments' => TeacherDepartment::where('status', TeacherDepartment::STATUS_ACTIVE)->orderBy('name')->get(),
            'nextTeacherCode' => $this->nextTeacherCode(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $teacherData = $this->teacherPayload($data, $request);

        $teacher = Teacher::create($teacherData);
        $this->syncTeacherLoginUser($teacher, true);

        AuditLogger::log('teacher_created', Teacher::class, (string) $teacher->getKey(), 'Tạo giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên');
    }

    public function edit(Teacher $teacher)
    {
        return view('teachers.edit', [
            'teacher' => $teacher->load(['primarySubject', 'department']),
            'subjects' => Subject::where('status', Subject::STATUS_ACTIVE)
                ->orWhere('id', $teacher->primary_subject_id)
                ->orderBy('name')
                ->get(),
            'departments' => TeacherDepartment::where('status', TeacherDepartment::STATUS_ACTIVE)
                ->orWhere('id', $teacher->department_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validatedData($request, $teacher);
        $teacherData = $this->teacherPayload($data, $request);

        if ($teacher->leadingDepartment && (string) $teacher->department_id !== (string) ($teacherData['department_id'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'Giáo viên này đang là tổ trưởng. Vui lòng chọn tổ trưởng mới trước khi chuyển giáo viên sang tổ khác.']);
        }

        $teacher->update($teacherData);

        $this->syncTeacherLoginUser($teacher);

        AuditLogger::log('teacher_updated', Teacher::class, (string) $teacher->getKey(), 'Cập nhật giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật giáo viên');
    }

    public function resetPassword(Teacher $teacher)
    {
        $user = $this->syncTeacherLoginUser($teacher, true);

        AuditLogger::log(
            'teacher_password_reset',
            Teacher::class,
            (string) $teacher->getKey(),
            'Đặt lại mật khẩu giáo viên ' . $teacher->name . ' bởi ' . (auth()->user()?->display_name ?? auth()->user()?->username ?? 'admin') . ' lúc ' . now()->format('d/m/Y H:i:s')
        );

        return back()->with('success', 'Đã đặt lại mật khẩu giáo viên về 12345678.');
    }

    public function toggleLogin(Teacher $teacher)
    {
        $user = $this->syncTeacherLoginUser($teacher);

        $user->update(['login_status' => ! (bool) ($user->login_status ?? true)]);

        AuditLogger::log(
            'teacher_login_status_changed',
            Teacher::class,
            (string) $teacher->getKey(),
            (($user->login_status ?? true) ? 'Mở khóa' : 'Khóa') . ' đăng nhập giáo viên ' . $teacher->name
        );

        return back()->with('success', ($user->login_status ?? true) ? 'Đã mở khóa tài khoản đăng nhập giáo viên.' : 'Đã khóa tài khoản đăng nhập giáo viên.');
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->user && $teacher->user->role === 'admin') {
            $validation = AdminProtectionService::validateAdminDeletion($teacher->user);
            if (! $validation['allowed']) {
                return back()->withErrors(['error' => $validation['message']]);
            }
        }

        $dependencies = $this->teacherDependencyLabels($teacher);
        if ($dependencies !== []) {
            return back()->withErrors([
                'teacher' => 'Không thể xóa giáo viên vì đã phát sinh dữ liệu: ' . implode(', ', $dependencies) . '. Vui lòng khóa đăng nhập hoặc chuyển trạng thái nghỉ việc để giữ nguyên lịch sử.',
            ]);
        }

        DB::transaction(function () use ($teacher) {
            if ($teacher->user && (string) $teacher->user->getKey() !== (string) $teacher->getKey()) {
                $teacher->user->delete();
            }
            $teacher->delete();
        });

        AuditLogger::log('teacher_deleted', Teacher::class, (string) $teacher->getKey(), 'Xóa giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã xóa giáo viên');
    }

    private function validatedData(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'teacher_code' => [
                $teacher ? 'required' : 'nullable',
                'string',
                Rule::unique($this->teacherTable(), 'teacher_code')->ignore($teacher?->getKey()),
                Rule::unique('users', 'username')->ignore($teacher?->user?->id),
            ],
            'name' => ['required', 'string', 'max:255', new BusinessText('Ho ten giao vien')],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(array_keys(Teacher::genderLabels()))],
            'phone' => ['nullable', 'string', 'min:8', 'max:20', new PhoneNumber()],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher?->getKey())],
            'address' => ['nullable', 'string', 'max:255', new BusinessText('Dia chi')],
            'joined_at' => ['nullable', 'date'],
            'work_status' => ['required', Rule::in(array_keys(Teacher::workStatuses()))],
            'qualification' => ['nullable', 'string', 'max:255', new BusinessText('Trinh do')],
            'primary_subject_id' => ['required', 'exists:subjects,id'],
            'department_id' => ['nullable', 'exists:teacher_departments,id'],
        ]);
    }

    private function teacherPayload(array $data, Request $request): array
    {
        $subject = ! empty($data['primary_subject_id'])
            ? Subject::find($data['primary_subject_id'])
            : null;

        return [
            'teacher_code' => $data['teacher_code'] ?? $this->nextTeacherCode(),
            'name' => $data['name'],
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'work_status' => $data['work_status'] ?? Teacher::STATUS_WORKING,
            'qualification' => $data['qualification'] ?? null,
            'primary_subject_id' => $data['primary_subject_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'main_subject' => $subject?->name,
        ];
    }

    private function nextTeacherCode(): string
    {
        $maxNumber = Teacher::query()
            ->where('teacher_code', 'like', 'GV%')
            ->pluck('teacher_code')
            ->map(function ($code) {
                return preg_match('/^GV(\d+)$/', (string) $code, $matches) ? (int) $matches[1] : 0;
            })
            ->max() ?? 0;

        return 'GV' . str_pad((string) ($maxNumber + 1), 3, '0', STR_PAD_LEFT);
    }

    private function teacherTable(): string
    {
        return (new Teacher())->getTable();
    }

    private function syncTeacherLoginUser(Teacher $teacher, bool $resetPassword = false): User
    {
        $payload = [
            'username' => $teacher->teacher_code,
            'full_name' => $teacher->name,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'role' => 'teacher',
            'role_type' => 'teacher',
            'teacher_id' => $teacher->id,
            'source_profile_id' => $teacher->source_profile_id ?: $teacher->id,
            'teacher_code' => $teacher->teacher_code,
            'is_active' => $teacher->isWorking() ? 1 : 0,
        ];

        if ($resetPassword) {
            $payload['password_hash'] = Hash::make('12345678');
            $payload['force_change_password'] = true;
            $payload['login_status'] = 1;
        }

        if ($this->teacherTable() === 'users') {
            $user = User::whereKey($teacher->getKey())->firstOrFail();
            $user->forceFill($payload)->save();

            return $user;
        }

        $user = $teacher->user ?: new User();
        if (! $user->exists) {
            $payload['password_hash'] = $payload['password_hash'] ?? Hash::make('12345678');
            $payload['force_change_password'] = true;
            $payload['login_status'] = 1;
        }

        $user->forceFill($payload)->save();

        return $user;
    }

    private function teacherDependencyLabels(Teacher $teacher): array
    {
        $teacherId = (string) $teacher->getKey();
        $dependencies = [];

        if ($this->tableHasRows('teaching_assignments', 'teacher_id', $teacherId)) {
            $dependencies[] = 'phân công giảng dạy';
        }

        if ($this->tableHasRows('classes', 'homeroom_teacher_id', $teacherId)) {
            $dependencies[] = 'lớp chủ nhiệm';
        }

        if ($this->tableHasRows('timetable_entries', 'teacher_id', $teacherId)) {
            $dependencies[] = 'thời khóa biểu';
        }

        if ($this->tableHasRows('substitute_teachings', 'original_teacher_id', $teacherId)
            || $this->tableHasRows('substitute_teachings', 'substitute_teacher_id', $teacherId)) {
            $dependencies[] = 'lịch dạy thay';
        }

        if ($this->tableHasRows('substitute_teachings', 'created_by', $teacherId)
            || $this->tableHasRows('substitute_teachings', 'updated_by', $teacherId)) {
            $dependencies[] = 'lịch sử cập nhật dạy thay';
        }

        if ($this->tableHasRows('teacher_departments', 'leader_teacher_id', $teacherId)) {
            $dependencies[] = 'tổ trưởng chuyên môn';
        }

        if ($this->tableHasRows('attendance_records', 'recorded_by', $teacherId)) {
            $dependencies[] = 'lịch sử điểm danh';
        }

        if ($this->tableHasRows('rewards', 'created_by', $teacherId)
            || $this->tableHasRows('rewards', 'updated_by', $teacherId)) {
            $dependencies[] = 'lịch sử khen thưởng';
        }

        if ($this->tableHasRows('tuition_fees', 'updated_by', $teacherId)) {
            $dependencies[] = 'lịch sử cập nhật học phí';
        }

        foreach (['exam_schedules', 'attendance_records', 'student_scores'] as $table) {
            if ($this->tableHasRows($table, 'teacher_id', $teacherId)) {
                $dependencies[] = match ($table) {
                    'exam_schedules' => 'lịch kiểm tra',
                    'attendance_records' => 'điểm danh',
                    default => 'điểm số',
                };
            }
        }

        return array_values(array_unique($dependencies));
    }

    private function tableHasRows(string $table, string $column, string $value): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, $column)
            && DB::table($table)->where($column, $value)->exists();
    }
}
