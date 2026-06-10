<?php

namespace App\Http\Controllers;

use App\Models\GradeWindow;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Http\Request;

class GradeWindowController extends Controller
{
    public function index()
    {
        $windows = GradeWindow::with(['classRoom', 'subject', 'semester', 'schoolYear'])->get();
        $classes = SchoolClass::all();
        $subjects = Subject::all();
        $semesters = Semester::all();
        $years = SchoolYear::all();
        return view('grade_windows.index', compact('windows', 'classes', 'subjects', 'semesters', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'school_year_id' => 'required|exists:school_years,id',
            'is_open' => 'required|boolean',
        ]);

        $data['is_open'] = $request->boolean('is_open');
        GradeWindow::updateOrCreate(
            [
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id'],
                'semester_id' => $data['semester_id'],
                'school_year_id' => $data['school_year_id'],
            ],
            ['is_open' => $data['is_open']]
        );

        return back()->with('success', 'Đã lưu cửa sổ nhập điểm');
    }

    public function update(Request $request, GradeWindow $gradeWindow)
    {
        $data = $request->validate([
            'is_open' => 'required|boolean',
        ]);

        $gradeWindow->update($data);
        return back()->with('success', 'Đã cập nhật trạng thái nhập điểm');
    }
}
