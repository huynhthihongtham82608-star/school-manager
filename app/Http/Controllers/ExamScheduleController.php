<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ExamScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Schema::hasTable('exam_schedules') ? ExamSchedule::with(['classRoom', 'subject', 'semester']) : null;

        if ($query && $user->isStudent() && $user->student) {
            $query->where('class_id', $user->student->class_id);
        }

        if ($query && $user->isParent() && $user->parentProfile) {
            $classIds = $user->parentProfile->students()->pluck('students.class_id')->filter()->unique();
            $query->whereIn('class_id', $classIds);
        }

        $schedules = $query ? $query->orderBy('exam_date')->paginate(12) : collect();
        $classes = Schema::hasTable('classes') ? SchoolClass::orderBy('name')->get() : collect();
        $subjects = Schema::hasTable('subjects') ? Subject::orderBy('name')->get() : collect();
        $semesters = Schema::hasTable('semesters') ? Semester::orderByDesc('start_date')->get() : collect();

        return view('exam_schedules.index', compact('schedules', 'classes', 'subjects', 'semesters'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'class_id' => ['nullable', 'string', 'max:50'],
            'subject_id' => ['nullable', 'string', 'max:50'],
            'semester_id' => ['nullable', 'string', 'max:50'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        $schedule = ExamSchedule::create($data);

        AuditLogger::log('exam_schedule_created', ExamSchedule::class, $schedule->id, 'Tạo lịch thi');

        return back()->with('success', 'Đã thêm lịch thi.');
    }
}
