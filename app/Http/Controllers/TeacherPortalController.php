<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\ScoreHeader;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeacherPortalController extends Controller
{
    public function classes(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $isHomeroomScope = $request->query('scope') === 'homeroom' || $request->routeIs('teacher.homeroom');

        if ($isHomeroomScope) {
            $homeroomClass = SchoolClass::with([
                'schoolYear',
                'semester',
                'students' => fn ($q) => $q->with(['parents', 'classRoom'])->orderBy('student_code'),
            ])
                ->where('homeroom_teacher_id', $teacher->id)
                ->first();

            return view('teachers.homeroom-profile', compact('homeroomClass'));
        }

        $assignedClassIds = $teacher->assignments()
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('class_id')
            ->unique();

        $classes = SchoolClass::with([
            'schoolYear',
            'semester',
            'students' => fn ($query) => $query->orderBy('student_code')->orderBy('name'),
        ])
            ->whereIn('id', $assignedClassIds)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return view('teachers.classes', [
            'classes' => $classes,
            'isHomeroomScope' => false,
        ]);
    }

    public function classStudents(Request $request, SchoolClass $class)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $canView = $class->homeroom_teacher_id === $teacher->id
            || $teacher->assignments()
                ->where('class_id', $class->id)
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->exists();

        abort_unless($canView, 403);

        $class->load(['schoolYear', 'semester', 'students' => fn ($query) => $query->orderBy('student_code')]);

        return view('teachers.class-students', compact('class'));
    }

    public function homeroomScores(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $homeroomClass = SchoolClass::with(['schoolYear', 'semester', 'students' => fn ($q) => $q->where('status', 'studying')->orderBy('student_code')])
            ->where('homeroom_teacher_id', $teacher->id)
            ->first();

        if (! $homeroomClass) {
            return view('teachers.homeroom-scores', [
                'homeroomClass' => null,
                'adminMatrix' => null,
                'adminMatrixJson' => '{}',
            ]);
        }

        $request->merge([
            'class_id' => $homeroomClass->id,
            'school_year_id' => $homeroomClass->school_year_id,
            'semester_id' => $request->query('semester_id') ?: $homeroomClass->semester_id,
        ]);

        $scoreController = new ScoreController();
        $adminMatrix = $scoreController->adminScoreMatrixPayload($request);
        $adminMatrixJson = json_encode($adminMatrix, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return view('teachers.homeroom-scores', compact('homeroomClass', 'adminMatrix', 'adminMatrixJson'));
    }

    public function departmentOverview(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $department = $teacher->leadingDepartment()
            ->with(['subjects', 'teachers.primarySubject'])
            ->first();

        abort_unless($department, 403);

        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $teacherIds = $department->teachers->pluck('id');

        $assignments = TeachingAssignment::with(['teacher', 'classRoom', 'subject'])
            ->whereIn('teacher_id', $teacherIds)
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();

        $scoreAssignments = $assignments->filter(fn (TeachingAssignment $assignment) => $assignment->subject?->isEvaluated());
        $scoreProgress = $scoreAssignments->groupBy('teacher_id')->map(function ($teacherAssignments) use ($selectedYearId, $selectedSemesterId) {
            $completed = 0;

            foreach ($teacherAssignments as $assignment) {
                $hasScore = Schema::hasTable('score_headers')
                    && ScoreHeader::where('subject_id', $assignment->subject_id)
                        ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                        ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                        ->whereHas('student', fn ($student) => $student->where('class_id', $assignment->class_id))
                        ->exists();

                if ($hasScore) {
                    $completed++;
                }
            }

            return [
                'total' => $teacherAssignments->count(),
                'completed' => $completed,
                'missing' => max(0, $teacherAssignments->count() - $completed),
            ];
        });

        return view('teachers.department', compact('department', 'assignments', 'scoreProgress'));
    }
}
