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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

        $activeYear = SchoolYear::where('is_active', true)->first();
        $adminOverview = $this->adminOverviewData();

        $teacherAssignments = collect();
        $homeroomClass = null;
        $studentScores = collect();
        $conduct = null;

        if ($user->isTeacher()) {
            $teacher = $user->teacher;
            if ($teacher) {
                $teacherAssignments = $teacher->assignments()->with(['classRoom', 'subject', 'schoolYear'])->get();
                $homeroomClass = SchoolClass::where('homeroom_teacher_id', $teacher->id)->with('students')->first();
            }
        } elseif ($user->isStudent()) {
            $student = $user->student;
            if ($student) {
                $studentScores = ScoreHeader::where('student_id', $student->id)
                    ->with(['subject', 'semester'])
                    ->get();
                $conduct = Conduct::where('student_id', $student->id)->with(['classRoom', 'semester'])->get();
            }
        }

        return view('dashboard', compact(
            'user',
            'stats',
            'adminOverview',
            'activeYear',
            'teacherAssignments',
            'homeroomClass',
            'studentScores',
            'conduct'
        ));
    }

    private function adminOverviewData(): array
    {
        $today = now()->toDateString();

        $classesWithStudentCount = SchoolClass::withCount('students')->get();
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
                ? AttendanceRecord::where('status', $status)->count()
                : 0;

            return compact('label', 'count');
        })->values();

        $scoreLevels = $this->scoreLevelStats();

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
                'label' => 'Lịch thi sắp diễn ra',
                'icon' => 'bi-calendar2-check',
                'value' => Schema::hasTable('exam_schedules')
                    ? ExamSchedule::all()->filter(fn (ExamSchedule $schedule) => $schedule->isPublished() && $this->examScheduleStartsAt($schedule)?->isFuture())->count()
                    : 0,
            ],
            [
                'label' => 'Tài liệu học tập',
                'icon' => 'bi-journal-bookmark',
                'value' => Schema::hasTable('learning_documents') ? LearningDocument::count() : 0,
            ],
        ];

        $attendedClassIds = Schema::hasTable('attendance_records')
            ? AttendanceRecord::whereDate('attendance_date', $today)->distinct()->pluck('class_id')->filter()
            : collect();
        $classesWithoutAttendance = SchoolClass::whereNotIn('id', $attendedClassIds)->orderBy('name')->get(['id', 'name']);

        $draftExamSchedules = Schema::hasTable('exam_schedules')
            ? ExamSchedule::all()->filter(fn (ExamSchedule $schedule) => $schedule->isDraft())->count()
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
                'title' => 'Có ' . $draftExamSchedules . ' lịch thi chưa công bố',
                'icon' => 'bi-calendar2-check',
                'count' => $draftExamSchedules,
                'detail' => $draftExamSchedules > 0 ? 'Cần rà soát và công bố lịch thi phù hợp.' : null,
                'empty' => 'Không có lịch thi ở trạng thái bản nháp.',
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

    private function scoreLevelStats()
    {
        $levels = collect([
            'Giỏi' => 0,
            'Khá' => 0,
            'Trung bình' => 0,
            'Yếu' => 0,
        ]);

        ScoreHeader::whereNotNull('average')->pluck('average')->each(function ($average) use ($levels) {
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
