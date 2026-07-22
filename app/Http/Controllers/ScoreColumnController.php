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

        $years = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $subjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $columns = ScoreColumn::with(['schoolYear', 'subject'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(in_array((string) $selectedGrade, ['10', '11', '12'], true), fn ($query) => $query->where('grade_level', $selectedGrade))
            ->when($selectedSubjectId !== 'all', fn ($query) => $query->where('subject_id', $selectedSubjectId))
            ->orderBy('grade_level')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('score_columns.index', compact(
            'years',
            'subjects',
            'columns',
            'selectedYearId',
            'selectedGrade',
            'selectedSubjectId'
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

    public function destroy(ScoreColumn $scoreColumn)
    {
        if ($scoreColumn->details()->exists()) {
            return back()->with('error', 'Không thể xóa cột điểm đã có dữ liệu. Có thể chuyển trạng thái sang Ngưng sử dụng.');
        }

        DB::transaction(function () use ($scoreColumn) {
            $scoreColumn->delete();
        });

        return back()->with('success', 'Đã xóa cột điểm.');
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
}
