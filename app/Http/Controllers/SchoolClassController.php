<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $classes = SchoolClass::with(['schoolYear', 'homeroomTeacher', 'students'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();
        $teachers = Teacher::all();
        $years = SchoolYear::all();
        return view('classes.index', compact('classes', 'teachers', 'years', 'selectedYearId'));
    }

    public function create()
    {
        $teachers = Teacher::all();
        $years = SchoolYear::all();
        return view('classes.create', compact('teachers', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:classes,name',
            'grade_level' => 'required|integer|in:10,11,12',
            'school_year_id' => 'required|exists:school_years,id',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
            'capacity' => 'nullable|integer|min:1',
        ]);

        SchoolClass::create($data);
        return redirect()->route('classes.index')->with('success', 'Đã tạo lớp học');
    }

    public function edit(SchoolClass $class)
    {
        $teachers = Teacher::all();
        $years = SchoolYear::all();
        return view('classes.edit', ['class' => $class, 'teachers' => $teachers, 'years' => $years]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:classes,name,' . $class->id,
            'grade_level' => 'required|integer|in:10,11,12',
            'school_year_id' => 'required|exists:school_years,id',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $class->update($data);
        return redirect()->route('classes.index')->with('success', 'Đã cập nhật lớp học');
    }

    public function destroy(SchoolClass $class)
    {
        $class->delete();
        return redirect()->route('classes.index')->with('success', 'Đã xóa lớp học');
    }
}
