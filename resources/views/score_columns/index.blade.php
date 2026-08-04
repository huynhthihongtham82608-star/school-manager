@extends('layouts.app')
@section('title', 'Cấu hình cột điểm')

@section('content')
@php
    $typeOptions = \App\Models\ScoreColumn::TYPES;
@endphp

<style>
    .score-weight-card {
        margin-bottom: 1rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
    }

    .score-weight-card .card-body {
        padding: 1.15rem;
    }

    .score-weight-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0 0 1rem;
        color: #111827;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .score-weight-title::before {
        width: 4px;
        height: 16px;
        display: inline-block;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .score-weight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .score-weight-grid label {
        color: #374151;
        font-size: .9rem;
        font-weight: 400;
    }

    .score-weight-grid .form-control {
        border-color: #e5e7eb;
        border-radius: 8px;
        font-weight: 400;
    }

    .score-weight-grid .form-control:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .95);
    }

    .score-weight-formula {
        margin-top: 1rem;
        padding: .7rem .85rem;
        border: 1px solid rgba(253, 186, 116, .65);
        border-radius: 8px;
        color: #9a3412;
        background: rgba(255, 247, 237, .72);
        font-size: .9rem;
        font-weight: 400;
    }

    .score-matrix-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    .score-matrix-card .card-body {
        padding: 0;
    }

    .score-matrix-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .score-matrix-table th {
        padding: .85rem .75rem;
        color: #374151;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        font-size: 1rem;
        font-weight: 500;
        text-align: left;
    }

    .score-matrix-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: top;
    }

    .score-grade-header {
        cursor: pointer;
        user-select: none;
    }

    .score-grade-header td {
        padding: .75rem 1rem;
        border-top: 1px solid #fed7aa;
        border-bottom: 1px solid #fed7aa;
        color: #111827;
        background: rgba(255, 237, 213, .4);
        font-size: .9rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .score-matrix-subject-row {
        cursor: pointer;
        transition: all .18s ease;
    }

    .score-matrix-subject-row.is-odd td {
        background: #fff;
    }

    .score-matrix-subject-row.is-even td {
        background: rgba(249, 250, 251, .55);
    }

    .score-matrix-subject-row:hover td {
        background: rgba(255, 247, 237, .72) !important;
    }

    .score-grade-toggle {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        white-space: nowrap;
    }

    .score-grade-arrow {
        width: 1rem;
        display: inline-block;
        color: #c2410c;
        font-weight: 700;
    }

    .score-subject-cell strong {
        display: block;
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .score-subject-cell span {
        display: block;
        margin-top: .2rem;
        color: #6b7280;
        font-size: .82rem;
        font-weight: 400;
    }

    .score-matrix-point-list {
        display: grid;
        gap: .5rem;
    }

    .score-matrix-count {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        padding: .1rem .42rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        color: #6b7280;
        background: #f9fafb;
        font-size: .76rem;
        font-weight: 400;
        line-height: 1.2;
    }

    .score-matrix-point {
        min-width: 0;
        display: grid;
        gap: .22rem;
        padding: .42rem 0 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: transparent;
        border-color: transparent;
    }

    .score-matrix-point.midterm {
        border-color: transparent;
        background: transparent;
    }

    .score-matrix-point.final {
        border-color: transparent;
        background: transparent;
    }

    .score-matrix-point-empty {
        color: #9ca3af;
        font-size: .86rem;
        font-weight: 400;
    }

    .score-matrix-status-fallback {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        padding: .2rem .5rem;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        color: #15803d;
        background: #f0fdf4;
        font-size: .78rem;
        font-weight: 400;
        line-height: 1.2;
    }

    .score-matrix-time {
        display: grid;
        gap: .16rem;
        color: #6b7280;
        font-size: .78rem;
        font-weight: 400;
        line-height: 1.35;
    }

    .score-matrix-time span {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }

    .score-matrix-time em {
        font-style: normal;
    }

    .score-matrix-time .score-column-manual-lock {
        color: #b91c1c;
        font-weight: 500;
    }

    .score-matrix-actions {
        text-align: center;
    }

    .score-matrix-edit-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #c2410c;
        background: #fff7ed;
    }

    .score-matrix-edit-btn:hover {
        color: #fff;
        background: #ea580c;
        border-color: #ea580c;
    }

    .score-matrix-config-list {
        display: grid;
        gap: .75rem;
    }

    .score-matrix-config-row {
        padding: .85rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }

    .score-matrix-config-grid {
        display: grid;
        grid-template-columns: minmax(160px, 1.4fr) minmax(130px, .85fr) repeat(2, minmax(130px, .8fr)) minmax(90px, .5fr) auto;
        gap: .65rem;
        align-items: end;
    }

    .score-matrix-config-grid label {
        color: #4b5563;
        font-size: .78rem;
        font-weight: 400;
    }

    .score-matrix-config-grid .form-control,
    .score-matrix-config-grid .form-select {
        font-size: .88rem;
        font-weight: 400;
    }

    @media (max-width: 767.98px) {
        .score-weight-grid {
            grid-template-columns: 1fr;
        }

        .score-matrix-table {
            min-width: 1180px;
        }

        .score-matrix-config-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<x-page-header
    title="Cấu hình cột điểm"
    subtitle="Admin quản lý tên, loại và số lượng cột điểm theo năm học, khối và môn học."
>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Quay lại điểm số
        </a>
    </div>
</x-page-header>

<div class="card score-weight-card">
    <div class="card-body">
        <form method="POST" action="{{ route('score-columns.settings.update') }}" data-score-weight-form>
            @csrf
            @method('PATCH')
            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between">
                <div class="flex-grow-1">
                    <h2 class="score-weight-title">Cấu hình trọng số và công thức tính điểm toàn trường</h2>
                    <div class="score-weight-grid">
                        <div>
                            <label class="form-label">Trọng số ĐGTX</label>
                            <input type="number" name="weight_gdtx" class="form-control" value="{{ old('weight_gdtx', $scoreSetting->weight_gdtx) }}" min="1" max="20" required data-score-weight-input>
                        </div>
                        <div>
                            <label class="form-label">Trọng số Giữa kỳ</label>
                            <input type="number" name="weight_dggk" class="form-control" value="{{ old('weight_dggk', $scoreSetting->weight_dggk) }}" min="1" max="20" required data-score-weight-input>
                        </div>
                        <div>
                            <label class="form-label">Trọng số Cuối kỳ</label>
                            <input type="number" name="weight_dgck" class="form-control" value="{{ old('weight_dgck', $scoreSetting->weight_dgck) }}" min="1" max="20" required data-score-weight-input>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-end">
                    <button class="btn btn-primary" data-score-weight-save>
                        <i class="bi bi-save me-1"></i>Lưu hệ số
                    </button>
                </div>
            </div>
            <div class="score-weight-formula" data-score-weight-formula>
                ĐTBmhk = {{ $scoreSetting->formulaLabel() }}. Kết quả làm tròn 1 chữ số thập phân.
            </div>
        </form>
    </div>
</div>

<div class="card mb-3 score-column-filter-card">
    <div class="card-body">
        <form method="GET" class="score-column-filter-row">
            <div class="score-column-filter-left">
                <div class="score-filter-field wide score-filter-search-field">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="toolbar-inline-search">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" class="form-control" value="{{ $keyword ?? '' }}" placeholder="Tên cột điểm hoặc môn học">
                    </div>
                </div>
                <div class="score-filter-field">
                    <label class="form-label">Năm học</label>
                    <select name="school_year_id" class="form-select">
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="score-filter-field compact">
                    <label class="form-label">Khối</label>
                    <select name="grade_level" class="form-select">
                        <option value="all" @selected($selectedGrade === 'all')>Tất cả</option>
                        @foreach([10, 11, 12] as $grade)
                            <option value="{{ $grade }}" @selected((string) $selectedGrade === (string) $grade)>Khối {{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="score-filter-field wide">
                    <label class="form-label">Môn học</label>
                    <select name="subject_id" class="form-select">
                        <option value="all" @selected($selectedSubjectId === 'all')>Tất cả môn học</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="score-filter-actions">
                <button class="btn btn-primary">
                    <i class="bi bi-search"></i>
                    Lọc
                </button>
                <a href="{{ route('score-columns.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                <button type="button" class="score-bulk-btn open" data-score-column-bulk="open" data-url="{{ route('score-columns.bulk-lock') }}">
                    <i class="bi bi-unlock"></i>
                    Mở tất cả theo bộ lọc
                </button>
                <button type="button" class="score-bulk-btn lock" data-score-column-bulk="locked" data-url="{{ route('score-columns.bulk-lock') }}">
                    <i class="bi bi-lock"></i>
                    Khóa tất cả theo bộ lọc
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade content-modal score-column-config-modal" id="createScoreColumnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form method="POST" action="{{ route('score-columns.store') }}" data-score-column-form>
                @csrf
                <div class="modal-header">
                    <div class="score-column-modal-title">
                        <h5 class="modal-title">Thêm cột điểm số mới</h5>
                        <p>Thiết lập tên cột, loại điểm và thời hạn nhập cho từng môn học theo khối.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="score-column-form-grid">
                        <div>
                            <label class="form-label">Năm học</label>
                            <select name="school_year_id" class="form-select" required>
                                @foreach($years as $year)
                                    <option value="{{ $year->id }}" @selected((string) old('school_year_id', $selectedYearId) === (string) $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Khối</label>
                            <select name="grade_level" class="form-select" required>
                                @foreach([10, 11, 12] as $grade)
                                    <option value="{{ $grade }}" @selected((string) old('grade_level', $selectedGrade !== 'all' ? $selectedGrade : 10) === (string) $grade)>Khối {{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Môn học</label>
                            <select name="subject_id" class="form-select" required>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected((string) old('subject_id', $selectedSubjectId !== 'all' ? $selectedSubjectId : '') === (string) $subject->id)>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tên cột điểm</label>
                            <input name="name" class="form-control" value="{{ old('name') }}" placeholder="Ví dụ: Kiểm tra 15 phút lần 1" required maxlength="255">
                        </div>
                        <div>
                            <label class="form-label">Loại điểm</label>
                            <select name="type" class="form-select" required>
                                @foreach($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" max="1000">
                        </div>
                        <div>
                            <label class="form-label">Ngày mở</label>
                            <input type="date" name="input_opens_at" class="form-control" value="{{ old('input_opens_at') }}">
                        </div>
                        <div>
                            <label class="form-label">Ngày khóa</label>
                            <input type="date" name="input_closes_at" class="form-control" value="{{ old('input_closes_at') }}">
                        </div>
                        <div class="score-column-checkline">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveAdd" checked>
                            <label class="form-check-label" for="isActiveAdd">Đang mở nhập điểm</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Thêm cột điểm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $scoreColumnGroups = $columns->groupBy(fn ($column) => implode('|', [
        $column->school_year_id,
        $column->grade_level,
        $column->subject_id,
    ]));

    $classifyScoreColumn = function ($column) {
        if ($column->type === \App\Models\ScoreColumn::TYPE_MIDTERM) {
            return 'midterm';
        }

        if ($column->type === \App\Models\ScoreColumn::TYPE_FINAL) {
            return 'final';
        }

        $name = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $column->name));

        if (str_contains($name, 'mieng') || str_contains($name, 'oral')) {
            return 'oral';
        }

        if (str_contains($name, '15')) {
            return 'fifteen';
        }

        return 'one_period';
    };

    $scoreMatrixRows = $scoreColumnGroups
        ->map(function ($group, $key) use ($classifyScoreColumn) {
            $firstColumn = $group->first();

            return [
                'key' => md5($key),
                'first' => $firstColumn,
                'columns' => $group->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                'categories' => [
                    'oral' => $group->filter(fn ($column) => $classifyScoreColumn($column) === 'oral')->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                    'fifteen' => $group->filter(fn ($column) => $classifyScoreColumn($column) === 'fifteen')->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                    'one_period' => $group->filter(fn ($column) => $classifyScoreColumn($column) === 'one_period')->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                    'midterm' => $group->filter(fn ($column) => $classifyScoreColumn($column) === 'midterm')->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                    'final' => $group->filter(fn ($column) => $classifyScoreColumn($column) === 'final')->sortBy(fn ($column) => [$column->sort_order, $column->name])->values(),
                ],
            ];
        })
        ->sortBy(fn ($row) => [(int) ($row['first']->grade_level ?? 0), $row['first']->subject?->name ?? ''])
        ->values();

    $scoreRowsByGrade = $scoreMatrixRows->groupBy(fn ($row) => (int) ($row['first']->grade_level ?? 0))->sortKeys();
    $renderMatrixPoint = function ($column, string $visualType = 'regular') {
        $deadlineLabel = $column->input_closes_at
            ? '⌛ Hạn: ' . $column->input_closes_at->format('d/m/Y')
            : 'Vô thời hạn';

        return '
            <div class="score-matrix-point ' . e($visualType) . '">
                <button
                    type="button"
                    class="score-column-toggle ' . ($column->is_active ? 'open' : 'locked') . '"
                    data-score-column-toggle
                    data-url="' . e(route('score-columns.toggle-lock', $column)) . '"
                    data-active="' . ($column->is_active ? '1' : '0') . '"
                    data-column-id="' . e($column->id) . '"
                >' . ($column->is_active ? '🟢 Đang mở' : '🔒 Đã khóa') . '</button>
                <div
                    class="score-matrix-time"
                    data-score-column-time="' . e($column->id) . '"
                    data-open-label="' . e($column->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn') . '"
                    data-close-label="' . e($column->input_closes_at?->format('d/m/Y') ?? '') . '"
                    data-deadline-label="' . e($deadlineLabel) . '"
                    data-updated-label="' . e($column->updated_at?->timezone(config('app.timezone'))->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y')) . '"
                >
                    <span data-time-close-row>
                        <i class="bi bi-hourglass-split" data-time-close-icon></i>
                        <em data-time-close class="' . (! $column->input_closes_at ? 'text-muted' : '') . '">' . e($deadlineLabel) . '</em>
                    </span>
                </div>
            </div>
        ';
    };
@endphp

<div class="card score-matrix-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Danh sách cột điểm</span>
        <span class="text-muted small">{{ $columns->count() }} đầu điểm</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="score-matrix-table">
                <colgroup>
                    <col style="width: 22%;">
                    <col style="width: 17%;">
                    <col style="width: 17%;">
                    <col style="width: 17%;">
                    <col style="width: 17%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Kiểm tra Miệng</th>
                        <th>Kiểm tra 15 phút</th>
                        <th>Kiểm tra Giữa kỳ</th>
                        <th>Kiểm tra Cuối kỳ</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($scoreRowsByGrade as $grade => $rows)
                    @php
                        $gradeYearNames = $rows
                            ->map(fn ($row) => $row['first']->schoolYear?->name)
                            ->filter()
                            ->unique()
                            ->values();
                        $gradeYearLabel = $gradeYearNames->count() === 1 ? $gradeYearNames->first() : $gradeYearNames->implode(', ');
                    @endphp
                    <tr class="score-grade-header" data-score-grade-toggle="{{ $grade }}">
                        <td colspan="6">
                            <span class="score-grade-toggle">
                                <span class="score-grade-arrow" data-score-grade-arrow>▼</span>
                                KHỐI {{ $grade }} @if($gradeYearLabel) (Niên khóa {{ $gradeYearLabel }}) @endif • Tổng số: {{ $rows->sum(fn ($row) => $row['columns']->count()) }} đầu điểm
                            </span>
                        </td>
                    </tr>
                    @foreach($rows as $row)
                        @php
                            $firstColumn = $row['first'];
                            $modalId = 'scoreColumnMatrixModal' . $row['key'];
                        @endphp
                        <tr class="score-matrix-subject-row {{ $loop->even ? 'is-even' : 'is-odd' }}" data-score-grade-row="{{ $grade }}">
                            <td class="score-subject-cell">
                                <strong>📖 {{ $firstColumn->subject?->name ?? '-' }}</strong>
                                <span>{{ $row['columns']->count() }} đầu điểm</span>
                            </td>
                            @foreach(['oral' => 'regular', 'fifteen' => 'regular', 'midterm' => 'midterm', 'final' => 'final'] as $category => $visualType)
                                <td>
                                    <div class="score-matrix-point-list">
                                        @if(in_array($category, ['midterm', 'final'], true))
                                            <span class="score-matrix-count">x1 cột (Mặc định)</span>
                                        @else
                                            <span class="score-matrix-count">x{{ $row['categories'][$category]->count() }} cột</span>
                                        @endif
                                        @forelse($row['categories'][$category] as $column)
                                            {!! $renderMatrixPoint($column, $visualType) !!}
                                        @empty
                                            @if(in_array($category, ['midterm', 'final'], true))
                                                <span class="score-matrix-status-fallback">🟢 Đang mở</span>
                                                <span class="score-matrix-point-empty">Vô thời hạn</span>
                                            @endif
                                        @endforelse
                                    </div>
                                </td>
                            @endforeach
                            <td class="score-matrix-actions">
                                <button type="button" class="score-matrix-edit-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Cấu hình chi tiết" aria-label="Cấu hình chi tiết">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state"><i class="bi bi-table"></i>Chưa có cột điểm nào.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($scoreMatrixRows as $row)
    @php
        $firstColumn = $row['first'];
        $modalId = 'scoreColumnMatrixModal' . $row['key'];
    @endphp
    <div class="modal fade content-modal score-column-config-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 448px;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="score-column-modal-title">
                        <h5 class="modal-title">Cấu hình đầu điểm - {{ $firstColumn->subject?->name ?? '-' }}</h5>
                        <p>Khối {{ $firstColumn->grade_level }} · {{ $firstColumn->schoolYear?->name ?? '-' }} · {{ $row['columns']->count() }} đầu điểm</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('score-columns.matrix-counts.update') }}" class="score-matrix-config-row mb-0" data-score-column-count-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="school_year_id" value="{{ $firstColumn->school_year_id }}">
                        <input type="hidden" name="grade_level" value="{{ $firstColumn->grade_level }}">
                        <input type="hidden" name="subject_id" value="{{ $firstColumn->subject_id }}">
                        <div class="d-grid gap-3">
                            <div>
                                <label class="form-label fw-normal">Số lượng cột kiểm tra Miệng</label>
                                <input type="number" name="oral_count" class="form-control" value="{{ $row['categories']['oral']->count() }}" min="0" max="10" step="1" required>
                            </div>
                            <div>
                                <label class="form-label fw-normal">Số lượng cột 15 phút</label>
                                <input type="number" name="fifteen_count" class="form-control" value="{{ $row['categories']['fifteen']->count() }}" min="0" max="10" step="1" required>
                            </div>
                            <div>
                                <label class="form-label fw-normal">Số lượng cột Kiểm tra Giữa kỳ</label>
                                <input type="number" class="form-control" value="1" disabled>
                            </div>
                            <div>
                                <label class="form-label fw-normal">Số lượng cột Kiểm tra Cuối kỳ</label>
                                <input type="number" class="form-control" value="1" disabled>
                            </div>
                            <button class="btn btn-primary w-100">💾 Lưu cấu hình môn</button>
                        </div>
                    </form>
                    <div class="score-matrix-config-list d-none">
                        @foreach($row['columns'] as $column)
                            <form method="POST" action="{{ route('score-columns.update', $column) }}" class="score-matrix-config-row" data-score-column-form>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="school_year_id" value="{{ $column->school_year_id }}">
                                <input type="hidden" name="grade_level" value="{{ $column->grade_level }}">
                                <input type="hidden" name="subject_id" value="{{ $column->subject_id }}">
                                <div class="score-matrix-config-grid">
                                    <div>
                                        <label class="form-label">Tên cột điểm</label>
                                        <input name="name" class="form-control" value="{{ $column->name }}" required maxlength="255">
                                    </div>
                                    <div>
                                        <label class="form-label">Loại điểm</label>
                                        <select name="type" class="form-select" required>
                                            @foreach($typeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($column->type === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Ngày mở</label>
                                        <input type="date" name="input_opens_at" class="form-control" value="{{ $column->input_opens_at?->format('Y-m-d') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">Ngày khóa</label>
                                        <input type="date" name="input_closes_at" class="form-control" value="{{ $column->input_closes_at?->format('Y-m-d') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">Thứ tự</label>
                                        <input type="number" name="sort_order" class="form-control" value="{{ $column->sort_order }}" min="0" max="1000">
                                    </div>
                                    <div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="matrixActive{{ $column->id }}" @checked($column->is_active)>
                                            <label class="form-check-label" for="matrixActive{{ $column->id }}">Đang mở</label>
                                        </div>
                                        <button class="btn btn-primary btn-sm w-100">Lưu</button>
                                    </div>
                                </div>
                            </form>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@foreach($columns as $column)
    <div class="modal fade content-modal score-column-detail-modal" id="showScoreColumn{{ $column->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="score-column-modal-title">
                        <h5 class="modal-title">{{ $column->name }}</h5>
                        <p>{{ $column->subject?->name ?? '-' }} · Khối {{ $column->grade_level }} · {{ $column->schoolYear?->name ?? '-' }}</p>
                    </div>
                    <span class="score-column-status-badge {{ $column->is_active ? 'open' : 'closed' }}">{{ $column->is_active ? 'Đang mở nhập điểm' : 'Đã khóa nhập điểm' }}</span>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <section class="score-column-detail-section">
                        <h6>Thông tin cấu hình đầu điểm</h6>
                        <div class="score-column-detail-grid">
                            <article>
                                <span>Loại điểm</span>
                                <strong>{{ $column->typeLabel() }}</strong>
                            </article>
                            <article>
                                <span>Thứ tự hiển thị</span>
                                <strong>{{ $column->sort_order }}</strong>
                            </article>
                            <article>
                                <span>Ngày mở nhập</span>
                                <strong>{{ $column->input_opens_at?->format('d/m/Y') ?? 'Không giới hạn' }}</strong>
                            </article>
                            <article>
                                <span>Ngày khóa nhập</span>
                                <strong>{{ $column->input_closes_at?->format('d/m/Y') ?? 'Vô thời hạn' }}</strong>
                            </article>
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng chi tiết</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade content-modal" id="editScoreColumn{{ $column->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('score-columns.update', $column) }}" data-score-column-form>
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div class="score-column-modal-title">
                            <h5 class="modal-title">Chỉnh sửa cột điểm</h5>
                            <p>Cập nhật cấu hình đầu điểm đang chọn.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Năm học</label>
                                <select name="school_year_id" class="form-select" required>
                                    @foreach($years as $year)
                                        <option value="{{ $year->id }}" @selected($column->school_year_id === $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Khối</label>
                                <select name="grade_level" class="form-select" required>
                                    @foreach([10, 11, 12] as $grade)
                                        <option value="{{ $grade }}" @selected($column->grade_level === $grade)>Khối {{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Môn học</label>
                                <select name="subject_id" class="form-select" required>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected($column->subject_id === $subject->id)>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tên cột điểm</label>
                                <input name="name" class="form-control" value="{{ $column->name }}" required maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Loại điểm</label>
                                <select name="type" class="form-select" required>
                                    @foreach($typeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($column->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Thứ tự</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ $column->sort_order }}" min="0" max="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày mở</label>
                                <input type="date" name="input_opens_at" class="form-control" value="{{ $column->input_opens_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày khóa</label>
                                <input type="date" name="input_closes_at" class="form-control" value="{{ $column->input_closes_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive{{ $column->id }}" @checked($column->is_active)>
                                    <label class="form-check-label" for="isActive{{ $column->id }}">Đang mở nhập điểm</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.querySelectorAll('[data-score-grade-toggle]').forEach((header) => {
        header.addEventListener('click', () => {
            const grade = header.dataset.scoreGradeToggle;
            const arrow = header.querySelector('[data-score-grade-arrow]');
            const rows = document.querySelectorAll(`[data-score-grade-row="${grade}"]`);
            const shouldCollapse = Array.from(rows).some((row) => !row.hidden);

            rows.forEach((row) => {
                row.hidden = shouldCollapse;
            });

            if (arrow) {
                arrow.textContent = shouldCollapse ? '▸' : '▼';
            }
        });
    });

    document.querySelectorAll('[data-score-column-count-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const saveButton = form.querySelector('button[type="submit"], button:not([type])');
            saveButton?.setAttribute('disabled', 'disabled');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(firstError || payload.message || 'Không thể lưu cấu hình số lượng cột điểm.');
                }

                showScoreColumnToast(payload.message || 'Đã lưu cấu hình số lượng cột điểm.');
                window.setTimeout(() => window.location.reload(), 650);
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể lưu cấu hình số lượng cột điểm.', 'error');
            } finally {
                saveButton?.removeAttribute('disabled');
            }
        });
    });

    document.querySelectorAll('[data-score-column-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const openInput = form.querySelector('[name="input_opens_at"]');
            const closeInput = form.querySelector('[name="input_closes_at"]');

            if (openInput?.value && closeInput?.value && closeInput.value < openInput.value) {
                event.preventDefault();
                closeInput.setCustomValidity('Ngày khóa nhập điểm phải sau hoặc bằng ngày mở.');
                closeInput.reportValidity();
                return;
            }

            closeInput?.setCustomValidity('');
        });
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '{{ csrf_token() }}';
    const openScoreColumnLabel = '🟢 Đang mở';
    const lockedScoreColumnLabel = '🔒 Đã khóa';

    const showScoreColumnToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `score-column-toast ${type === 'success' ? 'success' : 'error'}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        window.setTimeout(() => toast.classList.add('show'), 20);
        window.setTimeout(() => {
            toast.classList.remove('show');
            window.setTimeout(() => toast.remove(), 240);
        }, 2800);
    };

    document.querySelectorAll('[data-score-weight-form]').forEach((form) => {
        const formula = form.querySelector('[data-score-weight-formula]');
        const saveButton = form.querySelector('[data-score-weight-save]');
        const inputs = {
            gdtx: form.querySelector('[name="weight_gdtx"]'),
            dggk: form.querySelector('[name="weight_dggk"]'),
            dgck: form.querySelector('[name="weight_dgck"]'),
        };

        const numericValue = (input) => Math.max(1, Number.parseInt(input?.value || '1', 10) || 1);
        const renderFormula = () => {
            const w1 = numericValue(inputs.gdtx);
            const w2 = numericValue(inputs.dggk);
            const w3 = numericValue(inputs.dgck);
            formula.textContent = `ĐTBmhk = (Tổng ĐGTX x ${w1} + Tổng ĐGGK x ${w2} + Tổng ĐGCK x ${w3}) / (Số cột ĐGTX x ${w1} + Số cột ĐGGK x ${w2} + Số cột ĐGCK x ${w3}). Kết quả làm tròn 1 chữ số thập phân bằng toFixed(1).`;
        };

        Object.values(inputs).forEach((input) => input?.addEventListener('input', renderFormula));
        renderFormula();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            saveButton?.setAttribute('disabled', 'disabled');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(firstError || payload.message || 'Không thể cập nhật cấu hình trọng số.');
                }

                if (payload?.settings) {
                    inputs.gdtx.value = payload.settings.weight_gdtx;
                    inputs.dggk.value = payload.settings.weight_dggk;
                    inputs.dgck.value = payload.settings.weight_dgck;
                    renderFormula();
                }

                showScoreColumnToast(payload.message || 'Đã cập nhật cấu hình trọng số tính điểm toàn trường.');
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể cập nhật cấu hình trọng số.', 'error');
            } finally {
                saveButton?.removeAttribute('disabled');
            }
        });
    });

    const formatScoreColumnNow = () => {
        const now = new Date();
        const time = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
        const date = now.toLocaleDateString('vi-VN');
        return `${time} ${date}`;
    };

    const renderScoreColumnTime = (columnId, isActive, payload = {}) => {
        const timeCell = Array.from(document.querySelectorAll('[data-score-column-time]'))
            .find((element) => element.dataset.scoreColumnTime === String(columnId));

        if (! timeCell) {
            return;
        }

        const closeLabel = payload.input_closes_display || timeCell.dataset.closeLabel || '';
        const deadlineLabel = payload.deadline_label || (closeLabel ? `⌛ Hạn: ${closeLabel}` : 'Vô thời hạn');
        const closeElement = timeCell.querySelector('[data-time-close]');
        const closeRow = timeCell.querySelector('[data-time-close-row]');
        const closeIcon = timeCell.querySelector('[data-time-close-icon]');

        if (! closeElement) {
            return;
        }

        closeElement.classList.remove('score-column-manual-lock', 'text-muted');
        closeRow?.classList.remove('is-manual-lock');
        closeIcon?.classList.remove('bi-lock-fill');
        closeIcon?.classList.add('bi-hourglass-split');
        timeCell.dataset.closeLabel = closeLabel;
        timeCell.dataset.deadlineLabel = deadlineLabel;
        closeElement.textContent = deadlineLabel;
        closeElement.classList.toggle('text-muted', ! closeLabel);
    };

    const applyScoreColumnToggleState = (button, isActive, payload = {}) => {
        button.dataset.active = isActive ? '1' : '0';
        button.classList.toggle('open', isActive);
        button.classList.toggle('locked', ! isActive);
        button.textContent = isActive ? openScoreColumnLabel : lockedScoreColumnLabel;
        renderScoreColumnTime(button.dataset.columnId, isActive, payload);
    };

    document.querySelectorAll('[data-score-column-toggle]').forEach((button) => {
        button.addEventListener('click', async () => {
            const isCurrentlyActive = button.dataset.active === '1';
            button.classList.add('is-loading');

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ is_active: ! isCurrentlyActive }),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(payload.message || 'Không thể cập nhật trạng thái cột điểm.');
                }

                applyScoreColumnToggleState(button, Boolean(payload.is_active), payload);
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể cập nhật trạng thái cột điểm.', 'error');
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });

    document.querySelectorAll('[data-score-column-bulk]').forEach((button) => {
        button.addEventListener('click', async () => {
            const toggles = Array.from(document.querySelectorAll('[data-score-column-toggle]'));
            const columnIds = toggles
                .map((toggle) => toggle.dataset.columnId)
                .filter((id) => typeof id === 'string' && id.length > 0);

            if (columnIds.length === 0) {
                showScoreColumnToast('Không có cột điểm nào trong danh sách hiện tại để cập nhật.', 'error');
                return;
            }

            button.classList.add('is-loading');

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        column_ids: columnIds,
                        status: button.dataset.scoreColumnBulk,
                    }),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(payload.message || 'Không thể cập nhật hàng loạt cột điểm.');
                }

                const updatedIds = new Set((payload.column_ids || columnIds).map((id) => String(id)));
                toggles.forEach((toggle) => {
                    if (updatedIds.has(toggle.dataset.columnId)) {
                        applyScoreColumnToggleState(toggle, Boolean(payload.is_active), payload);
                    }
                });

                const actionLabel = payload.is_active ? 'mở' : 'khóa';
                showScoreColumnToast(`🎉 Đã ${actionLabel} thành công hàng loạt ${payload.count ?? columnIds.length} cột điểm được chọn!`);
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể cập nhật hàng loạt cột điểm.', 'error');
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });
</script>
@endsection
