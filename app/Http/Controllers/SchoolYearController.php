<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        $years = SchoolYear::orderByDesc('start_date')->get();
        return view('school_years.index', compact('years'));
    }

    public function create()
    {
        return view('school_years.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:school_years,name',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        SchoolYear::create($data);

        return redirect()->route('school-years.index')->with('success', 'Đã tạo năm học');
    }

    public function edit(SchoolYear $schoolYear)
    {
        return view('school_years.edit', compact('schoolYear'));
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:school_years,name,' . $schoolYear->id,
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $schoolYear->update($data);

        return redirect()->route('school-years.index')->with('success', 'Đã cập nhật năm học');
    }

    public function destroy(SchoolYear $schoolYear)
    {
        $schoolYear->delete();
        return redirect()->route('school-years.index')->with('success', 'Đã xóa năm học');
    }
}
