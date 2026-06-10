<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function index()
    {
        $assignments = TeachingAssignment::with(['teacher', 'classRoom', 'subject', 'schoolYear'])
            ->orderBy('school_year_id')
            ->orderBy('class_id')
            ->get();
        $teachers = Teacher::all();
        $classes = SchoolClass::all();
        $subjects = Subject::all();
        $years = SchoolYear::all();
        return view('assignments.index', compact('assignments', 'teachers', 'classes', 'subjects', 'years'));
    }

    public function create()
    {
        $teachers = Teacher::all();
        $classes = SchoolClass::all();
        $subjects = Subject::all();
        $years = SchoolYear::all();
        return view('assignments.create', compact('teachers', 'classes', 'subjects', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        TeachingAssignment::create($data);
        return redirect()->route('assignments.index')->with('success', 'Đã phân công giảng dạy');
    }

    public function edit(TeachingAssignment $assignment)
    {
        $teachers = Teacher::all();
        $classes = SchoolClass::all();
        $subjects = Subject::all();
        $years = SchoolYear::all();
        return view('assignments.edit', compact('assignment', 'teachers', 'classes', 'subjects', 'years'));
    }

    public function update(Request $request, TeachingAssignment $assignment)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        $assignment->update($data);
        return redirect()->route('assignments.index')->with('success', 'Đã cập nhật phân công');
    }

    public function destroy(TeachingAssignment $assignment)
    {
        $assignment->delete();
        return redirect()->route('assignments.index')->with('success', 'Đã xóa phân công');
    }
}
