<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AdminProtectionService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $teachers = Teacher::with([
            'user',
            'primarySubject',
            'assignments.classRoom',
            'assignments.subject',
            'assignments.schoolYear',
            'assignments.semester',
            'homeroomClasses.schoolYear',
        ])
            ->orderBy('name')
            ->get();

        return view('teachers.index', compact('teachers', 'selectedYearId'));
    }

    public function create()
    {
        return view('teachers.create', [
            'subjects' => Subject::where('status', Subject::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $teacherData = $this->teacherPayload($data, $request);

        $teacher = Teacher::create($teacherData);

        User::create([
            'username' => $teacher->teacher_code,
            'role' => 'teacher',
            'teacher_id' => $teacher->id,
            'password_hash' => Hash::make('12345678'),
            'force_change_password' => true,
            'is_active' => $teacher->isWorking() ? 1 : 0,
        ]);

        AuditLogger::log('teacher_created', Teacher::class, (string) $teacher->getKey(), 'Tạo giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên');
    }

    public function edit(Teacher $teacher)
    {
        return view('teachers.edit', [
            'teacher' => $teacher->load('primarySubject'),
            'subjects' => Subject::where('status', Subject::STATUS_ACTIVE)
                ->orWhere('id', $teacher->primary_subject_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validatedData($request, $teacher);
        $teacherData = $this->teacherPayload($data, $request);

        $teacher->update($teacherData);

        if ($teacher->user) {
            $update = [
                'username' => $teacher->teacher_code,
                'role' => 'teacher',
                'is_active' => $teacher->isWorking() ? 1 : 0,
            ];

            if ($teacher->user->role === 'admin') {
                $validation = AdminProtectionService::validateAdminChange($teacher->user, $update);
                if (! $validation['allowed']) {
                    return back()->withErrors(['error' => $validation['message']]);
                }
            }

            $teacher->user->update($update);
        } else {
            User::create([
                'username' => $teacher->teacher_code,
                'role' => 'teacher',
                'teacher_id' => $teacher->id,
                'password_hash' => Hash::make('12345678'),
                'force_change_password' => true,
                'is_active' => $teacher->isWorking() ? 1 : 0,
            ]);
        }

        AuditLogger::log('teacher_updated', Teacher::class, (string) $teacher->getKey(), 'Cập nhật giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật giáo viên');
    }

    public function resetPassword(Teacher $teacher)
    {
        $user = $teacher->user ?: User::create([
            'username' => $teacher->teacher_code,
            'role' => 'teacher',
            'teacher_id' => $teacher->id,
            'password_hash' => Hash::make('12345678'),
            'is_active' => $teacher->isWorking() ? 1 : 0,
        ]);

        $user->update([
            'password_hash' => Hash::make('12345678'),
            'force_change_password' => true,
            'is_active' => $teacher->isWorking() ? 1 : 0,
        ]);

        AuditLogger::log(
            'teacher_password_reset',
            Teacher::class,
            (string) $teacher->getKey(),
            'Đặt lại mật khẩu giáo viên ' . $teacher->name . ' bởi ' . (auth()->user()?->display_name ?? auth()->user()?->username ?? 'admin') . ' lúc ' . now()->format('d/m/Y H:i:s')
        );

        return back()->with('success', 'Đã đặt lại mật khẩu giáo viên về 12345678.');
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->user && $teacher->user->role === 'admin') {
            $validation = AdminProtectionService::validateAdminDeletion($teacher->user);
            if (! $validation['allowed']) {
                return back()->withErrors(['error' => $validation['message']]);
            }
        }

        DB::transaction(function () use ($teacher) {
            $teacher->user?->delete();
            $teacher->delete();
        });

        AuditLogger::log('teacher_deleted', Teacher::class, (string) $teacher->getKey(), 'Xóa giáo viên ' . $teacher->name);

        return redirect()->route('teachers.index')->with('success', 'Đã xóa giáo viên');
    }

    private function validatedData(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'teacher_code' => [
                'required',
                'string',
                Rule::unique('teachers', 'teacher_code')->ignore($teacher?->id),
                Rule::unique('users', 'username')->ignore($teacher?->user?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(array_keys(Teacher::genderLabels()))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'joined_at' => ['nullable', 'date'],
            'work_status' => ['required', Rule::in(array_keys(Teacher::workStatuses()))],
            'qualification' => ['nullable', 'string', 'max:255'],
            'primary_subject_id' => ['required', 'exists:subjects,id'],
        ]);
    }

    private function teacherPayload(array $data, Request $request): array
    {
        $subject = ! empty($data['primary_subject_id'])
            ? Subject::find($data['primary_subject_id'])
            : null;

        return [
            'teacher_code' => $data['teacher_code'],
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
            'main_subject' => $subject?->name,
        ];
    }
}
