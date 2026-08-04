<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\ScoreSetting;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ScoreColumnController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $request->query('school_year_id', $this->selectedSchoolYearId($request));
        $selectedGrade = $request->query('grade_level', 'all');
        $selectedSubjectId = $request->query('subject_id', 'all');
        $keyword = trim((string) $request->query('q', ''));
        $scoreSetting = ScoreSetting::current();

        $years = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $subjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->when(in_array((string) $selectedGrade, ['10', '11', '12'], true), fn ($query) => $query->forGrade((int) $selectedGrade))
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();

        $columns = ScoreColumn::with(['schoolYear', 'subject.gradeMappings'])
            ->whereHas('subject', fn ($query) => $query->withEvaluatedAssessment())
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(in_array((string) $selectedGrade, ['10', '11', '12'], true), fn ($query) => $query->where('grade_level', $selectedGrade))
            ->when($selectedSubjectId !== 'all', fn ($query) => $query->where('subject_id', $selectedSubjectId))
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->orderBy('grade_level')
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (ScoreColumn $column) => $column->subject?->appliesToGrade((int) $column->grade_level))
            ->reject(fn (ScoreColumn $column) => $this->scoreColumnFamily($column) === 'one_period')
            ->values();

        return view('score_columns.index', compact(
            'years',
            'subjects',
            'columns',
            'selectedYearId',
            'selectedGrade',
            'selectedSubjectId',
            'keyword',
            'scoreSetting'
        ));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'weight_gdtx' => ['required', 'integer', 'min:1', 'max:20'],
            'weight_dggk' => ['required', 'integer', 'min:1', 'max:20'],
            'weight_dgck' => ['required', 'integer', 'min:1', 'max:20'],
        ], [], [
            'weight_gdtx' => 'trọng số ĐGTX',
            'weight_dggk' => 'trọng số giữa kỳ',
            'weight_dgck' => 'trọng số cuối kỳ',
        ]);

        $setting = DB::transaction(function () use ($data) {
            $setting = ScoreSetting::current();
            $setting->update($data);
            $this->recalculateScoreAverages($setting);

            return $setting->refresh();
        });

        return response()->json([
            'message' => 'Đã cập nhật cấu hình trọng số tính điểm toàn trường.',
            'settings' => [
                'weight_gdtx' => $setting->weight_gdtx,
                'weight_dggk' => $setting->weight_dggk,
                'weight_dgck' => $setting->weight_dgck,
                'formula' => $setting->formulaLabel(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            ScoreColumn::create($data);
        });

        return back()->with('success', 'Đã thêm cột điểm.');
    }

    public function update(Request $request, ScoreColumn $scoreColumn)
    {
        $data = $this->validatedData($request, $scoreColumn);

        DB::transaction(function () use ($scoreColumn, $data) {
            $scoreColumn->update($data);
        });

        return back()->with('success', 'Đã cập nhật cột điểm.');
    }

    public function updateMatrixCounts(Request $request)
    {
        $data = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'grade_level' => ['required', 'integer', Rule::in([10, 11, 12])],
            'oral_count' => ['required', 'integer', 'min:0', 'max:10'],
            'fifteen_count' => ['required', 'integer', 'min:0', 'max:10'],
        ], [], [
            'school_year_id' => 'năm học',
            'subject_id' => 'môn học',
            'grade_level' => 'khối',
            'oral_count' => 'số lượng cột kiểm tra Miệng',
            'fifteen_count' => 'số lượng cột 15 phút',
        ]);

        $subject = Subject::findOrFail($data['subject_id']);
        if (! $subject->isEvaluated()) {
            abort(422, 'Môn học Không đánh giá không được cấu hình cột điểm.');
        }
        if (! $subject->appliesToGrade((int) $data['grade_level'])) {
            abort(422, 'Môn học này không được cấu hình áp dụng cho khối đã chọn.');
        }

        $result = DB::transaction(function () use ($data) {
            $oral = $this->syncScoreColumnFamily($data, 'oral', (int) $data['oral_count']);
            $fifteen = $this->syncScoreColumnFamily($data, 'fifteen', (int) $data['fifteen_count']);
            $onePeriod = $this->syncScoreColumnFamily($data, 'one_period', 0);
            $midterm = $this->syncFixedExamColumn($data, ScoreColumn::TYPE_MIDTERM);
            $final = $this->syncFixedExamColumn($data, ScoreColumn::TYPE_FINAL);
            $affectedHeaderIds = collect($fifteen['affected_header_ids'])
                ->merge($oral['affected_header_ids'])
                ->merge($onePeriod['affected_header_ids'])
                ->merge($midterm['affected_header_ids'])
                ->merge($final['affected_header_ids'])
                ->unique()
                ->values();

            $this->recalculateHeadersByIds($affectedHeaderIds);

            return [
                'oral_count' => $oral['count'],
                'fifteen_count' => $fifteen['count'],
                'midterm_count' => 1,
                'final_count' => 1,
                'affected_header_count' => $affectedHeaderIds->count(),
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã lưu cấu hình số lượng cột điểm cho môn học.',
                'counts' => $result,
                'state' => $this->matrixStatePayload($data),
            ]);
        }

        return back()->with('success', 'Đã lưu cấu hình số lượng cột điểm cho môn học.');
    }

    public function toggleLock(Request $request, ScoreColumn $scoreColumn)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($scoreColumn, $data) {
            $scoreColumn->update([
                'is_active' => (bool) $data['is_active'],
            ]);
        });

        $scoreColumn->refresh();

        return response()->json($this->statusPayload($scoreColumn));
    }

    public function bulkLock(Request $request)
    {
        $data = $request->validate([
            'column_ids' => ['required', 'array', 'min:1'],
            'column_ids.*' => ['required', 'string', 'uuid', 'distinct', 'exists:score_columns,id'],
            'status' => ['required', Rule::in(['open', 'locked'])],
        ]);

        $isActive = $data['status'] === 'open';
        $columnIds = collect($data['column_ids'])->map(fn ($id) => (string) $id)->unique()->values();

        try {
            $changedAt = now();
            $affectedCount = DB::transaction(function () use ($columnIds, $isActive, $changedAt) {
                return ScoreColumn::whereIn('id', $columnIds)->update([
                    'is_active' => $isActive,
                    'updated_at' => $changedAt,
                ]);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Không thể cập nhật hàng loạt cột điểm. Vui lòng thử lại.',
            ], 500);
        }

        return response()->json([
            'is_active' => $isActive,
            'count' => $affectedCount,
            'column_ids' => $columnIds,
            'label' => $this->statusLabel($isActive),
            'updated_at_display' => $this->dateTimeLabel($changedAt),
        ]);
    }

    public function destroy(ScoreColumn $scoreColumn)
    {
        if ($scoreColumn->details()->exists()) {
            return back()->with('error', 'Không thể xóa cột điểm đã có dữ liệu. Có thể chuyển trạng thái sang Đã khóa nhập điểm.');
        }

        DB::transaction(function () use ($scoreColumn) {
            $scoreColumn->delete();
        });

        return back()->with('success', 'Đã xóa cột điểm.');
    }

    private function statusPayload(ScoreColumn $scoreColumn): array
    {
        return [
            'id' => $scoreColumn->id,
            'is_active' => (bool) $scoreColumn->is_active,
            'label' => $this->statusLabel((bool) $scoreColumn->is_active),
            'updated_at_display' => $this->dateTimeLabel($scoreColumn->updated_at),
            'input_opens_display' => $scoreColumn->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn',
            'input_closes_display' => $scoreColumn->input_closes_at?->format('d/m/Y'),
            'deadline_label' => $this->deadlineLabel($scoreColumn),
        ];
    }

    private function statusLabel(bool $isActive): string
    {
        return $isActive ? '🟢 Đang mở' : '🔒 Đã khóa';
    }

    private function deadlineLabel(ScoreColumn $scoreColumn): string
    {
        return $scoreColumn->input_closes_at
            ? '⌛ Hạn: ' . $scoreColumn->input_closes_at->format('d/m/Y')
            : 'Vô thời hạn';
    }

    private function dateTimeLabel($dateTime): string
    {
        if (! $dateTime) {
            return now()->format('H:i d/m/Y');
        }

        return $dateTime->copy()->timezone(config('app.timezone'))->format('H:i d/m/Y');
    }

    private function validatedData(Request $request, ?ScoreColumn $scoreColumn = null): array
    {
        $types = array_keys(ScoreColumn::TYPES);
        $uniqueName = Rule::unique('score_columns', 'name')
            ->where('school_year_id', $request->input('school_year_id'))
            ->where('subject_id', $request->input('subject_id'))
            ->where('grade_level', $request->input('grade_level'));

        if ($scoreColumn) {
            $uniqueName->ignore($scoreColumn->id);
        }

        $data = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'grade_level' => ['required', 'integer', Rule::in([10, 11, 12])],
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'type' => ['required', Rule::in($types)],
            'input_opens_at' => ['nullable', 'date'],
            'input_closes_at' => ['nullable', 'date', 'after_or_equal:input_opens_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'school_year_id' => 'năm học',
            'subject_id' => 'môn học',
            'grade_level' => 'khối',
            'name' => 'tên cột điểm',
            'type' => 'loại điểm',
            'input_opens_at' => 'ngày mở nhập điểm',
            'input_closes_at' => 'ngày khóa nhập điểm',
            'sort_order' => 'thứ tự',
        ]);

        $data['weight_group'] = ScoreSetting::current()->weightForScoreType($data['type']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $subject = Subject::find($data['subject_id']);
        if (! $subject?->isEvaluated()) {
            abort(422, 'Môn học Không đánh giá không được cấu hình cột điểm.');
        }
        if (! $subject->appliesToGrade((int) $data['grade_level'])) {
            abort(422, 'Môn học này không được cấu hình áp dụng cho khối đã chọn.');
        }

        return $data;
    }

    private function syncScoreColumnFamily(array $scope, string $family, int $desiredCount): array
    {
        $setting = ScoreSetting::current();
        $baseName = match ($family) {
            'oral' => 'Kiểm tra Miệng',
            'fifteen' => 'Kiểm tra 15 phút',
            default => 'Kiểm tra 1 tiết',
        };
        $baseSortOrder = match ($family) {
            'oral' => 10,
            'fifteen' => 20,
            default => 30,
        };

        $columns = ScoreColumn::query()
            ->where('school_year_id', $scope['school_year_id'])
            ->where('subject_id', $scope['subject_id'])
            ->where('grade_level', (int) $scope['grade_level'])
            ->where('type', ScoreColumn::TYPE_REGULAR)
            ->get()
            ->filter(fn (ScoreColumn $column) => $this->scoreColumnFamily($column) === $family)
            ->sortBy(fn (ScoreColumn $column) => [$this->scoreColumnSequence($column), $column->sort_order, $column->name])
            ->values();

        $columns->each(fn (ScoreColumn $column) => $column->update([
            'name' => 'tmp_' . $column->id,
        ]));

        $keptColumns = $columns->take($desiredCount)->values();
        foreach ($keptColumns as $index => $column) {
            $column->update([
                'name' => $this->scoreColumnManagedName($baseName, $desiredCount, $index + 1),
                'type' => ScoreColumn::TYPE_REGULAR,
                'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_REGULAR),
                'sort_order' => $baseSortOrder + $index + 1,
            ]);
        }

        for ($index = $keptColumns->count() + 1; $index <= $desiredCount; $index++) {
            ScoreColumn::create([
                'school_year_id' => $scope['school_year_id'],
                'subject_id' => $scope['subject_id'],
                'grade_level' => (int) $scope['grade_level'],
                'name' => $this->scoreColumnManagedName($baseName, $desiredCount, $index),
                'type' => ScoreColumn::TYPE_REGULAR,
                'weight_group' => $setting->weightForScoreType(ScoreColumn::TYPE_REGULAR),
                'sort_order' => $baseSortOrder + $index,
                'is_active' => true,
            ]);
        }

        $surplusColumns = $columns->slice($desiredCount)->values();
        $affectedHeaderIds = collect();

        if ($surplusColumns->isNotEmpty()) {
            $surplusColumnIds = $surplusColumns->pluck('id')->values();
            $affectedHeaderIds = ScoreDetail::whereIn('score_column_id', $surplusColumnIds)
                ->pluck('score_header_id')
                ->unique()
                ->values();

            ScoreDetail::whereIn('score_column_id', $surplusColumnIds)->delete();
            ScoreColumn::whereIn('id', $surplusColumnIds)->delete();
        }

        return [
            'count' => $desiredCount,
            'affected_header_ids' => $affectedHeaderIds,
        ];
    }

    private function syncFixedExamColumn(array $scope, string $type): array
    {
        $setting = ScoreSetting::current();
        $baseName = $type === ScoreColumn::TYPE_MIDTERM ? 'Kiểm tra Giữa kỳ' : 'Kiểm tra Cuối kỳ';
        $sortOrder = $type === ScoreColumn::TYPE_MIDTERM ? 40 : 50;

        $columns = ScoreColumn::query()
            ->where('school_year_id', $scope['school_year_id'])
            ->where('subject_id', $scope['subject_id'])
            ->where('grade_level', (int) $scope['grade_level'])
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        $primary = $columns->first();
        if (! $primary) {
            ScoreColumn::create([
                'school_year_id' => $scope['school_year_id'],
                'subject_id' => $scope['subject_id'],
                'grade_level' => (int) $scope['grade_level'],
                'name' => $baseName,
                'type' => $type,
                'weight_group' => $setting->weightForScoreType($type),
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);

            return [
                'count' => 1,
                'affected_header_ids' => collect(),
            ];
        }

        $primary->update([
            'name' => $baseName,
            'type' => $type,
            'weight_group' => $setting->weightForScoreType($type),
            'sort_order' => $sortOrder,
        ]);

        $surplusColumns = $columns->slice(1)->values();
        $affectedHeaderIds = collect();

        if ($surplusColumns->isNotEmpty()) {
            $surplusColumnIds = $surplusColumns->pluck('id')->values();
            $affectedHeaderIds = ScoreDetail::whereIn('score_column_id', $surplusColumnIds)
                ->pluck('score_header_id')
                ->unique()
                ->values();

            ScoreDetail::whereIn('score_column_id', $surplusColumnIds)->delete();
            ScoreColumn::whereIn('id', $surplusColumnIds)->delete();
        }

        return [
            'count' => 1,
            'affected_header_ids' => $affectedHeaderIds,
        ];
    }

    private function matrixStatePayload(array $scope): array
    {
        $columns = ScoreColumn::query()
            ->where('school_year_id', $scope['school_year_id'])
            ->where('subject_id', $scope['subject_id'])
            ->where('grade_level', (int) $scope['grade_level'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->reject(fn (ScoreColumn $column) => $this->scoreColumnFamily($column) === 'one_period')
            ->values();

        $columnsByFamily = $columns
            ->groupBy(fn (ScoreColumn $column) => $this->scoreColumnFamily($column))
            ->map(fn ($familyColumns) => $familyColumns
                ->sortBy(fn (ScoreColumn $column) => [$this->scoreColumnSequence($column), $column->sort_order, $column->name])
                ->values()
                ->map(fn (ScoreColumn $column) => [
                    'id' => $column->id,
                    'name' => $column->name,
                    'family' => $this->scoreColumnFamily($column),
                    'visual_type' => match ($column->type) {
                        ScoreColumn::TYPE_MIDTERM => 'midterm',
                        ScoreColumn::TYPE_FINAL => 'final',
                        default => 'regular',
                    },
                    'is_active' => (bool) $column->is_active,
                    'toggle_url' => route('score-columns.toggle-lock', $column),
                    'input_opens_display' => $column->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn',
                    'input_closes_display' => $column->input_closes_at?->format('d/m/Y'),
                    'deadline_label' => $this->deadlineLabel($column),
                    'updated_at_display' => $this->dateTimeLabel($column->updated_at),
                ]));

        return [
            'row_key' => md5(implode('|', [
                $scope['school_year_id'],
                $scope['grade_level'],
                $scope['subject_id'],
            ])),
            'total_count' => $columns->count(),
            'columns_by_family' => [
                'oral' => $columnsByFamily->get('oral', collect())->values(),
                'fifteen' => $columnsByFamily->get('fifteen', collect())->values(),
                'midterm' => $columnsByFamily->get('midterm', collect())->values(),
                'final' => $columnsByFamily->get('final', collect())->values(),
            ],
        ];
    }

    private function scoreColumnFamily(ScoreColumn $column): string
    {
        if ($column->type === ScoreColumn::TYPE_MIDTERM) {
            return 'midterm';
        }

        if ($column->type === ScoreColumn::TYPE_FINAL) {
            return 'final';
        }

        $name = Str::lower(Str::ascii((string) $column->name));

        if (str_contains($name, '15')) {
            return 'fifteen';
        }

        if (str_contains($name, '1 tiet') || str_contains($name, 'mot tiet')) {
            return 'one_period';
        }

        if (str_contains($name, 'mieng') || str_contains($name, 'oral')) {
            return 'oral';
        }

        return 'one_period';
    }

    private function scoreColumnSequence(ScoreColumn $column): int
    {
        $name = Str::lower(Str::ascii((string) $column->name));

        if (preg_match('/lan\s*(\d+)/', $name, $matches)) {
            return (int) $matches[1];
        }

        return max(1, (int) $column->sort_order);
    }

    private function scoreColumnManagedName(string $baseName, int $count, int $index): string
    {
        return $count > 1 ? "{$baseName} (Lần {$index})" : $baseName;
    }

    private function recalculateHeadersByIds($headerIds): void
    {
        $ids = collect($headerIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $setting = ScoreSetting::current();

        ScoreHeader::with(['subject', 'details.scoreColumn'])
            ->whereIn('id', $ids)
            ->get()
            ->each(function (ScoreHeader $header) use ($setting) {
                if (! $header->subject?->isEvaluated() || $header->subject?->usesPassFailAssessment()) {
                    $header->forceFill(['average' => null])->save();
                    return;
                }

                $weightedSum = 0;
                $totalWeight = 0;

                foreach ($header->details as $detail) {
                    if ($detail->value === null) {
                        continue;
                    }

                    if ($detail->scoreColumn && $this->scoreColumnFamily($detail->scoreColumn) === 'one_period') {
                        continue;
                    }

                    $weight = $setting->weightForScoreType($detail->type);
                    $weightedSum += (float) $detail->value * $weight;
                    $totalWeight += $weight;
                }

                $header->forceFill([
                    'average' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null,
                ])->save();
            });
    }

    private function recalculateScoreAverages(ScoreSetting $setting): void
    {
        ScoreHeader::with(['subject', 'details.scoreColumn'])->chunkById(100, function ($headers) use ($setting) {
            foreach ($headers as $header) {
                if (! $header->subject?->isEvaluated() || $header->subject?->usesPassFailAssessment()) {
                    $header->forceFill(['average' => null])->save();
                    continue;
                }

                $weightedSum = 0;
                $totalWeight = 0;

                foreach ($header->details as $detail) {
                    if ($detail->value === null) {
                        continue;
                    }

                    if ($detail->scoreColumn && $this->scoreColumnFamily($detail->scoreColumn) === 'one_period') {
                        continue;
                    }

                    $weight = $setting->weightForScoreType($detail->type);
                    $weightedSum += (float) $detail->value * $weight;
                    $totalWeight += $weight;
                }

                $header->forceFill([
                    'average' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null,
                ])->save();
            }
        });
    }

    protected function selectedSchoolYearId(?Request $request = null): ?string
    {
        $request ??= request();

        return $request->attributes->get('selected_school_year')?->id
            ?? parent::selectedSchoolYearId($request)
            ?? SchoolYear::where('is_current', true)->value('id')
            ?? SchoolYear::orderByDesc('start_date')->value('id');
    }
}
