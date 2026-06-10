<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')->get();
        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:subjects,name',
            'credit' => 'nullable|integer|min:1',
            'is_weighted' => 'boolean',
        ]);

        $data['is_weighted'] = $request->boolean('is_weighted');
        Subject::create($data);
        return redirect()->route('subjects.index')->with('success', 'Đã thêm môn học');
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:subjects,name,' . $subject->id,
            'credit' => 'nullable|integer|min:1',
            'is_weighted' => 'boolean',
        ]);

        $data['is_weighted'] = $request->boolean('is_weighted');
        $subject->update($data);
        return redirect()->route('subjects.index')->with('success', 'Đã cập nhật môn học');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học');
    }
}
