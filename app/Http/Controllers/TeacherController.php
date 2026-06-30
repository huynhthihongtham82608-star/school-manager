<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminProtectionService;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $teachers = Teacher::with('user')
            ->when($selectedYearId, function ($query) use ($selectedYearId) {
                $query->whereHas('assignments', fn ($assignmentQuery) => $assignmentQuery->where('school_year_id', $selectedYearId))
                    ->orWhereHas('homeroomClasses', fn ($classQuery) => $classQuery->where('school_year_id', $selectedYearId));
            })
            ->orderBy('name')
            ->get();

        return view('teachers.index', compact('teachers', 'selectedYearId'));
    }

    public function create()
    {
        return view('teachers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_code' => 'required|string|unique:teachers,teacher_code',
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'qualification' => 'nullable|string',
            'main_subject' => 'nullable|string',
            'is_homeroom' => 'boolean',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        $teacherData = $request->only([
            'teacher_code', 'name', 'phone', 'email', 'qualification', 'main_subject',
        ]);
        $teacherData['is_homeroom'] = $request->boolean('is_homeroom');

        $teacher = Teacher::create($teacherData);

        User::create([
            'username' => $data['username'],
            'role' => $teacher->is_homeroom ? 'homeroom' : 'teacher',
            'teacher_id' => $teacher->id,
            'password_hash' => Hash::make($data['password']),
            'is_active' => 1,
        ]);

        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên');
    }

    public function edit(Teacher $teacher)
    {
        return view('teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'teacher_code' => 'required|string|unique:teachers,teacher_code,' . $teacher->id,
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'qualification' => 'nullable|string',
            'main_subject' => 'nullable|string',
            'is_homeroom' => 'boolean',
            'password' => 'nullable|string|min:6',
        ]);

        $teacherData = $request->only([
            'teacher_code', 'name', 'phone', 'email', 'qualification', 'main_subject',
        ]);
        $teacherData['is_homeroom'] = $request->boolean('is_homeroom');

        $teacher->update($teacherData);

        if ($teacher->user) {
            $update = [
                'role' => $teacher->is_homeroom ? 'homeroom' : 'teacher',
            ];

            // Check if this is an admin account
            if ($teacher->user->role === 'admin') {
                $validation = AdminProtectionService::validateAdminChange($teacher->user, $update);
                if (!$validation['allowed']) {
                    return back()->withErrors(['error' => $validation['message']]);
                }
            }

            if (!empty($data['password'])) {
                $update['password_hash'] = Hash::make($data['password']);
            }
            $teacher->user->update($update);
        }

        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật giáo viên');
    }

    public function destroy(Teacher $teacher)
    {
        // Check if teacher's user is admin
        if ($teacher->user && $teacher->user->role === 'admin') {
            $validation = AdminProtectionService::validateAdminDeletion($teacher->user);
            if (!$validation['allowed']) {
                return back()->withErrors(['error' => $validation['message']]);
            }
        }

        if ($teacher->user) {
            $teacher->user->delete();
        }

        $teacher->delete();
        return redirect()->route('teachers.index')->with('success', 'Đã xóa giáo viên');
    }
}
