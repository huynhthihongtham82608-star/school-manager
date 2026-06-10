<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $classes = SchoolClass::orderBy('name')->get();
        $semesters = Semester::with('schoolYear')->orderBy('order')->get();

        $selectedClass = null;
        $selectedSemester = null;
        $timetable = null;
        $entries = collect();

        if ($request->filled('class_id') && $request->filled('semester_id')) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);
            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($request->input('semester_id'));

            $timetable = Timetable::where('class_id', $selectedClass->id)
                ->where('semester_id', $selectedSemester->id)
                ->first();
            if ($timetable) {
                $entries = TimetableEntry::where('timetable_id', $timetable->id)
                    ->with(['subject', 'teacher'])
                    ->get()
                    ->keyBy(fn ($e) => $e->day_of_week . '-' . $e->period);
            }
        } elseif ($user->isStudent() && $user->student) {
            $selectedClass = $user->student->classRoom;
        } elseif ($user->isParent() && $user->parentProfile) {
            $child = $user->parentProfile->students()->with('classRoom')->first();
            $selectedClass = $child?->classRoom;
        } elseif ($user->isTeacher() && $user->teacher) {
            // Teacher view: show personal schedule via view
            return $this->teacherView();
        }

        return view('timetables.index', compact(
            'classes',
            'semesters',
            'selectedClass',
            'selectedSemester',
            'timetable',
            'entries'
        ));
    }

    public function manage(Request $request)
    {
        $years = SchoolYear::orderByDesc('start_date')->get();
        $classes = SchoolClass::orderBy('name')->get();
        $semesters = Semester::with('schoolYear')->orderBy('order')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();

        $selectedClass = null;
        $selectedSemester = null;
        $timetable = null;
        $entries = collect();

        if ($request->filled('class_id') && $request->filled('semester_id')) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);
            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($request->input('semester_id'));

            $timetable = Timetable::firstOrCreate([
                'class_id' => $selectedClass->id,
                'semester_id' => $selectedSemester->id,
            ], [
                'school_year_id' => $selectedSemester->school_year_id,
            ]);

            $entries = TimetableEntry::where('timetable_id', $timetable->id)
                ->get()
                ->keyBy(fn ($e) => $e->day_of_week . '-' . $e->period);
        }

        return view('timetables.manage', compact(
            'years',
            'classes',
            'semesters',
            'subjects',
            'teachers',
            'selectedClass',
            'selectedSemester',
            'timetable',
            'entries'
        ));
    }

    public function saveEntries(Request $request)
    {
        $data = $request->validate([
            'timetable_id' => 'required|exists:timetables,id',
            'entries' => 'array',
        ]);

        $timetable = Timetable::findOrFail($data['timetable_id']);

        $days = [1, 2, 3, 4, 5, 6]; // Mon..Sat
        $periods = [1, 2, 3, 4, 5];

        foreach ($days as $d) {
            foreach ($periods as $p) {
                $slot = $request->input("entries.$d.$p", []);
                $subjectId = $slot['subject_id'] ?? null;
                $teacherId = $slot['teacher_id'] ?? null;
                $room = $slot['room'] ?? null;
                $note = $slot['note'] ?? null;

                $existing = TimetableEntry::where('timetable_id', $timetable->id)
                    ->where('day_of_week', $d)
                    ->where('period', $p)
                    ->first();

                if (!$subjectId || !$teacherId) {
                    if ($existing) {
                        $existing->delete();
                    }
                    continue;
                }

                TimetableEntry::updateOrCreate(
                    [
                        'timetable_id' => $timetable->id,
                        'day_of_week' => $d,
                        'period' => $p,
                    ],
                    [
                        'subject_id' => $subjectId,
                        'teacher_id' => $teacherId,
                        'room' => $room,
                        'note' => $note,
                    ]
                );
            }
        }

        return back()->with('success', 'Đã lưu thời khóa biểu');
    }

    protected function teacherView()
    {
        $teacherId = optional(Auth::user()->teacher)->id;
        if (!$teacherId) {
            abort(403);
        }

        $entries = TimetableEntry::with(['timetable.classRoom', 'subject'])
            ->where('teacher_id', $teacherId)
            ->get()
            ->sortBy(fn ($e) => sprintf('%d-%02d', (int) $e->day_of_week, (int) $e->period))
            ->values();

        return view('timetables.teacher', compact('entries'));
    }
}
