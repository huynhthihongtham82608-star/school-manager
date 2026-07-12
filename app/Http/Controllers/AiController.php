<?php

namespace App\Http\Controllers;

use App\Models\AiAlert;
use App\Models\AiReport;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\AiAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiController extends Controller
{
    public function alerts(Request $request, AiAnalyzer $analyzer)
    {
        return $this->supportView($request, $analyzer, 'alerts');
    }

    public function reports(Request $request, AiAnalyzer $analyzer)
    {
        return $this->supportView($request, $analyzer, 'reports');
    }

    public function runForm(Request $request, AiAnalyzer $analyzer)
    {
        return $this->supportView($request, $analyzer, 'analysis');
    }

    public function run(Request $request, AiAnalyzer $analyzer)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);

        $user = Auth::user();
        if (! $user->isAdmin()) {
            if (! ($user->isHomeroom() && $user->teacher && $class->homeroom_teacher_id === $user->teacher->id)) {
                abort(403);
            }
        }

        $result = $analyzer->analyzeClass($data['class_id'], $data['semester_id']);

        return redirect()->route('ai.alerts')->with(
            'success',
            "Đã phân tích lớp {$result['class']->name}, học kỳ {$result['semester']->name}. Tạo {$result['reports']} nhận xét và {$result['alerts']} cảnh báo hỗ trợ."
        );
    }

    public function export(Request $request, AiAnalyzer $analyzer)
    {
        $request->validate([
            'scope' => 'required|in:student,class,school',
            'format' => 'required|in:excel,pdf',
            'school_year_id' => 'nullable|exists:school_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $analysis = $this->buildAnalysisResult($request, $analyzer, $request->string('scope')->toString());
        if (! $analysis) {
            return redirect()->route('ai.run.form', $request->query())->with('error', 'Vui lòng chọn đủ dữ liệu trước khi xuất báo cáo AI.');
        }

        if ($request->input('format') === 'pdf') {
            return view('ai.export_print', compact('analysis'));
        }

        $filename = 'bao-cao-ai-' . now()->format('Ymd-His') . '.csv';

        return new StreamedResponse(function () use ($analysis) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['Nhóm', 'Nội dung']);
            foreach ([
                'Điểm mạnh' => $analysis['strengths'] ?? [],
                'Điểm cần cải thiện' => $analysis['improvements'] ?? [],
                'Khuyến nghị' => $analysis['recommendations'] ?? [],
            ] as $group => $items) {
                foreach ($items as $item) {
                    fputcsv($handle, [$group, $item]);
                }
            }

            fputcsv($handle, ['Ghi chú', $analysis['note'] ?? '']);
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function supportView(Request $request, AiAnalyzer $analyzer, string $activeTab)
    {
        $user = Auth::user();

        $alerts = $activeTab === 'alerts' ? $this->alertsQuery($user)->get() : collect();
        $reports = $activeTab === 'reports' ? $this->reportsQuery($user)->get() : collect();
        $classes = collect();
        $semesters = collect();
        $students = collect();
        $schoolYears = collect();
        $analysisScope = $request->query('scope', 'student');
        if (! in_array($analysisScope, ['student', 'class', 'school'], true)) {
            $analysisScope = 'student';
        }
        $selectedSchoolYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $analysisResult = null;
        $inspirationMessage = $this->randomInspirationMessage();

        if ($user->isAdmin() || $user->isStaff() || $user->isHomeroom()) {
            $schoolYears = SchoolYear::orderByDesc('name')->get();
            $semesters = Semester::with('schoolYear')
                ->when($selectedSchoolYearId, fn ($query) => $query->where('school_year_id', $selectedSchoolYearId))
                ->orderBy('school_year_id')
                ->orderBy('name')
                ->get();

            if ($activeTab === 'analysis') {
                if ($analysisScope === 'student') {
                    $students = Student::with('classRoom')
                        ->when($selectedSchoolYearId, fn ($query) => $query->where('school_year_id', $selectedSchoolYearId))
                        ->orderBy('student_code')
                        ->get();
                } elseif ($analysisScope === 'class') {
                    $classes = SchoolClass::with('schoolYear')
                        ->when($selectedSchoolYearId, fn ($query) => $query->where('school_year_id', $selectedSchoolYearId))
                        ->orderBy('name')
                        ->get();
                }

                $analysisResult = $this->buildAnalysisResult($request, $analyzer, $analysisScope);
            }
        }

        return view('ai.index', compact(
            'activeTab',
            'alerts',
            'reports',
            'classes',
            'semesters',
            'students',
            'schoolYears',
            'analysisScope',
            'analysisResult',
            'inspirationMessage',
            'selectedSchoolYearId',
            'selectedSemesterId'
        ));
    }

    private function buildAnalysisResult(Request $request, AiAnalyzer $analyzer, string $scope): ?array
    {
        $schoolYearId = $request->input('school_year_id') ?: $this->selectedSchoolYearId($request);
        $semesterId = $request->input('semester_id') ?: $this->selectedSemesterId($request);

        return match ($scope) {
            'student' => $request->filled('student_id') && $semesterId
                ? $analyzer->analyzeStudent($request->input('student_id'), $semesterId)
                : null,
            'class' => $request->filled('class_id') && $semesterId
                ? $analyzer->analyzeClassSnapshot($request->input('class_id'), $semesterId)
                : null,
            'school' => $schoolYearId && $semesterId
                ? $analyzer->analyzeSchoolSnapshot($schoolYearId, $semesterId)
                : null,
            default => null,
        };
    }

    private function alertsQuery($user): Builder
    {
        $query = AiAlert::with(['student', 'classRoom', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin() || $user->isStaff()) {
            return $query;
        }

        if ($user->isTeacher() && $user->teacher) {
            return $query->where(function ($subQuery) use ($user) {
                $subQuery->where('teacher_id', $user->teacher->id)
                    ->orWhereIn('class_id', $user->teacher->homeroomClasses()->pluck('id'));
            });
        }

        if ($user->isStudent() && $user->student) {
            return $query->where('student_id', $user->student->id);
        }

        if ($user->isParent() && $user->parentProfile) {
            $studentIds = $this->selectedParentStudentIds($user);
            return $query->whereIn('student_id', $studentIds);
        }

        return $query->whereRaw('1=0');
    }

    private function reportsQuery($user): Builder
    {
        $query = AiReport::with(['student', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin() || $user->isStaff()) {
            return $query;
        }

        if ($user->isStudent() && $user->student) {
            return $query->where('student_id', $user->student->id);
        }

        if ($user->isParent() && $user->parentProfile) {
            $studentIds = $this->selectedParentStudentIds($user);
            return $query->whereIn('student_id', $studentIds);
        }

        return $query->whereRaw('1=0');
    }

    private function selectedParentStudentIds($user)
    {
        $students = $user->parentProfile->students()->orderBy('student_code')->get(['students.id']);

        if ($students->isEmpty()) {
            return collect();
        }

        $selectedId = session('selected_parent_student_id');
        $selected = $students->firstWhere('id', $selectedId) ?: $students->first();

        return collect([$selected->id]);
    }

    private function randomInspirationMessage(): ?string
    {
        $messages = SystemSetting::current()->activeAiEncouragements();

        if (empty($messages)) {
            return null;
        }

        return $messages[random_int(0, count($messages) - 1)];
    }
}
