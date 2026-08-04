@props([
    'id' => 'scoreColumnConfigModal',
    'config' => [],
])

@php
    $typeOptions = \App\Models\ScoreColumn::TYPES;
    $years = collect($config['years'] ?? []);
    $subjects = collect($config['subjects'] ?? []);
    $columns = collect($config['columns'] ?? []);
    $selectedYearId = $config['selectedYearId'] ?? null;
    $selectedGrade = $config['selectedGrade'] ?? 'all';
    $selectedSubjectId = $config['selectedSubjectId'] ?? 'all';
    $keyword = $config['keyword'] ?? '';
    $scoreSetting = $config['scoreSetting'] ?? \App\Models\ScoreSetting::current();
    $scope = preg_replace('/[^A-Za-z0-9_]/', '', $id);

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
        $deadlineLabel = $column->input_closes_at?->format('d/m/Y')
            ? '⌛ Hạn: ' . $column->input_closes_at->format('d/m/Y')
            : 'Vô thời hạn';

        return '
            <div class="score-shortcut-point ' . e($visualType) . '" data-score-column-point>
                <button
                    type="button"
                    class="score-shortcut-toggle ' . ($column->is_active ? 'open' : 'locked') . '"
                    data-score-column-toggle
                    data-url="' . e(route('score-columns.toggle-lock', $column)) . '"
                    data-active="' . ($column->is_active ? '1' : '0') . '"
                    data-column-id="' . e($column->id) . '"
                >' . ($column->is_active ? '🟢 Đang mở' : '🔒 Đã khóa') . '</button>
                <div
                    class="score-shortcut-time"
                    data-score-column-time="' . e($column->id) . '"
                    data-open-label="' . e($column->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn') . '"
                    data-close-label="' . e($column->input_closes_at?->format('d/m/Y') ?? '') . '"
                    data-deadline-label="' . e($deadlineLabel) . '"
                    data-updated-label="' . e($column->updated_at?->timezone(config('app.timezone'))->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y')) . '"
                >
                    <span data-time-close-row>
                        <em data-time-close class="' . (! $column->input_closes_at ? 'text-muted' : '') . '">' . e($deadlineLabel) . '</em>
                    </span>
                </div>
            </div>
        ';
    };
@endphp

<style>
    .score-column-shortcut-modal .modal-dialog {
        max-width: min(1140px, calc(100vw - 2rem));
    }

    .score-column-shortcut-modal .modal-content {
        max-height: 90vh;
        overflow-y: auto;
        border: 0;
        border-radius: 8px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, .28);
    }

    .score-shortcut-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0;
        color: #111827;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .score-shortcut-title::before {
        width: 4px;
        height: 16px;
        display: inline-block;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .score-shortcut-weight-card,
    .score-shortcut-filter-card,
    .score-shortcut-matrix-card {
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff;
    }

    .score-shortcut-weight-card {
        padding: 1rem;
    }

    .score-shortcut-weight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .85rem;
    }

    .score-shortcut-weight-grid label,
    .score-shortcut-filter-row label,
    .score-shortcut-config-grid label {
        color: #374151;
        font-size: .86rem;
        font-weight: 400;
    }

    .score-shortcut-weight-card .form-control:focus,
    .score-shortcut-filter-card .form-control:focus,
    .score-shortcut-filter-card .form-select:focus,
    .score-shortcut-config-grid .form-control:focus,
    .score-shortcut-config-grid .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .9);
    }

    .score-shortcut-formula {
        margin-top: .8rem;
        padding: .68rem .8rem;
        border: 1px solid rgba(253, 186, 116, .65);
        border-radius: 8px;
        color: #9a3412;
        background: rgba(255, 247, 237, .78);
        font-size: .88rem;
        font-weight: 400;
    }

    .score-shortcut-filter-card {
        margin: 1rem 0;
        padding: .9rem;
    }

    .score-shortcut-filter-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: .8rem;
    }

    .score-shortcut-filter-left,
    .score-shortcut-filter-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .65rem;
    }

    .score-shortcut-filter-field {
        min-width: 140px;
    }

    .score-shortcut-filter-field.wide {
        min-width: 230px;
    }

    .score-shortcut-search {
        position: relative;
    }

    .score-shortcut-search i {
        position: absolute;
        left: .72rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    .score-shortcut-search .form-control {
        padding-left: 2.1rem;
        border-radius: 8px;
        background: rgba(249, 250, 251, .8);
    }

    .score-shortcut-bulk {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .48rem .7rem;
        border-radius: 8px;
        font-size: .88rem;
        font-weight: 400;
    }

    .score-shortcut-bulk.open {
        border: 1px solid #bbf7d0;
        color: #15803d;
        background: #f0fdf4;
    }

    .score-shortcut-bulk.lock {
        border: 1px solid #fecaca;
        color: #b91c1c;
        background: #fef2f2;
    }

    .score-shortcut-matrix-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .score-shortcut-matrix-table th {
        padding: .78rem .65rem;
        color: #374151;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        font-size: .96rem;
        font-weight: 500;
        text-align: left;
    }

    .score-shortcut-matrix-table td {
        padding: .74rem .65rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: .94rem;
        font-weight: 400;
        vertical-align: top;
    }

    .score-shortcut-grade-header {
        cursor: pointer;
        user-select: none;
    }

    .score-shortcut-grade-header td {
        padding: .78rem 1rem;
        border-top: 1px solid #fed7aa;
        border-bottom: 1px solid #fed7aa;
        color: #111827;
        background: rgba(255, 237, 213, .4);
        font-size: .9rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .score-shortcut-grade-arrow {
        width: 1rem;
        display: inline-block;
        color: #c2410c;
        font-weight: 700;
    }

    .score-shortcut-grade-toggle {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        white-space: nowrap;
    }

    .score-shortcut-subject-row {
        cursor: pointer;
        transition: all .18s ease;
    }

    .score-shortcut-subject-row.is-odd td {
        background: #fff;
    }

    .score-shortcut-subject-row.is-even td {
        background: rgba(249, 250, 251, .55);
    }

    .score-shortcut-subject-row:hover td {
        background: rgba(255, 247, 237, .72) !important;
    }

    .score-shortcut-subject strong {
        display: block;
        color: #111827;
        font-size: .96rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .score-shortcut-subject span,
    .score-shortcut-empty {
        color: #6b7280;
        font-size: .8rem;
        font-weight: 400;
    }

    .score-shortcut-point-list {
        display: grid;
        gap: .45rem;
    }

    .score-shortcut-count {
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

    .score-shortcut-point {
        display: grid;
        gap: .22rem;
        padding: .42rem 0 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: transparent;
        border-color: transparent;
    }

    .score-shortcut-point.midterm {
        border-color: transparent;
        background: transparent;
    }

    .score-shortcut-point.final {
        border-color: transparent;
        background: transparent;
    }

    .score-shortcut-toggle {
        width: 100%;
        border-radius: 999px;
        padding: .28rem .48rem;
        font-size: .76rem;
        font-weight: 400;
    }

    .score-shortcut-toggle.open {
        border: 1px solid #bbf7d0;
        color: #15803d;
        background: #f0fdf4;
    }

    .score-shortcut-toggle.locked {
        border: 1px solid #fecaca;
        color: #b91c1c;
        background: #fef2f2;
    }

    .score-shortcut-time {
        display: grid;
        gap: .12rem;
        color: #6b7280;
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.35;
    }

    .score-shortcut-time span {
        display: inline-flex;
        align-items: center;
        gap: .22rem;
    }

    .score-shortcut-time em {
        font-style: normal;
    }

    .score-shortcut-time .score-column-manual-lock {
        color: #b91c1c;
        font-weight: 500;
    }

    .score-shortcut-status-fallback {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        padding: .2rem .5rem;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        color: #15803d;
        background: #f0fdf4;
        font-size: .76rem;
        font-weight: 400;
        line-height: 1.2;
    }

    .score-shortcut-edit-btn {
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

    .score-shortcut-edit-btn:hover {
        color: #fff;
        background: #ea580c;
        border-color: #ea580c;
    }

    .score-shortcut-config-row {
        padding: .85rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }

    .score-shortcut-config-grid {
        display: grid;
        grid-template-columns: minmax(160px, 1.4fr) minmax(130px, .85fr) repeat(2, minmax(130px, .8fr)) minmax(90px, .5fr) auto;
        gap: .65rem;
        align-items: end;
    }

    .score-column-toast {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1085;
        transform: translateY(10px);
        opacity: 0;
        padding: .75rem .9rem;
        border-radius: 8px;
        color: #fff;
        background: #ea580c;
        font-size: .9rem;
        font-weight: 400;
        box-shadow: 0 16px 32px rgba(15, 23, 42, .2);
        transition: all .2s ease;
    }

    .score-column-toast.error {
        background: #b91c1c;
    }

    .score-column-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    @media (max-width: 991.98px) {
        .score-shortcut-weight-grid {
            grid-template-columns: 1fr;
        }

        .score-shortcut-matrix-table {
            min-width: 1080px;
        }

        .score-shortcut-config-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div data-score-column-config-root data-score-column-config-modal-root>
    <div class="modal fade score-column-shortcut-modal" id="{{ $id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-white p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="score-shortcut-title">Quản lý cấu hình cột điểm</h2>
                        <div class="text-muted small mt-1 fw-normal">Cấu hình trọng số, trạng thái mở khóa và chi tiết đầu điểm theo từng môn học.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <form method="POST" action="{{ route('score-columns.settings.update') }}" class="score-shortcut-weight-card" data-score-weight-form>
                    @csrf
                    @method('PATCH')
                    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between">
                        <div class="flex-grow-1">
                            <h3 class="score-shortcut-title mb-3">Cấu hình trọng số và công thức tính điểm toàn trường</h3>
                            <div class="score-shortcut-weight-grid">
                                <div>
                                    <label class="form-label">Trọng số ĐGTX</label>
                                    <input type="number" name="weight_gdtx" class="form-control" value="{{ $scoreSetting->weight_gdtx }}" min="1" max="20" required data-score-weight-input>
                                </div>
                                <div>
                                    <label class="form-label">Trọng số Giữa kỳ</label>
                                    <input type="number" name="weight_dggk" class="form-control" value="{{ $scoreSetting->weight_dggk }}" min="1" max="20" required data-score-weight-input>
                                </div>
                                <div>
                                    <label class="form-label">Trọng số Cuối kỳ</label>
                                    <input type="number" name="weight_dgck" class="form-control" value="{{ $scoreSetting->weight_dgck }}" min="1" max="20" required data-score-weight-input>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-end">
                            <button class="btn btn-primary" data-score-weight-save>
                                <i class="bi bi-save me-1"></i>Lưu hệ số
                            </button>
                        </div>
                    </div>
                    <div class="score-shortcut-formula" data-score-weight-formula>
                        ĐTBmhk = {{ $scoreSetting->formulaLabel() }}. Kết quả làm tròn 1 chữ số thập phân.
                    </div>
                </form>

                <div class="score-shortcut-filter-card">
                    <form class="score-shortcut-filter-row" data-score-column-local-filter>
                        <div class="score-shortcut-filter-left">
                            <div class="score-shortcut-filter-field wide">
                                <label class="form-label">Tìm kiếm</label>
                                <div class="score-shortcut-search">
                                    <i class="bi bi-search"></i>
                                    <input type="search" class="form-control" value="{{ $keyword }}" placeholder="Tên cột điểm hoặc môn học" data-score-column-filter-keyword>
                                </div>
                            </div>
                            <div class="score-shortcut-filter-field">
                                <label class="form-label">Năm học</label>
                                <select class="form-select" data-score-column-filter-year>
                                    @foreach($years as $year)
                                        <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="score-shortcut-filter-field">
                                <label class="form-label">Khối</label>
                                <select class="form-select" data-score-column-filter-grade>
                                    <option value="all" @selected($selectedGrade === 'all')>Tất cả</option>
                                    @foreach([10, 11, 12] as $grade)
                                        <option value="{{ $grade }}" @selected((string) $selectedGrade === (string) $grade)>Khối {{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="score-shortcut-filter-field wide">
                                <label class="form-label">Môn học</label>
                                <select class="form-select" data-score-column-filter-subject>
                                    <option value="all" @selected($selectedSubjectId === 'all')>Tất cả môn học</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="score-shortcut-filter-actions">
                            <button class="btn btn-primary"><i class="bi bi-search"></i> Lọc</button>
                            <button type="button" class="btn btn-outline-secondary" data-score-column-filter-reset>Đặt lại</button>
                            <button type="button" class="score-shortcut-bulk open" data-score-column-bulk="open" data-url="{{ route('score-columns.bulk-lock') }}">
                                <i class="bi bi-unlock"></i>Mở tất cả theo bộ lọc
                            </button>
                            <button type="button" class="score-shortcut-bulk lock" data-score-column-bulk="locked" data-url="{{ route('score-columns.bulk-lock') }}">
                                <i class="bi bi-lock"></i>Khóa tất cả theo bộ lọc
                            </button>
                        </div>
                    </form>
                </div>

                <div class="score-shortcut-matrix-card">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <span class="fw-medium text-gray-900">Danh sách cột điểm</span>
                        <span class="text-muted small" data-score-column-count>{{ $columns->count() }} đầu điểm</span>
                    </div>
                    <div class="table-responsive">
                        <table class="score-shortcut-matrix-table">
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
                                <tr class="score-shortcut-grade-header" data-score-grade-toggle="{{ $grade }}">
                                    <td colspan="6">
                                        <span class="score-shortcut-grade-toggle">
                                            <span class="score-shortcut-grade-arrow" data-score-grade-arrow>▼</span>
                                            KHỐI {{ $grade }} @if($gradeYearLabel) (Niên khóa {{ $gradeYearLabel }}) @endif • Tổng số: {{ $rows->sum(fn ($row) => $row['columns']->count()) }} đầu điểm
                                        </span>
                                    </td>
                                </tr>
                                @foreach($rows as $row)
                                    @php
                                        $firstColumn = $row['first'];
                                        $modalId = $scope . 'ScoreColumnMatrixModal' . $row['key'];
                                        $searchText = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(($firstColumn->subject?->name ?? '') . ' ' . $row['columns']->pluck('name')->implode(' ') . ' ' . ($firstColumn->schoolYear?->name ?? '')));
                                    @endphp
                                    <tr
                                        class="score-shortcut-subject-row {{ $loop->even ? 'is-even' : 'is-odd' }}"
                                        data-score-grade-row="{{ $grade }}"
                                        data-score-row-year="{{ $firstColumn->school_year_id }}"
                                        data-score-row-subject="{{ $firstColumn->subject_id }}"
                                        data-score-row-search="{{ $searchText }}"
                                        data-score-subject-row="{{ $row['key'] }}"
                                    >
                                        <td class="score-shortcut-subject">
                                            <strong>📖 {{ $firstColumn->subject?->name ?? '-' }}</strong>
                                            <span data-score-row-summary>{{ $row['columns']->count() }} đầu điểm</span>
                                        </td>
                                        @foreach(['oral' => 'regular', 'fifteen' => 'regular', 'midterm' => 'midterm', 'final' => 'final'] as $category => $visualType)
                                            <td data-score-category-cell="{{ $category }}" data-score-fixed-category="{{ in_array($category, ['midterm', 'final'], true) ? '1' : '0' }}">
                                                <div class="score-shortcut-point-list" data-score-point-list>
                                                    @if(in_array($category, ['midterm', 'final'], true))
                                                        <span class="score-shortcut-count" data-score-category-count>x1 cột (Mặc định)</span>
                                                    @else
                                                        <span class="score-shortcut-count" data-score-category-count>x{{ $row['categories'][$category]->count() }} cột</span>
                                                    @endif
                                                    @forelse($row['categories'][$category] as $column)
                                                        {!! $renderMatrixPoint($column, $visualType) !!}
                                                    @empty
                                                        @if(in_array($category, ['midterm', 'final'], true))
                                                            <span class="score-shortcut-status-fallback" data-score-empty-state>🟢 Đang mở</span>
                                                            <span class="score-shortcut-empty" data-score-empty-state>Vô thời hạn</span>
                                                        @endif
                                                    @endforelse
                                                </div>
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            <button type="button" class="score-shortcut-edit-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Cấu hình chi tiết" aria-label="Cấu hình chi tiết">
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
        </div>
    </div>

    @foreach($scoreMatrixRows as $row)
        @php
            $firstColumn = $row['first'];
            $modalId = $scope . 'ScoreColumnMatrixModal' . $row['key'];
        @endphp
        <div class="modal fade content-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 448px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold text-gray-900">Cấu hình đầu điểm - {{ $firstColumn->subject?->name ?? '-' }}</h5>
                            <p class="text-muted small mb-0 fw-normal">Khối {{ $firstColumn->grade_level }} · {{ $firstColumn->schoolYear?->name ?? '-' }} · {{ $row['columns']->count() }} đầu điểm</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('score-columns.matrix-counts.update') }}" class="score-shortcut-config-row mb-0" data-score-column-count-form data-score-target-row="{{ $row['key'] }}">
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
                                    <div class="text-muted small fw-normal">Giữa kỳ cố định 1 cột theo chính sách.</div>
                                    <input type="number" class="form-control" value="1" disabled>
                                </div>
                                <div>
                                    <label class="form-label fw-normal">Số lượng cột Kiểm tra Cuối kỳ</label>
                                    <input type="number" class="form-control" value="1" disabled>
                                </div>
                                <button class="btn btn-primary w-100">💾 Lưu cấu hình môn</button>
                            </div>
                        </form>
                        <div class="d-none">
                            @foreach($row['columns'] as $column)
                                <form method="POST" action="{{ route('score-columns.update', $column) }}" class="score-shortcut-config-row" data-score-column-form>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="school_year_id" value="{{ $column->school_year_id }}">
                                    <input type="hidden" name="grade_level" value="{{ $column->grade_level }}">
                                    <input type="hidden" name="subject_id" value="{{ $column->subject_id }}">
                                    <div class="score-shortcut-config-grid">
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
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $scope }}MatrixActive{{ $column->id }}" @checked($column->is_active)>
                                                <label class="form-check-label fw-normal" for="{{ $scope }}MatrixActive{{ $column->id }}">Đang mở</label>
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
</div>

<script>
    (() => {
        const root = document.querySelector('[data-score-column-config-root][data-score-column-config-modal-root]');

        if (! root || root.dataset.initialized === '1') {
            return;
        }

        root.dataset.initialized = '1';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || root.querySelector('input[name="_token"]')?.value
            || '{{ csrf_token() }}';
        const openLabel = '🟢 Đang mở';
        const lockedLabel = '🔒 Đã khóa';

        const showToast = (message, type = 'success') => {
            const toast = document.createElement('div');
            toast.className = `score-column-toast ${type === 'success' ? 'success' : 'error'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(() => toast.classList.add('show'), 20);
            window.setTimeout(() => {
                toast.classList.remove('show');
                window.setTimeout(() => toast.remove(), 240);
            }, 2600);
        };

        const formatNow = () => {
            const now = new Date();
            const time = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
            const date = now.toLocaleDateString('vi-VN');
            return `${time} ${date}`;
        };

        const renderTime = (columnId, isActive, payload = {}) => {
            const timeCell = Array.from(root.querySelectorAll('[data-score-column-time]'))
                .find((element) => element.dataset.scoreColumnTime === String(columnId));

            if (! timeCell) {
                return;
            }

            const closeElement = timeCell.querySelector('[data-time-close]');

            if (! closeElement) {
                return;
            }

            const closeText = payload.input_closes_display ?? timeCell.dataset.closeLabel ?? '';
            const deadlineLabel = payload.deadline_label || (closeText ? `⌛ Hạn: ${closeText}` : 'Vô thời hạn');
            timeCell.dataset.closeLabel = closeText || '';
            timeCell.dataset.deadlineLabel = deadlineLabel;
            closeElement.textContent = deadlineLabel;
            closeElement.classList.toggle('text-muted', ! closeText);
            closeElement.classList.remove('score-column-manual-lock');
        };

        const applyToggleState = (button, isActive, payload = {}) => {
            button.dataset.active = isActive ? '1' : '0';
            button.classList.toggle('open', isActive);
            button.classList.toggle('locked', ! isActive);
            button.textContent = isActive ? openLabel : lockedLabel;
            renderTime(button.dataset.columnId, isActive, payload);
        };

        const bindToggleButton = (button) => {
            if (! button || button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', async (event) => {
                event.stopPropagation();
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

                    applyToggleState(button, Boolean(payload.is_active), payload);
                } catch (error) {
                    showToast(error.message || 'Không thể cập nhật trạng thái cột điểm.', 'error');
                } finally {
                    button.classList.remove('is-loading');
                }
            });
        };

        const createMatrixPoint = (column) => {
            const point = document.createElement('div');
            point.className = `score-shortcut-point ${column.visual_type || 'regular'}`;
            point.dataset.scoreColumnPoint = '1';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = `score-shortcut-toggle ${column.is_active ? 'open' : 'locked'}`;
            button.dataset.scoreColumnToggle = '1';
            button.dataset.url = column.toggle_url || '';
            button.dataset.active = column.is_active ? '1' : '0';
            button.dataset.columnId = column.id || '';
            button.textContent = column.is_active ? openLabel : lockedLabel;
            point.appendChild(button);

            const time = document.createElement('div');
            time.className = 'score-shortcut-time';
            time.dataset.scoreColumnTime = column.id || '';
            time.dataset.openLabel = column.input_opens_display || 'Vô thời hạn';
            time.dataset.closeLabel = column.input_closes_display || '';
            time.dataset.deadlineLabel = column.deadline_label || (column.input_closes_display ? `⌛ Hạn: ${column.input_closes_display}` : 'Vô thời hạn');
            time.dataset.updatedLabel = column.updated_at_display || formatNow();

            const timeRow = document.createElement('span');
            timeRow.dataset.timeCloseRow = '1';
            const deadline = document.createElement('em');
            deadline.dataset.timeClose = '1';
            deadline.textContent = time.dataset.deadlineLabel;
            deadline.classList.toggle('text-muted', ! column.input_closes_display);
            timeRow.appendChild(deadline);
            time.appendChild(timeRow);
            point.appendChild(time);

            bindToggleButton(button);

            return point;
        };

        const setSubjectColumnsState = (rowKey, state) => {
            if (! rowKey || ! state?.columns_by_family) {
                return;
            }

            const row = root.querySelector(`[data-score-subject-row="${rowKey}"]`);
            if (! row) {
                return;
            }

            const families = ['oral', 'fifteen', 'midterm', 'final'];
            families.forEach((family) => {
                const cell = row.querySelector(`[data-score-category-cell="${family}"]`);
                const pointList = cell?.querySelector('[data-score-point-list]');
                const countLabel = cell?.querySelector('[data-score-category-count]');

                if (! cell || ! pointList || ! countLabel) {
                    return;
                }

                const columns = state.columns_by_family[family] || [];
                const isFixed = cell.dataset.scoreFixedCategory === '1';
                countLabel.textContent = isFixed ? 'x1 cột (Mặc định)' : `x${columns.length} cột`;
                countLabel.classList.remove('d-none');

                pointList.querySelectorAll('[data-score-column-point], [data-score-empty-state]').forEach((element) => element.remove());
                columns.forEach((column) => pointList.appendChild(createMatrixPoint(column)));

                if (isFixed && columns.length === 0) {
                    const status = document.createElement('span');
                    status.className = 'score-shortcut-status-fallback';
                    status.dataset.scoreEmptyState = '1';
                    status.textContent = openLabel;
                    pointList.appendChild(status);

                    const deadline = document.createElement('span');
                    deadline.className = 'score-shortcut-empty';
                    deadline.dataset.scoreEmptyState = '1';
                    deadline.textContent = 'Vô thời hạn';
                    pointList.appendChild(deadline);
                }
            });

            const summary = row.querySelector('[data-score-row-summary]');
            if (summary && Number.isFinite(Number(state.total_count))) {
                summary.textContent = `${state.total_count} đầu điểm`;
            }
        };

        window.setSubjectColumnsState = setSubjectColumnsState;

        root.querySelectorAll('[data-score-grade-toggle]').forEach((header) => {
            header.addEventListener('click', () => {
                const grade = header.dataset.scoreGradeToggle;
                const arrow = header.querySelector('[data-score-grade-arrow]');
                header.dataset.collapsed = header.dataset.collapsed === '1' ? '0' : '1';
                const collapsed = header.dataset.collapsed === '1';

                root.querySelectorAll(`[data-score-grade-row="${grade}"]`).forEach((row) => {
                    row.hidden = collapsed || row.dataset.filterHidden === '1';
                });

                if (arrow) {
                    arrow.textContent = collapsed ? '▸' : '▼';
                }
            });
        });

        const filterForm = root.querySelector('[data-score-column-local-filter]');
        const applyFilter = () => {
            const keyword = (filterForm?.querySelector('[data-score-column-filter-keyword]')?.value || '').trim().toLowerCase();
            const year = filterForm?.querySelector('[data-score-column-filter-year]')?.value || '';
            const grade = filterForm?.querySelector('[data-score-column-filter-grade]')?.value || 'all';
            const subject = filterForm?.querySelector('[data-score-column-filter-subject]')?.value || 'all';

            root.querySelectorAll('[data-score-grade-row]').forEach((row) => {
                const matchesKeyword = keyword === '' || (row.dataset.scoreRowSearch || '').includes(keyword);
                const matchesYear = year === '' || row.dataset.scoreRowYear === year;
                const matchesGrade = grade === 'all' || row.dataset.scoreGradeRow === grade;
                const matchesSubject = subject === 'all' || row.dataset.scoreRowSubject === subject;
                row.dataset.filterHidden = matchesKeyword && matchesYear && matchesGrade && matchesSubject ? '0' : '1';
            });

            root.querySelectorAll('[data-score-grade-toggle]').forEach((header) => {
                const rows = Array.from(root.querySelectorAll(`[data-score-grade-row="${header.dataset.scoreGradeToggle}"]`));
                const visibleRows = rows.filter((row) => row.dataset.filterHidden !== '1');
                const collapsed = header.dataset.collapsed === '1';

                header.hidden = visibleRows.length === 0;
                rows.forEach((row) => {
                    row.hidden = row.dataset.filterHidden === '1' || collapsed;
                });
            });
        };

        if (filterForm) {
            let debounceTimer = null;
            filterForm.addEventListener('submit', (event) => {
                event.preventDefault();
                applyFilter();
            });
            filterForm.querySelector('[data-score-column-filter-keyword]')?.addEventListener('input', () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(applyFilter, 300);
            });
            filterForm.querySelectorAll('select').forEach((select) => select.addEventListener('change', applyFilter));
            filterForm.querySelector('[data-score-column-filter-reset]')?.addEventListener('click', () => {
                const keyword = filterForm.querySelector('[data-score-column-filter-keyword]');
                const grade = filterForm.querySelector('[data-score-column-filter-grade]');
                const subject = filterForm.querySelector('[data-score-column-filter-subject]');
                if (keyword) keyword.value = '';
                if (grade) grade.value = 'all';
                if (subject) subject.value = 'all';
                applyFilter();
            });
        }

        root.querySelectorAll('[data-score-column-count-form]').forEach((form) => {
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

                    setSubjectColumnsState(payload.state?.row_key || form.dataset.scoreTargetRow, payload.state);
                    showToast(payload.message || 'Đã lưu cấu hình số lượng cột điểm.');
                    const modalElement = form.closest('.modal');
                    if (modalElement && window.bootstrap?.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    }
                } catch (error) {
                    showToast(error.message || 'Không thể lưu cấu hình số lượng cột điểm.', 'error');
                } finally {
                    saveButton?.removeAttribute('disabled');
                }
            });
        });

        root.querySelectorAll('[data-score-column-form]').forEach((form) => {
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

        root.querySelectorAll('[data-score-weight-form]').forEach((form) => {
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

                    showToast(payload.message || 'Đã cập nhật cấu hình trọng số tính điểm toàn trường.');
                } catch (error) {
                    showToast(error.message || 'Không thể cập nhật cấu hình trọng số.', 'error');
                } finally {
                    saveButton?.removeAttribute('disabled');
                }
            });
        });

        root.querySelectorAll('[data-score-column-toggle]').forEach(bindToggleButton);

        root.querySelectorAll('[data-score-column-bulk]').forEach((button) => {
            button.addEventListener('click', async () => {
                const toggles = Array.from(root.querySelectorAll('[data-score-column-toggle]'))
                    .filter((toggle) => ! toggle.closest('[data-score-grade-row]')?.hidden);
                const columnIds = toggles.map((toggle) => toggle.dataset.columnId).filter(Boolean);

                if (columnIds.length === 0) {
                    showToast('Không có cột điểm nào trong bộ lọc hiện tại để cập nhật.', 'error');
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

                    const updatedIds = new Set((payload.column_ids || columnIds).map((columnId) => String(columnId)));
                    toggles.forEach((toggle) => {
                        if (updatedIds.has(toggle.dataset.columnId)) {
                            applyToggleState(toggle, Boolean(payload.is_active), payload);
                        }
                    });

                    showToast(`Đã ${payload.is_active ? 'mở' : 'khóa'} thành công ${payload.count ?? columnIds.length} cột điểm.`);
                } catch (error) {
                    showToast(error.message || 'Không thể cập nhật hàng loạt cột điểm.', 'error');
                } finally {
                    button.classList.remove('is-loading');
                }
            });
        });

        applyFilter();
    })();
</script>
