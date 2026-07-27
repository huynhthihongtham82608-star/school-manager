<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScoreColumnController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $request->query('school_year_id', $this->selectedSchoolYearId($request));
        $selectedGrade = $request->query('grade_level', 'all');
        $selectedSubjectId = $request->query('subject_id', 'all');
        $keyword = trim((string) $request->query('q', ''));

        $years = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $subjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $columns = ScoreColumn::with(['schoolYear', 'subject'])
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
            ->get();

        return view('score_columns.index', compact(
            'years',
            'subjects',
            'columns',
            'selectedYearId',
            'selectedGrade',
            'selectedSubjectId',
            'keyword'
        ));
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
            'input_closes_display' => $scoreColumn->input_closes_at?->format('d/m/Y') ?? 'Chưa khóa',
        ];
    }

    private function statusLabel(bool $isActive): string
    {
        return $isActive ? '🟢 Đang mở nhập điểm' : '🔒 Đã khóa nhập điểm';
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

        $data['weight_group'] = match ($data['type']) {
            ScoreColumn::TYPE_MIDTERM => 2,
            ScoreColumn::TYPE_FINAL => 3,
            default => 1,
        };
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
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
