<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\ExamSchedule;
use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'classes' => SchoolClass::count(),
            'subjects' => Subject::count(),
            'assignments' => TeachingAssignment::count(),
            'announcements' => Schema::hasTable('school_posts') ? SchoolPost::where('type', SchoolPost::TYPE_ANNOUNCEMENT)->count() : 0,
            'events' => Schema::hasTable('school_events') ? SchoolEvent::count() : 0,
            'documents' => Schema::hasTable('learning_documents') ? LearningDocument::count() : 0,
            'attendance' => Schema::hasTable('attendance_records') ? AttendanceRecord::count() : 0,
        ];

        $selectedYearId = $this->selectedSchoolYearId(request());
        $selectedSemesterId = $this->selectedSemesterId(request());
        $activeYear = SchoolYear::find($selectedYearId);
        $adminOverview = $this->adminOverviewData($activeYear?->getKey());

        $teacherAssignments = collect();
        $teacherDashboard = null;
        $homeroomClass = null;
        $studentScores = collect();
        $conduct = null;
        $parentChildren = collect();
        $selectedParentStudent = null;
        $parentScores = collect();
        $parentConduct = collect();

        if ($user->isTeacher()) {
            $teacher = $user->teacher;
            if ($teacher) {
                $teacherAssignments = $teacher->assignments()->with(['classRoom', 'subject', 'schoolYear'])->get();
                $homeroomClass = SchoolClass::where('homeroom_teacher_id', $teacher->id)->with('students')->first();
                $teacherDashboard = $this->teacherDashboardData($user);
            }
        } elseif ($user->isStudent()) {
            $student = $user->student;
            if ($student) {
                $studentScores = ScoreHeader::where('student_id', $student->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->with(['subject', 'semester'])
                    ->get();
                $conduct = Conduct::where('student_id', $student->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->with(['classRoom', 'semester'])
                    ->get();
            }
        } elseif ($user->isParent() && $user->parentProfile) {
            $parentChildren = $user->parentProfile->students()->with('classRoom')->orderBy('student_code')->get();
            $selectedParentStudent = $this->selectedParentStudent($parentChildren);

            if ($selectedParentStudent) {
                $parentScores = ScoreHeader::where('student_id', $selectedParentStudent->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->with(['subject', 'semester'])
                    ->get();
                $parentConduct = Conduct::where('student_id', $selectedParentStudent->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->with(['classRoom', 'semester'])
                    ->get();
            }
        }

        return view('dashboard', compact(
            'user',
            'stats',
            'adminOverview',
            'activeYear',
            'teacherAssignments',
            'teacherDashboard',
            'homeroomClass',
            'studentScores',
            'conduct',
            'parentChildren',
            'selectedParentStudent',
            'parentScores',
            'parentConduct'
        ));
    }

    public function selectParentChild(Request $request)
    {
        $user = Auth::user();

        if (! $user?->isParent() || ! $user->parentProfile) {
            abort(403);
        }

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $allowed = $user->parentProfile->students()
            ->where('students.id', $data['student_id'])
            ->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'student_id' => 'Học sinh không thuộc tài khoản phụ huynh này.',
            ]);
        }

        session(['selected_parent_student_id' => $data['student_id']]);

        return back();
    }

    private function selectedParentStudent($children)
    {
        if ($children->isEmpty()) {
            return null;
        }

        $selectedId = session('selected_parent_student_id');

        return $children->firstWhere('id', $selectedId) ?: $children->first();
    }

    private function teacherDashboardData($user): array
    {
        $teacher = $user->teacher;
        $todayWeekday = now()->isoWeekday();
        $selectedYearId = $this->selectedSchoolYearId(request());
        $selectedSemesterId = $this->selectedSemesterId(request());

        $assignments = $teacher->assignments()
            ->with(['classRoom', 'subject', 'semester.schoolYear'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();

        $todayEntries = collect();
        if ($todayWeekday >= 1 && $todayWeekday <= 6) {
            $todayEntries = TimetableEntry::with(['timetable.classRoom', 'assignment.subject', 'roomInfo'])
                ->where('teacher_id', $teacher->id)
                ->where('day_of_week', $todayWeekday)
                ->where('status', TimetableEntry::STATUS_ACTIVE)
                ->orderBy('period')
                ->get();
        }

        $announcements = Schema::hasTable('school_posts')
            ? SchoolPost::where('type', SchoolPost::TYPE_ANNOUNCEMENT)
                ->where('is_published', true)
                ->latest('published_at')
                ->latest()
                ->get()
                ->filter(fn (SchoolPost $post) => $post->isVisibleToUser($user))
                ->take(5)
                ->values()
            : collect();

        $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
        $homeroomClassIds = $teacher->homeroomClasses()
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->pluck('id');
        $assignedPairs = $assignments->map(fn (TeachingAssignment $assignment) => [
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
        ]);
        $visibleClassIds = $classIds->merge($homeroomClassIds)->unique()->values();

        $upcomingExams = Schema::hasTable('exam_schedules')
            ? ExamSchedule::with(['classRoom', 'subject'])
                ->whereIn('class_id', $visibleClassIds)
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where(function ($query) use ($homeroomClassIds, $assignedPairs) {
                    $hasCondition = false;

                    if ($homeroomClassIds->isNotEmpty()) {
                        $query->whereIn('class_id', $homeroomClassIds);
                        $hasCondition = true;
                    }

                    foreach ($assignedPairs as $pair) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $query->{$method}(function ($query) use ($pair) {
                            $query->where('class_id', $pair['class_id'])
                                ->where('subject_id', $pair['subject_id']);
                        });
                        $hasCondition = true;
                    }

                    if (! $hasCondition) {
                        $query->whereRaw('1 = 0');
                    }
                })
                ->whereDate('exam_date', '>=', now()->toDateString())
                ->orderBy('exam_date')
                ->orderBy('start_time')
                ->get()
                ->filter(fn (ExamSchedule $schedule) => $schedule->isPublished())
                ->take(5)
                ->values()
            : collect();

        return [
            'class_count' => $visibleClassIds->count(),
            'today_period_count' => $todayEntries->count(),
            'today_entries' => $todayEntries,
            'announcements' => $announcements,
            'upcoming_exams' => $upcomingExams,
        ];
    }

    private function adminOverviewData(?string $schoolYearId = null): array
    {
        $today = now()->toDateString();

        $classesWithStudentCount = SchoolClass::withCount('students')
            ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->get();
        $studentsByGrade = collect([10, 11, 12])->map(function (int $grade) use ($classesWithStudentCount) {
            $count = $classesWithStudentCount
                ->filter(fn (SchoolClass $class) => $this->classGrade($class) === $grade)
                ->sum('students_count');

            return [
                'label' => 'Khối ' . $grade,
                'value' => $count,
            ];
        });

        $attendanceByStatus = collect(AttendanceRecord::STATUSES)->map(function (string $label, string $status) {
            $count = Schema::hasTable('attendance_records')
                ? AttendanceRecord::where('status', $status)
                    ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
                    ->count()
                : 0;

            return compact('label', 'count');
        })->values();

        $scoreLevels = $this->scoreLevelStats($schoolYearId);

        $quickInfo = [
            [
                'label' => 'Thông báo đã công bố',
                'icon' => 'bi-megaphone',
                'value' => Schema::hasTable('school_posts')
                    ? SchoolPost::where('type', SchoolPost::TYPE_ANNOUNCEMENT)->where('is_published', true)->count()
                    : 0,
            ],
            [
                'label' => 'Sự kiện sắp diễn ra',
                'icon' => 'bi-calendar-event',
                'value' => Schema::hasTable('school_events')
                    ? SchoolEvent::where('is_published', true)->where('starts_at', '>=', now())->count()
                    : 0,
            ],
            [
                'label' => 'Lịch kiểm tra sắp diễn ra',
                'icon' => 'bi-calendar2-check',
                'value' => Schema::hasTable('exam_schedules')
                    ? ExamSchedule::all()
                        ->filter(fn (ExamSchedule $schedule) => $schedule->isPublished()
                            && (! $schoolYearId || str_contains((string) $schedule->note, '"school_year_id":"' . $schoolYearId . '"'))
                            && $this->examScheduleStartsAt($schedule)?->isFuture())
                        ->count()
                    : 0,
            ],
            [
                'label' => 'Tài liệu học tập',
                'icon' => 'bi-journal-bookmark',
                'value' => Schema::hasTable('learning_documents') ? LearningDocument::count() : 0,
            ],
        ];

        $attendedClassIds = Schema::hasTable('attendance_records')
            ? AttendanceRecord::whereDate('attendance_date', $today)
                ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
                ->distinct()
                ->pluck('class_id')
                ->filter()
            : collect();
        $classesWithoutAttendance = SchoolClass::whereNotIn('id', $attendedClassIds)
            ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $draftExamSchedules = Schema::hasTable('exam_schedules')
            ? ExamSchedule::all()
                ->filter(fn (ExamSchedule $schedule) => $schedule->isDraft()
                    && (! $schoolYearId || str_contains((string) $schedule->note, '"school_year_id":"' . $schoolYearId . '"')))
                ->count()
            : 0;

        $draftAnnouncements = Schema::hasTable('school_posts')
            ? SchoolPost::where('type', SchoolPost::TYPE_ANNOUNCEMENT)->where('is_published', false)->count()
            : 0;

        $draftEvents = Schema::hasTable('school_events')
            ? SchoolEvent::where('is_published', false)->count()
            : 0;

        $tasks = [
            [
                'title' => 'Hôm nay còn ' . $classesWithoutAttendance->count() . ' lớp chưa điểm danh',
                'icon' => 'bi-exclamation-triangle',
                'count' => $classesWithoutAttendance->count(),
                'detail' => $classesWithoutAttendance->take(4)->pluck('name')->implode(', '),
                'empty' => 'Tất cả lớp đã có dữ liệu điểm danh hôm nay.',
            ],
            [
                'title' => 'Có ' . $draftExamSchedules . ' lịch kiểm tra chưa công bố',
                'icon' => 'bi-calendar2-check',
                'count' => $draftExamSchedules,
                'detail' => $draftExamSchedules > 0 ? 'Cần rà soát và công bố lịch kiểm tra phù hợp.' : null,
                'empty' => 'Không có lịch kiểm tra ở trạng thái bản nháp.',
            ],
            [
                'title' => 'Có ' . $draftAnnouncements . ' thông báo đang ở trạng thái Bản nháp',
                'icon' => 'bi-megaphone',
                'count' => $draftAnnouncements,
                'detail' => $draftAnnouncements > 0 ? 'Có thông báo đang chờ công bố.' : null,
                'empty' => 'Không có thông báo bản nháp.',
            ],
            [
                'title' => 'Có ' . $draftEvents . ' sự kiện đang ở trạng thái Bản nháp',
                'icon' => 'bi-calendar-event',
                'count' => $draftEvents,
                'detail' => $draftEvents > 0 ? 'Có sự kiện đang chờ công bố.' : null,
                'empty' => 'Không có sự kiện bản nháp.',
            ],
        ];

        return compact('studentsByGrade', 'attendanceByStatus', 'scoreLevels', 'quickInfo', 'tasks');
    }

    private function scoreLevelStats(?string $schoolYearId = null)
    {
        $levels = collect([
            'Giỏi' => 0,
            'Khá' => 0,
            'Trung bình' => 0,
            'Yếu' => 0,
        ]);

        ScoreHeader::whereNotNull('average')
            ->when($schoolYearId ?? null, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->pluck('average')
            ->each(function ($average) use ($levels) {
            $average = (float) $average;

            if ($average >= 8) {
                $levels['Giỏi']++;
            } elseif ($average >= 6.5) {
                $levels['Khá']++;
            } elseif ($average >= 5) {
                $levels['Trung bình']++;
            } else {
                $levels['Yếu']++;
            }
        });

        return $levels->map(fn ($count, $label) => compact('label', 'count'))->values();
    }

    private function classGrade(SchoolClass $class): ?int
    {
        if (in_array((int) $class->grade_level, [10, 11, 12], true)) {
            return (int) $class->grade_level;
        }

        if (preg_match('/^(10|11|12)/', (string) $class->name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function examScheduleStartsAt(ExamSchedule $schedule): ?Carbon
    {
        if (! $schedule->exam_date) {
            return null;
        }

        $time = $schedule->start_time ? substr((string) $schedule->start_time, 0, 5) : '00:00';

        return Carbon::parse($schedule->exam_date->format('Y-m-d') . ' ' . $time);
    }
}
