<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\ScoreSetting;
use App\Models\Subject;
use App\Models\SubjectPeriodNorm;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    private const GRADE_LEVELS = [10, 11, 12];

    public function index(Request $request)
    {
        $selectedStatus = $request->query('status', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $subjects = Subject::with(['periodNorms', 'gradeMappings', 'departments'])
            ->when($selectedStatus !== 'all', function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        return view('subjects.index', [
            'subjects' => $subjects,
            'selectedStatus' => $selectedStatus,
            'readOnly' => $readOnly,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('subjects.index')->withErrors([
                'subject' => 'Đang xem dữ liệu lịch sử, không thể thêm môn học.',
            ]);
        }

        return view('subjects.create', [
            'gradeLevels' => self::GRADE_LEVELS,
            'nextCode' => Subject::nextCode(),
        ]);
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();

        [$data, $periodNorms, $gradeLevels] = $this->validatedPayload($request);

        DB::transaction(function () use ($data, $periodNorms, $gradeLevels) {
            $subject = Subject::create($data);
            $this->syncGradeMappings($subject, $gradeLevels);
            $this->syncPeriodNorms($subject, $periodNorms);
            $this->ensureDefaultScoreColumnsForMappings($subject, $gradeLevels);

            AuditLogger::log('subject_created', Subject::class, (string) $subject->getKey(), 'Tạo môn học ' . $subject->name);
        });

        return redirect()->route('subjects.index')->with('success', 'Đã thêm môn học.');
    }

    public function edit(Subject $subject)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('subjects.index')->withErrors([
                'subject' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa môn học.',
            ]);
        }

        $subject->load(['periodNorms', 'gradeMappings', 'departments']);

        return view('subjects.edit', [
            'subject' => $subject,
            'isUsed' => $subject->isUsed(),
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $this->denyHistoricalWrite();

        [$data, $periodNorms, $gradeLevels] = $this->validatedPayload($request, $subject);
        $oldStatus = $subject->status;

        if ($subject->isUsed() && $data['code'] !== $subject->code) {
            throw ValidationException::withMessages([
                'code' => 'Môn học đã được sử dụng, không thể sửa mã môn.',
            ]);
        }

        DB::transaction(function () use ($subject, $data, $periodNorms, $gradeLevels, $oldStatus) {
            $subject->update($data);
            $this->syncGradeMappings($subject, $gradeLevels);
            $this->syncPeriodNorms($subject, $periodNorms);
            $this->ensureDefaultScoreColumnsForMappings($subject, $gradeLevels);
            $this->detachDepartmentsWhenNotOfficial($subject);

            AuditLogger::log('subject_updated', Subject::class, (string) $subject->getKey(), 'Sửa môn học ' . $subject->name);

            if ($oldStatus !== $subject->status) {
                AuditLogger::log('subject_status_changed', Subject::class, (string) $subject->getKey(), 'Đổi trạng thái môn học ' . $subject->name . ' sang ' . $subject->statusLabel());
            }
        });

        return redirect()->route('subjects.index')->with('success', 'Đã cập nhật môn học.');
    }

    public function destroy(Subject $subject)
    {
        $this->denyHistoricalWrite();

        if (! $subject->canDelete()) {
            return back()->withErrors([
                'subject' => 'Không thể xóa môn học vì đang được gán cho giáo viên hoặc đã phát sinh phân công, thời khóa biểu, điểm số.',
            ]);
        }

        $subject->load(['periodNorms', 'gradeMappings']);
        $subjectName = $subject->name;
        $subjectId = (string) $subject->getKey();

        DB::transaction(function () use ($subject, $subjectName, $subjectId) {
            foreach ($subject->periodNorms as $periodNorm) {
                AuditLogger::log(
                    'subject_period_norm_deleted',
                    SubjectPeriodNorm::class,
                    (string) $periodNorm->getKey(),
                    'Xóa định mức tiết khối ' . $periodNorm->grade_level . ' của môn ' . $subjectName
                );
            }

            $subject->periodNorms()->delete();
            $subject->gradeMappings()->delete();
            $subject->delete();

            AuditLogger::log('subject_deleted', Subject::class, $subjectId, 'Xóa môn học ' . $subjectName);
        });

        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học.');
    }

    private function validatedPayload(Request $request, ?Subject $subject = null): array
    {
        $request->merge([
            'code' => $subject?->code ?: Subject::nextCode(),
            'name' => trim((string) $request->input('name')),
            'assessment_type' => Subject::normalizeAssessmentType($request->input('assessment_type', $subject?->assessment_type ?: Subject::ASSESSMENT_GRADE_10)),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^MH\d{3,}$/',
                Rule::unique('subjects', 'code')->ignore($subject?->getKey()),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'name')->ignore($subject?->getKey()),
            ],
            'credit' => ['required', 'integer', 'min:1', 'max:10'],
            'type' => ['required', Rule::in(array_keys(Subject::TYPES))],
            'assessment_type' => ['required', Rule::in(array_keys(Subject::ASSESSMENT_TYPES))],
            'status' => ['required', Rule::in(array_keys(Subject::STATUSES))],
            'period_norms' => ['nullable', 'array'],
            'period_norms.10' => ['nullable', 'integer', 'min:1', 'max:10'],
            'period_norms.11' => ['nullable', 'integer', 'min:1', 'max:10'],
            'period_norms.12' => ['nullable', 'integer', 'min:1', 'max:10'],
            'applicable_grade_levels' => ['required', 'array', 'min:1'],
            'applicable_grade_levels.*' => ['required', 'integer', Rule::in(self::GRADE_LEVELS)],
        ], [
            'code.unique' => 'Mã môn đã tồn tại.',
            'code.regex' => 'Mã môn phải theo định dạng MH001, MH002...',
            'name.unique' => 'Tên môn đã tồn tại.',
            'type.in' => 'Loại môn không hợp lệ.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'period_norms.*.integer' => 'Định mức tiết phải là số nguyên.',
            'period_norms.*.min' => 'Định mức tiết tối thiểu là 1.',
            'period_norms.*.max' => 'Định mức tiết tối đa là 10.',
            'applicable_grade_levels.required' => 'Vui lòng chọn ít nhất một khối học áp dụng.',
            'applicable_grade_levels.min' => 'Vui lòng chọn ít nhất một khối học áp dụng.',
        ]);

        $gradeLevels = collect($validated['applicable_grade_levels'])
            ->map(fn ($gradeLevel) => (int) $gradeLevel)
            ->filter(fn (int $gradeLevel) => in_array($gradeLevel, self::GRADE_LEVELS, true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $periodNorms = [];
        foreach (self::GRADE_LEVELS as $gradeLevel) {
            $value = $validated['period_norms'][$gradeLevel] ?? null;
            $periodNorms[$gradeLevel] = in_array($gradeLevel, $gradeLevels, true) && $value !== null && $value !== ''
                ? (int) $value
                : null;
        }

        unset($validated['period_norms'], $validated['applicable_grade_levels']);

        return [$validated, $periodNorms, $gradeLevels];
    }

    private function syncGradeMappings(Subject $subject, array $gradeLevels): void
    {
        $existing = $subject->gradeMappings()
            ->pluck('grade_level')
            ->map(fn ($gradeLevel) => (int) $gradeLevel)
            ->sort()
            ->values()
            ->all();
        $desired = collect($gradeLevels)
            ->map(fn ($gradeLevel) => (int) $gradeLevel)
            ->filter(fn (int $gradeLevel) => in_array($gradeLevel, self::GRADE_LEVELS, true))
            ->unique()
            ->sort()
            ->values();

        $subject->gradeMappings()
            ->whereNotIn('grade_level', $desired->all())
            ->delete();

        foreach ($desired as $gradeLevel) {
            $subject->gradeMappings()->firstOrCreate(['grade_level' => $gradeLevel]);
        }

        if ($existing !== $desired->all()) {
            AuditLogger::log(
                'subject_grade_mapping_synced',
                Subject::class,
                (string) $subject->getKey(),
                'Cập nhật khối học áp dụng cho môn ' . $subject->name . ': Khối ' . $desired->implode(', Khối ')
            );
        }
    }

    private function syncPeriodNorms(Subject $subject, array $periodNorms): void
    {
        if (! $subject->requiresTeachingAssignment()) {
            $subject->periodNorms()->delete();

            return;
        }

        foreach (self::GRADE_LEVELS as $gradeLevel) {
            $value = $periodNorms[$gradeLevel] ?? null;
            $existing = $subject->periodNorms()->where('grade_level', $gradeLevel)->first();

            if ($value === null) {
                if ($existing) {
                    $normId = (string) $existing->getKey();
                    $existing->delete();

                    AuditLogger::log(
                        'subject_period_norm_deleted',
                        SubjectPeriodNorm::class,
                        $normId,
                        'Xóa định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name
                    );
                }

                continue;
            }

            if ($existing) {
                if ((int) $existing->periods_per_week !== $value) {
                    $existing->update(['periods_per_week' => $value]);

                    AuditLogger::log(
                        'subject_period_norm_updated',
                        SubjectPeriodNorm::class,
                        (string) $existing->getKey(),
                        'Sửa định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name . ' thành ' . $value . ' tiết/tuần'
                    );
                }

                continue;
            }

            $created = $subject->periodNorms()->create([
                'grade_level' => $gradeLevel,
                'periods_per_week' => $value,
            ]);

            AuditLogger::log(
                'subject_period_norm_created',
                SubjectPeriodNorm::class,
                (string) $created->getKey(),
                'Tạo định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name . ': ' . $value . ' tiết/tuần'
            );
        }
    }

    private function ensureDefaultScoreColumnsForMappings(Subject $subject, array $gradeLevels): void
    {
        if (! $subject->isEvaluated()) {
            return;
        }

        $setting = ScoreSetting::current();
        $years = SchoolYear::query()->pluck('id');
        $defaults = [
            ['name' => 'Kiểm tra Miệng', 'type' => ScoreColumn::TYPE_REGULAR, 'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_REGULAR), 'sort_order' => 10],
            ['name' => 'Kiểm tra 15 phút', 'type' => ScoreColumn::TYPE_REGULAR, 'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_REGULAR), 'sort_order' => 20],
            ['name' => 'Kiểm tra Giữa kỳ', 'type' => ScoreColumn::TYPE_MIDTERM, 'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_MIDTERM), 'sort_order' => 40],
            ['name' => 'Kiểm tra Cuối kỳ', 'type' => ScoreColumn::TYPE_FINAL, 'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_FINAL), 'sort_order' => 50],
        ];

        foreach ($years as $yearId) {
            foreach ($gradeLevels as $gradeLevel) {
                foreach ($defaults as $default) {
                    ScoreColumn::firstOrCreate(
                        [
                            'school_year_id' => $yearId,
                            'subject_id' => $subject->id,
                            'grade_level' => (int) $gradeLevel,
                            'name' => $default['name'],
                        ],
                        [
                            'type' => $default['type'],
                            'weight_group' => $default['weight_group'],
                            'sort_order' => $default['sort_order'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }

    private function detachDepartmentsWhenNotOfficial(Subject $subject): void
    {
        if ($subject->isScorable()) {
            return;
        }

        if ($subject->departments()->exists()) {
            $subject->departments()->detach();

            AuditLogger::log(
                'subject_department_detached',
                Subject::class,
                (string) $subject->getKey(),
                'Gỡ tổ chuyên môn khỏi môn ' . $subject->name . ' vì môn không thuộc loại Chính khóa'
            );
        }
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi môn học.',
            ]);
        }
    }
}
