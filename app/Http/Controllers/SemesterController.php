<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::with('schoolYear')->orderBy('school_year_id')->orderBy('order')->get();
        $years = SchoolYear::all();
        return view('semesters.index', compact('semesters', 'years'));
    }

    public function create()
    {
        $years = SchoolYear::all();
        return view('semesters.create', compact('years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'order' => 'required|integer|min:1|max:4',
            'school_year_id' => 'required|exists:school_years,id',
            'is_score_input_open' => 'boolean',
        ]);

        $data['is_score_input_open'] = $request->boolean('is_score_input_open');
        Semester::create($data);

        return redirect()->route('semesters.index')->with('success', 'Đã tạo học kỳ');
    }

    public function edit(Semester $semester)
    {
        $years = SchoolYear::all();
        return view('semesters.edit', compact('semester', 'years'));
    }

    public function update(Request $request, Semester $semester)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'order' => 'required|integer|min:1|max:4',
            'school_year_id' => 'required|exists:school_years,id',
            'is_score_input_open' => 'boolean',
        ]);

        $data['is_score_input_open'] = $request->boolean('is_score_input_open');
        $semester->update($data);

        return redirect()->route('semesters.index')->with('success', 'Đã cập nhật học kỳ');
    }

    public function destroy(Semester $semester)
    {
        $semester->delete();
        return redirect()->route('semesters.index')->with('success', 'Đã xóa học kỳ');
    }
}
