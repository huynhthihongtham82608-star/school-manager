<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $viewStudent = null;
        $studentConductRecords = collect();

        if ($user->isStudent() && $user->student) {
            $viewStudent = $user->student->load('classRoom');
        } elseif ($user->isParent() && $user->parentProfile) {
            $children = $user->parentProfile->students()->with('classRoom')->orderBy('student_code')->get();
            $viewStudent = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
        }

        if ($viewStudent) {
            $studentConductRecords = Conduct::with(['classRoom', 'semester.schoolYear'])
                ->where('student_id', $viewStudent->id)
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->latest()
                ->get();

            return view('conduct.index', [
                'classes' => collect(),
                'semesters' => Semester::with('schoolYear')->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get(),
                'selectedClass' => null,
                'selectedSemester' => null,
                'students' => collect(),
                'records' => collect(),
                'selectedYearId' => $selectedYearId,
                'viewStudent' => $viewStudent,
                'studentConductRecords' => $studentConductRecords,
            ]);
        }

        $classes = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->get();

        if ($user->isHomeroom()) {
            $teacherId = optional($user->teacher)->id;
            $classes = $classes->where('homeroom_teacher_id', $teacherId);
        }

        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->get();
        $selectedClass = null;
        $selectedSemester = $selectedSemesterId ? $semesters->firstWhere('id', $selectedSemesterId) : null;
        $students = collect();
        $records = collect();

        if ($request->filled('class_id') && $selectedSemesterId) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($selectedSemesterId);
            $this->authorizeHomeroom($selectedClass);

            $students = Student::where('class_id', $selectedClass->id)->orderBy('student_code')->get();
            $records = Conduct::where('class_id', $selectedClass->id)
                ->where('semester_id', $selectedSemester->id)
                ->get()
                ->keyBy('student_id');
        }

        return view('conduct.index', compact('classes', 'semesters', 'selectedClass', 'selectedSemester', 'students', 'records', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
            'conduct' => 'array',
            'conduct.*.conduct_level' => ['nullable', Rule::in(array_keys(Conduct::LEVELS))],
            'conduct.*.comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $this->authorizeHomeroom($class);

        if (! $semester->isActive()) {
            abort(403, 'Học kỳ không ở trạng thái Hoạt động nên không thể nhập hoặc chỉnh sửa hạnh kiểm.');
        }

        if ($this->isHistoricalReadOnly()) {
            abort(403, 'Đang xem dữ liệu năm học cũ, chỉ được xem hạnh kiểm.');
        }

        $students = Student::where('class_id', $class->id)->get();
        $errors = [];

        foreach ($students as $student) {
            $entry = $request->input("conduct.{$student->id}", []);
            if (empty($entry['conduct_level'])) {
                continue;
            }

            if (trim((string) ($entry['comment'] ?? '')) === '') {
                $errors["conduct.{$student->id}.comment"] = 'Vui lòng nhập nhận xét khi đã chọn xếp loại hạnh kiểm.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($students, $request, $class, $semester) {
            foreach ($students as $student) {
                $entry = $request->input("conduct.{$student->id}", []);
                if (empty($entry['conduct_level'])) {
                    continue;
                }

                Conduct::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'school_year_id' => $semester->school_year_id,
                        'class_id' => $class->id,
                    ],
                    [
                        'conduct_level' => $entry['conduct_level'],
                        'comment' => trim((string) $entry['comment']),
                    ]
                );
            }
        });

        return back()->with('success', 'Đã lưu hạnh kiểm');
    }

    protected function authorizeHomeroom(SchoolClass $class): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            return;
        }

        abort(403, 'Chỉ GVCN hoặc admin được nhập hạnh kiểm');
    }
}
