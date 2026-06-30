<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminProtectionService;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $students = Student::with(['classRoom', 'schoolYear', 'user'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('student_code')
            ->get();
        $classes = SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get();
        $years = SchoolYear::all();

        return view('students.index', compact('students', 'classes', 'years', 'selectedYearId'));
    }

    public function create()
    {
        $classes = SchoolClass::all();
        $years = SchoolYear::all();
        return view('students.create', compact('classes', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_code' => 'required|string|unique:students,student_code',
            'name' => 'required|string',
            'gender' => 'nullable|string',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_phone' => 'nullable|string',
            'email' => 'nullable|email',
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'status' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        $student = Student::create($request->only([
            'student_code',
            'name',
            'gender',
            'dob',
            'address',
            'parent_phone',
            'email',
            'class_id',
            'school_year_id',
            'status',
        ]));

        User::create([
            'username' => $data['username'],
            'role' => 'student',
            'student_id' => $student->id,
            'password_hash' => Hash::make($data['password']),
            'is_active' => 1,
        ]);

        return redirect()->route('students.index')->with('success', 'Đã thêm học sinh');
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::all();
        $years = SchoolYear::all();
        return view('students.edit', compact('student', 'classes', 'years'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'student_code' => 'required|string|unique:students,student_code,' . $student->id,
            'name' => 'required|string',
            'gender' => 'nullable|string',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_phone' => 'nullable|string',
            'email' => 'nullable|email',
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'status' => 'required|string',
            'password' => 'nullable|string|min:6',
        ]);

        $student->update($request->only([
            'student_code',
            'name',
            'gender',
            'dob',
            'address',
            'parent_phone',
            'email',
            'class_id',
            'school_year_id',
            'status',
        ]));

        if ($student->user && !empty($data['password'])) {
            $student->user->update([
                'password_hash' => Hash::make($data['password']),
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Đã cập nhật học sinh');
    }

    public function destroy(Student $student)
    {
        // Check if student's user is admin
        if ($student->user && $student->user->role === 'admin') {
            $validation = AdminProtectionService::validateAdminDeletion($student->user);
            if (!$validation['allowed']) {
                return back()->withErrors(['error' => $validation['message']]);
            }
        }

        if ($student->user) {
            $student->user->delete();
        }

        $student->delete();
        return redirect()->route('students.index')->with('success', 'Đã xóa học sinh');
    }
}
