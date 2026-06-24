<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedClassId = $request->query('class_id');
        $date = $request->query('date', now()->toDateString());

        $classes = Schema::hasTable('classes') ? SchoolClass::with('students')->orderBy('name')->get() : collect();
        $students = collect();

        if ($selectedClassId && Schema::hasTable('students')) {
            $class = $classes->firstWhere('id', $selectedClassId);
            $students = $class ? $class->students->sortBy('name')->values() : collect();
        }

        $recordsQuery = Schema::hasTable('attendance_records')
            ? AttendanceRecord::with(['student', 'classRoom', 'semester'])->latest('attendance_date')->latest()
            : null;

        if ($recordsQuery && $user->isStudent() && $user->student) {
            $recordsQuery->where('student_id', $user->student->id);
        } elseif ($recordsQuery && $user->isParent() && $user->parentProfile) {
            $studentIds = $user->parentProfile->students()->pluck('students.id');
            $recordsQuery->whereIn('student_id', $studentIds);
        } elseif ($recordsQuery && $selectedClassId) {
            $recordsQuery->where('class_id', $selectedClassId);
        }

        $records = $recordsQuery ? $recordsQuery->paginate(15) : collect();
        $semesters = Schema::hasTable('semesters') ? Semester::orderByDesc('start_date')->get() : collect();

        return view('attendance.index', compact('classes', 'students', 'records', 'semesters', 'selectedClassId', 'date'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isHomeroom(), 403);

        if (! Schema::hasTable('attendance_records')) {
            return back()->with('error', 'Chưa có bảng attendance_records. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'class_id' => ['required', 'string', 'max:50'],
            'semester_id' => ['nullable', 'string', 'max:50'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'in:present,absent,late,excused'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['status'] as $studentId => $status) {
            AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'attendance_date' => $data['attendance_date'],
                ],
                [
                    'class_id' => $data['class_id'],
                    'semester_id' => $data['semester_id'] ?? null,
                    'status' => $status,
                    'note' => $data['note'][$studentId] ?? null,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        AuditLogger::log('attendance_updated', AttendanceRecord::class, null, 'Cập nhật điểm danh lớp');

        return back()->with('success', 'Đã lưu điểm danh.');
    }
}
