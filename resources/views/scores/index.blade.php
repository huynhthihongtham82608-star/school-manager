@extends('layouts.app')
@section('title', (auth()->user()->isStudent() || auth()->user()->isParent()) ? 'Điểm số' : (auth()->user()->isAdmin() ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số'))

@section('content')
<style>
    .score-formula-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .36rem .62rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #c2410c;
        background: #fff7ed;
        font-size: .78rem;
        font-weight: 400;
        line-height: 1;
    }

    .score-formula-badge:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .score-formula-modal .modal-dialog {
        max-width: 576px;
    }

    .score-formula-modal .modal-content {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 24px 56px rgba(15, 23, 42, .22);
    }

    .score-formula-modal .modal-body {
        padding: 1.5rem;
    }

    .score-formula-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0 0 1rem;
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
    }

    .score-formula-title::before {
        width: 4px;
        height: 16px;
        display: inline-block;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .score-formula-text {
        color: #374151;
        font-size: .92rem;
        font-weight: 400;
        line-height: 1.65;
    }

    .score-formula-box {
        margin-top: .9rem;
        padding: .75rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #9a3412;
        background: #fff7ed;
        font-size: .86rem;
        font-weight: 400;
        line-height: 1.55;
    }

    .score-config-shortcut-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .75rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        color: #c2410c;
        background: #fff7ed;
        font-size: .88rem;
        font-weight: 400;
        line-height: 1.2;
        transition: all .18s ease;
    }

    .score-config-shortcut-btn:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .student-report-toolbar {
        margin-bottom: .9rem;
        padding: .9rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff;
    }

    .student-report-toolbar form {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .75rem;
    }

    .student-report-toolbar label {
        color: #374151;
        font-size: .86rem;
        font-weight: 400;
    }

    .student-report-toolbar .form-select {
        min-width: 180px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 400;
    }

    .student-report-toolbar .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .9);
    }

    .student-report-table th,
    .student-report-table td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
    }

    .student-report-table th {
        color: #111827;
        font-weight: 500;
    }

    .student-report-gpa-row td {
        color: #c2410c;
        background: #fff7ed;
        font-size: 1rem;
        font-weight: 700;
    }

    .student-report-term-col {
        background: rgba(255, 247, 237, .38) !important;
    }

    .student-report-term-value {
        color: #ea580c;
        font-size: 1rem;
        font-weight: 700;
    }

    .student-report-term-muted {
        color: #9ca3af;
        font-size: .92rem;
        font-weight: 400;
    }

    .student-report-summary {
        width: 100%;
        margin-top: .9rem;
        padding: 1rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: rgba(255, 247, 237, .55);
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: space-between;
        gap: .8rem;
    }

    .student-report-summary-item {
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .student-report-summary-item strong {
        color: #ea580c;
        font-weight: 700;
    }

    .student-report-summary-item.master {
        color: #111827;
        font-size: 1.12rem;
        font-weight: 500;
    }

    @media (min-width: 576px) {
        .student-report-summary {
            flex-direction: row;
            align-items: center;
        }
    }

    .score-retest-badge {
        position: relative;
        display: inline-flex;
        align-items: center;
        margin-left: .25rem;
        padding: .05rem .28rem;
        border-radius: 4px;
        color: #c2410c;
        background: #fff7ed;
        font-size: 10px;
        font-weight: 400;
        line-height: 1.25;
        cursor: help;
    }

    .score-retest-badge:hover::after {
        position: absolute;
        left: 50%;
        bottom: calc(100% + 8px);
        z-index: 20;
        min-width: 210px;
        padding: .45rem .55rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        color: #7c2d12;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .16);
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.4;
        white-space: normal;
        transform: translateX(-50%);
        content: attr(data-tooltip);
    }

    .student-report-export-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .75rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        color: #374151;
        background: #f9fafb;
        font-size: .88rem;
        font-weight: 400;
        text-decoration: none;
        transition: all .18s ease;
    }

    .student-report-export-btn:hover {
        color: #ea580c;
        background: #fff7ed;
    }

    .admin-score-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        width: 100%;
        margin-bottom: 1.5rem;
        padding: .75rem;
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    @media (min-width: 992px) {
        .admin-score-toolbar {
            flex-wrap: nowrap;
        }
    }

    .admin-score-search {
        min-width: 180px;
        max-width: 240px;
        flex: 1 1 180px;
        border: 1px solid rgba(255, 237, 213, .72);
        border-radius: 8px;
        background: rgba(255, 247, 237, .18);
        color: #374151;
        font-size: .9rem;
        font-weight: 400;
        transition: all .18s ease;
    }

    .admin-score-search::placeholder {
        color: #9ca3af;
        font-size: .88rem;
        font-weight: 400;
    }

    .admin-score-search:focus,
    .admin-score-toolbar .form-select:focus {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 .25rem rgba(255, 237, 213, .55);
    }

    .admin-score-toolbar .form-select {
        width: 132px;
        min-width: 112px;
        max-width: 156px;
        flex: 0 0 auto;
        border-color: #fed7aa;
        border-radius: 8px;
        color: #374151;
        font-size: .95rem;
        font-weight: 400;
    }

    .admin-score-toolbar [data-admin-score-class] {
        width: 118px;
    }

    .admin-score-toolbar [data-admin-score-subject] {
        width: 150px;
    }

    .admin-score-toolbar [data-admin-score-teacher] {
        width: 156px;
    }

    .admin-score-reset-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        flex: 0 0 auto;
        padding: .5rem .68rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #c2410c;
        background: #fff7ed;
        font-size: .88rem;
        font-weight: 400;
        line-height: 1.2;
        white-space: nowrap;
        transition: all .18s ease;
    }

    .admin-score-reset-btn:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .admin-score-context {
        margin-bottom: .75rem;
        color: #6b7280;
        font-size: .82rem;
        font-weight: 400;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .w-full { width: 100% !important; }
    .table-fixed { table-layout: fixed !important; }
    .text-xs { font-size: 0.75rem !important; line-height: 1rem !important; }
    .text-orange-700 { color: #c2410c !important; }
    .text-orange-800 { color: #9a3412 !important; }
    .bg-orange-50 { background-color: #fff7ed !important; }
    .bg-orange-100 { background-color: #ffedd5 !important; }
    .bg-orange-50\/20 { background-color: rgba(255, 247, 237, 0.2) !important; }
    .border-orange-100 { border-color: #ffedd5 !important; }
    .border-orange-200 { border-color: #fed7aa !important; }

    .admin-score-grid {
        width: 100% !important;
        table-layout: fixed !important;
    }

    .admin-score-grid th,
    .admin-score-grid td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .admin-score-grid th {
        color: #111827;
        font-weight: 500;
        background: #fff;
    }

    .admin-score-student {
        color: #111827;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
    }

    .admin-score-student:hover {
        color: #ea580c;
    }

    .admin-score-value {
        color: #ea580c;
        font-weight: 700;
    }

    .admin-score-empty {
        color: #d1d5db;
        font-weight: 400;
    }

    .admin-score-term {
        background: rgba(255, 247, 237, .38) !important;
    }

    .admin-score-term.locked {
        color: #d1d5db;
        font-weight: 400;
    }

    .admin-score-summary {
        width: 100%;
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: rgba(255, 247, 237, .58);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .admin-score-summary span {
        color: #ea580c;
        font-size: 1rem;
        font-weight: 700;
    }

    .admin-score-summary .master {
        font-size: 1.12rem;
    }

    @media (min-width: 576px) {
        .admin-score-summary {
            flex-direction: row;
        }
    }

    .admin-score-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem 0;
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .admin-score-pager {
        display: inline-flex;
        gap: .5rem;
    }

    .admin-score-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, .45);
    }

    .admin-score-modal-backdrop.active {
        display: flex;
    }

    .admin-score-modal {
        width: 100%;
        max-width: 1024px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 24px 56px rgba(15, 23, 42, .28);
        padding: 1.5rem;
    }

    .admin-score-modal-title {
        display: flex;
        align-items: center;
        margin: 0;
        color: #111827;
        font-size: 1.15rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .admin-score-modal-title::before {
        width: 4px;
        height: 16px;
        margin-right: .5rem;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .admin-score-ledger-card {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        padding: .85rem;
        background: #fff;
    }

    .admin-score-ledger-card h6 {
        margin: 0 0 .55rem;
        color: #111827;
        font-size: .95rem;
        font-weight: 600;
    }

    .admin-score-modal-subtitle {
        margin-top: .25rem;
        color: #6b7280;
        font-size: .92rem;
        font-weight: 400;
    }

    .admin-score-ledger-table th,
    .admin-score-ledger-table td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .admin-score-ledger-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
    }

    .admin-score-ledger-table tbody tr:nth-child(even) td {
        background: rgba(249, 250, 251, .65);
    }

    .admin-score-ledger-table .empty-mark {
        display: block;
        color: #d1d5db;
        font-weight: 400;
        text-align: center;
    }

    .admin-score-ledger-table .average-mark {
        color: #ea580c;
        font-weight: 700;
    }

    .admin-score-chip {
        display: inline-flex;
        gap: .35rem;
        align-items: center;
        margin: .16rem .2rem .16rem 0;
        padding: .25rem .45rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        background: #fff7ed;
        color: #9a3412;
        font-size: .82rem;
        font-weight: 400;
    }
</style>
@if(auth()->user()->isStudent() || auth()->user()->isParent())
    @php
        $detailLabels = $detailLabels ?? [];
        $formatScoreList = function ($score, array $types, array $keywords = []) use ($detailLabels) {
            if (! $score) {
                return '<span class="text-muted">&nbsp;</span>';
            }

            $items = $score->details->filter(function ($detail) use ($types, $keywords, $detailLabels) {
                $normalizedType = match ($detail->type) {
                    'oral', 'quiz', 'test', 'regular' => 'regular',
                    'final', 'final_test' => 'final',
                    default => $detail->type,
                };

                if (! in_array($normalizedType, $types, true)) {
                    return false;
                }

                if ($keywords === []) {
                    return true;
                }

                $label = mb_strtolower((string) ($detail->scoreColumn?->name ?: ($detail->name ?: ($detailLabels[$detail->type] ?? $detail->type))));

                return collect($keywords)->contains(fn ($keyword) => str_contains($label, mb_strtolower($keyword)));
            });

            if ($items->isEmpty()) {
                return '<span class="text-muted">Chưa có</span>';
            }

            return $items
                ->sortBy(fn ($detail) => $detail->scoreColumn?->sort_order ?? 999)
                ->map(function ($detail) use ($detailLabels, $score) {
                    $label = $detail->scoreColumn?->name ?: ($detail->name ?: ($detailLabels[$detail->type] ?? $detail->type));
                    $value = $score->subject?->usesPassFailAssessment()
                        ? ((float) $detail->value >= 0.5 ? 'Đ' : 'CĐ')
                        : rtrim(rtrim(number_format((float) $detail->value, 2, '.', ''), '0'), '.');

                    return '<span class="score-chip"><span>' . e($label) . '</span><strong>' . e($value) . '</strong></span>';
                })
                ->implode('');
        };
    @endphp

    <x-page-header
        :title="auth()->user()->isParent() ? 'Điểm số của con' : 'Điểm số của tôi'"
        :subtitle="auth()->user()->isParent()
            ? 'Theo dõi điểm thành phần và điểm trung bình của học sinh đang chọn.'
            : 'Theo dõi điểm thành phần và điểm trung bình theo từng môn học.'"
    >
        <button type="button" class="score-formula-badge" data-bs-toggle="modal" data-bs-target="#scoreFormulaModal">
            👁️ Xem cách tính điểm
        </button>
    </x-page-header>

    <div class="modal fade score-formula-modal" id="scoreFormulaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-white">
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <h5 class="score-formula-title">Quy tắc tính điểm số học kỳ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <x-score-formula-box :score-setting="$scoreSetting" class="mt-0" />
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row gap-3 justify-content-between">
            <div>
                <div class="text-muted small">Học sinh</div>
                <div class="fw-bold">{{ $student?->student_code }} - {{ $student?->name }}</div>
            </div>
            <div>
                <div class="text-muted small">Lớp</div>
                <div class="fw-bold">{{ $student?->classRoom?->name ?? 'Chưa phân lớp' }}</div>
            </div>
            <div>
                <div class="text-muted small">Học kỳ</div>
                <div class="fw-bold">{{ $semesters->firstWhere('id', $selectedSemesterId)?->normalizedName() ?? 'Học kỳ hiện hành' }}</div>
            </div>
        </div>
    </div>

    <div class="student-report-toolbar" data-student-report-toolbar>
        <form data-student-report-filter data-url="{{ route('scores.report-card') }}">
            <div>
                <label class="form-label">Chọn Năm học</label>
                <select class="form-select" name="school_year_id" data-report-year>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected((string) $selectedYearId === (string) $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Chọn Học kỳ</label>
                <select class="form-select" name="semester_id" data-report-semester>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>{{ $semester->normalizedName() }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card" data-student-report-card>
        <div class="card-header d-flex align-items-center justify-content-between gap-3 bg-white">
            <span class="fw-medium text-gray-900">Phiếu điểm tổng hợp học kỳ</span>
            <a
                href="{{ route('scores.report-card.export', ['school_year_id' => $selectedYearId, 'semester_id' => $selectedSemesterId]) }}"
                class="student-report-export-btn"
                data-report-export
            >
                📥 Xuất phiếu điểm học kỳ
            </a>
        </div>
        <div class="table-responsive">
            @php
                $studentReportColumnHeaders = collect($studentReportColumnHeaders ?? []);
                $studentReportColumnsBySubject = collect($studentReportColumnsBySubject ?? []);
                $studentReportAnnualSummary = $studentReportAnnualSummary ?? ['hk1_gpa' => null, 'hk2_gpa' => null, 'year_gpa' => null];
                $formatTermValue = fn ($value) => $value !== null ? number_format((float) $value, 1, '.', '') : null;
                $formatReportScoreValue = function ($score, $subject, $column) {
                    if (! $score || ! $column) {
                        return '<span class="text-muted">-</span>';
                    }

                    $detail = $score->details?->firstWhere('score_column_id', $column->id);
                    if (! $detail || $detail->value === null) {
                        return '<span class="text-muted">-</span>';
                    }

                    $value = $subject->usesPassFailAssessment()
                        ? ((float) $detail->value >= 0.5 ? 'Đ' : 'CĐ')
                        : rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.');
                    $badge = '';

                    if ($detail->is_retest && $detail->original_value !== null) {
                        $originalValue = rtrim(rtrim(number_format((float) $detail->original_value, 1, '.', ''), '0'), '.');
                        $tooltip = 'Điểm gốc: ' . $originalValue . '. Cập nhật ngày: ' . ($detail->retest_updated_at?->format('d/m/Y') ?? '-');
                        $badge = '<span class="score-retest-badge" data-tooltip="' . e($tooltip) . '">Bù</span>';
                    }

                    return '<span class="score-chip"><strong>' . e($value) . '</strong>' . $badge . '</span>';
                };
            @endphp
            <table class="table align-middle student-report-table">
                <thead data-report-head>
                    <tr>
                        <th>Môn học</th>
                        @foreach($studentReportColumnHeaders as $header)
                            <th class="text-nowrap">{{ $header['label'] }}</th>
                        @endforeach
                        <th>Điểm trung bình môn</th>
                        <th class="student-report-term-col">Tổng kết HK1</th>
                        <th class="student-report-term-col">Tổng kết HK2</th>
                        <th class="student-report-term-col">Điểm Cả Năm</th>
                    </tr>
                </thead>
                <tbody data-report-body>
                @forelse($studentReportRows as $row)
                    @php
                        $subject = $row['subject'];
                        $score = $row['score'];
                        $columnsByFamily = collect($studentReportColumnsBySubject->get($subject->id, []));
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $subject->name ?? '-' }}</td>
                        @foreach($studentReportColumnHeaders as $header)
                            @php
                                $familyColumns = collect($columnsByFamily->get($header['family'], []))->values();
                                $column = $familyColumns->get(((int) $header['index']) - 1);
                            @endphp
                            <td>{!! $formatReportScoreValue($score, $subject, $column) !!}</td>
                        @endforeach
                        <td>
                            @if($subject->usesPassFailAssessment())
                                <span class="text-muted">Không tính TB</span>
                            @elseif($score?->average !== null)
                                <span class="badge bg-info">{{ rtrim(rtrim(number_format($score->average, 1), '0'), '.') }}</span>
                            @else
                                <span class="text-muted">&nbsp;</span>
                            @endif
                        </td>
                        @php
                            $termAverages = $row['annual'] ?? ['hk1' => null, 'hk2' => null, 'year' => null];
                        @endphp
                        <td class="student-report-term-col">
                            @if($termAverages['hk1'] !== null)
                                <span class="student-report-term-value">{{ $formatTermValue($termAverages['hk1']) }}</span>
                            @else
                                <span class="student-report-term-muted">-</span>
                            @endif
                        </td>
                        <td class="student-report-term-col">
                            @if($termAverages['hk2'] !== null)
                                <span class="student-report-term-value">{{ $formatTermValue($termAverages['hk2']) }}</span>
                            @else
                                <span class="student-report-term-muted">-</span>
                            @endif
                        </td>
                        <td class="student-report-term-col">
                            @if($termAverages['year'] !== null)
                                <span class="student-report-term-value">{{ $formatTermValue($termAverages['year']) }}</span>
                            @else
                                <span class="student-report-term-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $studentReportColumnHeaders->count() + 5 }}">
                            <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu điểm trong học kỳ này.</div>
                        </td>
                    </tr>
                @endforelse
                    <tr class="student-report-gpa-row" data-report-gpa-row>
                        <td colspan="{{ $studentReportColumnHeaders->count() + 4 }}">Điểm trung bình học kỳ (Tất cả các môn)</td>
                        <td data-report-gpa>{{ $studentReportGlobalGpa !== null ? number_format($studentReportGlobalGpa, 1, '.', '') : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div
            class="student-report-summary"
            data-report-annual-summary
            data-hk1="{{ $studentReportAnnualSummary['hk1_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['hk1_gpa'], 1, '.', '') : '-' }}"
            data-hk2="{{ $studentReportAnnualSummary['hk2_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['hk2_gpa'], 1, '.', '') : '-' }}"
            data-year="{{ $studentReportAnnualSummary['year_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['year_gpa'], 1, '.', '') : '-' }}"
        >
            <div class="student-report-summary-item">🟢 Điểm TB học kỳ 1: <strong data-report-summary-hk1>{{ $studentReportAnnualSummary['hk1_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['hk1_gpa'], 1, '.', '') : '-' }}</strong></div>
            <div class="student-report-summary-item">🔵 Điểm TB học kỳ 2: <strong data-report-summary-hk2>{{ $studentReportAnnualSummary['hk2_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['hk2_gpa'], 1, '.', '') : '-' }}</strong></div>
            <div class="student-report-summary-item master">🔥 ĐIỂM TRUNG BÌNH CẢ NĂM: <strong data-report-summary-year>{{ $studentReportAnnualSummary['year_gpa'] !== null ? number_format((float) $studentReportAnnualSummary['year_gpa'], 1, '.', '') : '-' }}</strong></div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-student-report-filter]');
            const tableHead = document.querySelector('[data-report-head]');
            const tableBody = document.querySelector('[data-report-body]');
            const yearSelect = form?.querySelector('[data-report-year]');
            const semesterSelect = form?.querySelector('[data-report-semester]');
            const exportButton = document.querySelector('[data-report-export]');
            const summaryHk1 = document.querySelector('[data-report-summary-hk1]');
            const summaryHk2 = document.querySelector('[data-report-summary-hk2]');
            const summaryYear = document.querySelector('[data-report-summary-year]');

            if (! form || ! tableHead || ! tableBody || ! yearSelect || ! semesterSelect) {
                return;
            }

            const createCell = (tag, text, className = '') => {
                const cell = document.createElement(tag);
                cell.textContent = text;
                if (className) {
                    cell.className = className;
                }
                return cell;
            };

            const renderSemesters = (semesters, selectedSemesterId) => {
                semesterSelect.innerHTML = '';
                semesters.forEach((semester) => {
                    const option = document.createElement('option');
                    option.value = semester.id;
                    option.textContent = semester.name;
                    option.selected = String(semester.id) === String(selectedSemesterId);
                    semesterSelect.appendChild(option);
                });
            };

            const createTermCell = (value) => {
                const td = document.createElement('td');
                td.className = 'student-report-term-col';
                const span = document.createElement('span');
                span.className = value ? 'student-report-term-value' : 'student-report-term-muted';
                span.textContent = value || '-';
                td.appendChild(span);

                return td;
            };

            const renderAnnualSummary = (summary = {}) => {
                if (summaryHk1) {
                    summaryHk1.textContent = summary.hk1_gpa || '-';
                }

                if (summaryHk2) {
                    summaryHk2.textContent = summary.hk2_gpa || '-';
                }

                if (summaryYear) {
                    summaryYear.textContent = summary.year_gpa || '-';
                }
            };

            const renderReport = (payload) => {
                renderSemesters(payload.semesters || [], payload.selected_semester_id);
                if (exportButton) {
                    const exportUrl = new URL(exportButton.href, window.location.origin);
                    exportUrl.searchParams.set('school_year_id', payload.selected_year_id || yearSelect.value);
                    exportUrl.searchParams.set('semester_id', payload.selected_semester_id || semesterSelect.value);
                    exportButton.href = exportUrl.toString();
                }

                const headerRow = document.createElement('tr');
                headerRow.appendChild(createCell('th', 'Môn học'));
                (payload.headers || []).forEach((header) => {
                    headerRow.appendChild(createCell('th', header.label, 'text-nowrap'));
                });
                headerRow.appendChild(createCell('th', 'Điểm trung bình môn'));
                headerRow.appendChild(createCell('th', 'Tổng kết HK1', 'student-report-term-col'));
                headerRow.appendChild(createCell('th', 'Tổng kết HK2', 'student-report-term-col'));
                headerRow.appendChild(createCell('th', 'Điểm Cả Năm', 'student-report-term-col'));
                tableHead.innerHTML = '';
                tableHead.appendChild(headerRow);

                tableBody.innerHTML = '';
                const colspan = (payload.headers || []).length + 5;
                renderAnnualSummary(payload.annual_summary || {});

                if (! payload.rows || payload.rows.length === 0) {
                    const emptyRow = document.createElement('tr');
                    const emptyCell = createCell('td', 'Chưa có dữ liệu điểm trong học kỳ này.', 'text-muted');
                    emptyCell.colSpan = colspan;
                    emptyRow.appendChild(emptyCell);
                    tableBody.appendChild(emptyRow);
                } else {
                    payload.rows.forEach((row) => {
                        const tr = document.createElement('tr');
                        tr.appendChild(createCell('td', row.subject_name || '-', 'fw-semibold'));

                        (row.values || []).forEach((value) => {
                            const td = document.createElement('td');
                            const span = document.createElement('span');
                            span.className = value.muted ? 'text-muted' : 'score-chip';
                            span.textContent = value.text || '-';

                            if (value.is_retest && value.retest_tooltip) {
                                const badge = document.createElement('span');
                                badge.className = 'score-retest-badge';
                                badge.dataset.tooltip = value.retest_tooltip;
                                badge.textContent = 'Bù';
                                span.appendChild(badge);
                            }

                            td.appendChild(span);
                            tr.appendChild(td);
                        });

                        const average = document.createElement('td');
                        if (row.uses_pass_fail) {
                            average.appendChild(createCell('span', 'Không tính TB', 'text-muted'));
                        } else if (row.average !== null && row.average !== undefined) {
                            average.appendChild(createCell('span', row.average, 'badge bg-info'));
                        } else {
                            average.appendChild(createCell('span', '-', 'text-muted'));
                        }
                        tr.appendChild(average);
                        tr.appendChild(createTermCell(row.term_averages?.hk1));
                        tr.appendChild(createTermCell(row.term_averages?.hk2));
                        tr.appendChild(createTermCell(row.term_averages?.year));
                        tableBody.appendChild(tr);
                    });
                }

                const gpaRow = document.createElement('tr');
                gpaRow.className = 'student-report-gpa-row';
                const gpaLabel = createCell('td', 'Điểm trung bình học kỳ (Tất cả các môn)');
                gpaLabel.colSpan = Math.max(1, colspan - 1);
                gpaRow.appendChild(gpaLabel);
                gpaRow.appendChild(createCell('td', payload.global_gpa || '-'));
                tableBody.appendChild(gpaRow);
            };

            const fetchReport = async () => {
                const params = new URLSearchParams({
                    school_year_id: yearSelect.value,
                    semester_id: semesterSelect.value,
                });

                const response = await fetch(`${form.dataset.url}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(payload.message || 'Không thể tải phiếu điểm.');
                }

                renderReport(payload);
            };

            [yearSelect, semesterSelect].forEach((select) => {
                select.addEventListener('change', () => {
                    fetchReport().catch((error) => {
                        console.error(error);
                    });
                });
            });
        })();
    </script>
@else
    @php
        $isScoreAdmin = auth()->user()->isAdmin() || auth()->user()->isStaff();
        $availableGrades = $classes->pluck('grade_level')->filter()->unique()->sort()->values();
        $scoreBulkContext = [
            'school_year_id' => $selectedYearId ?? null,
            'class_id' => request('class_id') ?: (isset($selectedClass) ? $selectedClass?->id : null),
            'subject_id' => request('subject_id') ?: (isset($selectedSubject) ? $selectedSubject?->id : null),
            'semester_id' => request('semester_id') ?: ($selectedSemesterId ?? null),
        ];
    @endphp

    <x-page-header
        :title="$isScoreAdmin ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số học sinh'"
        :subtitle="$isScoreAdmin
            ? 'Tra cứu, giám sát tiến độ nhập điểm và phê duyệt yêu cầu sửa đổi điểm số của giáo viên toàn trường.'
            : 'Giáo viên bộ môn nhập điểm theo các cột điểm do Admin cấu hình.'"
    >
        <x-bulk-excel-actions module="scores" :context="$scoreBulkContext" :allow-import="! $isScoreAdmin" />
        @if(auth()->user()->hasPermission('scores.manage'))
            <button type="button" class="score-config-shortcut-btn" data-bs-toggle="modal" data-bs-target="#scoreColumnConfigModal">
                ⚙️ Quản lý cấu hình cột điểm
            </button>
        @endif
    </x-page-header>

    @if(auth()->user()->hasPermission('scores.manage') && $scoreColumnConfig)
        <x-score-column-config-modal id="scoreColumnConfigModal" :config="$scoreColumnConfig" />
    @endif

    @if($isScoreAdmin)
        @php
            $adminMatrix = $adminMatrix ?? [
                'filters' => [],
                'years' => [],
                'semesters' => [],
                'classes' => [],
                'subjects' => [],
                'headers' => [],
                'rows' => [],
                'summary' => [],
                'pagination' => ['label' => 'Hiển thị 0 trong tổng số 0 học sinh', 'show_controls' => false],
                'selected_term_index' => 1,
            ];
            $adminMatrixJson = json_encode($adminMatrix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @endphp

        <div
            data-admin-score-app
            data-cascade-url="{{ route('scores.cascade') }}"
            data-matrix-url="{{ route('scores.admin-matrix') }}"
        >
            <div class="admin-score-toolbar">
                <input
                    type="search"
                    class="form-control admin-score-search"
                    placeholder="Tìm mã HS hoặc họ tên"
                    data-admin-score-search
                    value="{{ $adminMatrix['filters']['q'] ?? '' }}"
                >
                <input type="hidden" data-admin-score-year value="{{ $adminMatrix['filters']['school_year_id'] ?? $selectedYearId }}">
                <select class="form-select" data-admin-score-grade>
                    <option value="">Chọn Khối</option>
                    @foreach([10, 11, 12] as $grade)
                        <option value="{{ $grade }}" @selected((string) ($adminMatrix['filters']['grade_level'] ?? '') === (string) $grade)>Khối {{ $grade }}</option>
                    @endforeach
                </select>
                <select class="form-select" data-admin-score-class>
                    <option value="">Tất cả lớp</option>
                    @foreach(($adminMatrix['classes'] ?? []) as $classOption)
                        <option value="{{ $classOption['id'] }}" @selected((string) ($adminMatrix['filters']['class_id'] ?? '') === (string) $classOption['id'])>{{ $classOption['name'] }}</option>
                    @endforeach
                </select>
                <select class="form-select" data-admin-score-subject>
                    <option value="">Tất cả môn học</option>
                    @foreach(($adminMatrix['subjects'] ?? []) as $subjectOption)
                        <option value="{{ $subjectOption['id'] }}">{{ $subjectOption['name'] }}</option>
                    @endforeach
                </select>
                <select class="form-select" data-admin-score-teacher>
                    <option value="">Chọn Giáo viên</option>
                    @foreach(($teachers ?? collect()) as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->teacher_code ? ' - ' . $teacher->teacher_code : '' }}</option>
                    @endforeach
                </select>
                <select class="form-select" data-admin-score-semester>
                    @foreach(($adminMatrix['semesters'] ?? []) as $semesterOption)
                        <option value="{{ $semesterOption['id'] }}" @selected((string) ($adminMatrix['filters']['semester_id'] ?? '') === (string) $semesterOption['id'])>{{ $semesterOption['name'] }}</option>
                    @endforeach
                </select>
                <div class="admin-eval-toggle d-inline-flex align-items-center gap-1 ms-auto" data-admin-eval-toggle style="display: none;">
                    <button type="button" class="btn text-xs font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded px-2.5 py-1 cursor-pointer shadow-xs" data-eval-tab="GRADE_10">
                        📊 Môn chấm điểm
                    </button>
                    <button type="button" class="btn text-xs font-normal text-orange-700 bg-orange-50 border border-orange-100 rounded px-2.5 py-1 cursor-pointer hover:bg-orange-100 transition-all" data-eval-tab="ASSESSMENT">
                        ☑️ Môn nhận xét
                    </button>
                </div>
                <button type="button" class="admin-score-reset-btn" data-admin-score-reset>
                    <i class="bi bi-arrow-counterclockwise"></i>Đặt lại lọc
                </button>
            </div>

            <div class="admin-score-context" data-admin-score-context>
                Chọn lớp để xem ma trận điểm tất cả các môn.
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle admin-score-grid w-full table-fixed mb-0">
                        <thead data-admin-score-head></thead>
                        <tbody data-admin-score-body></tbody>
                    </table>
                </div>
                <div class="admin-score-footer">
                    <span data-admin-score-count>{{ $adminMatrix['pagination']['label'] ?? 'Hiển thị 0 trong tổng số 0 học sinh' }}</span>
                    <div class="admin-score-pager" data-admin-score-pager></div>
                </div>
            </div>

            <div class="admin-score-summary">
                <span>🟢 Điểm TB Học kỳ 1 (Tổng các môn): <strong data-admin-score-summary-hk1>-</strong></span>
                <span>🔵 Điểm TB Học kỳ 2 (Tổng các môn): <strong data-admin-score-summary-hk2>-</strong></span>
                <span class="master">🔥 ĐIỂM TRUNG BÌNH CẢ NĂM: <strong data-admin-score-summary-year>-</strong></span>
            </div>

            <div class="admin-score-modal-backdrop" data-admin-score-modal aria-hidden="true">
                <div class="admin-score-modal">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="admin-score-modal-title" data-admin-score-modal-title>Chi tiết điểm học sinh</h5>
                            <div class="admin-score-modal-subtitle" data-admin-score-modal-subtitle></div>
                        </div>
                        <button type="button" class="btn-close" data-admin-score-modal-close aria-label="Đóng"></button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle admin-score-ledger-table">
                            <thead>
                                <tr>
                                    <th>Môn học</th>
                                    <th>Điểm Miệng</th>
                                    <th>15 phút (Lần 1)</th>
                                    <th>15 phút (Lần 2)</th>
                                    <th>Điểm Giữa kỳ</th>
                                    <th>Điểm Cuối kỳ</th>
                                    <th>Điểm TB Môn</th>
                                </tr>
                            </thead>
                            <tbody data-admin-score-ledger></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script type="application/json" data-admin-score-initial>{!! $adminMatrixJson !!}</script>
            <script>
                (() => {
                    const app = document.querySelector('[data-admin-score-app]');
                    if (!app) {
                        return;
                    }

                    const initial = JSON.parse(app.querySelector('[data-admin-score-initial]')?.textContent || '{}');
                    const urls = {
                        cascade: app.dataset.cascadeUrl,
                        matrix: app.dataset.matrixUrl,
                    };
                    const controls = {
                        year: app.querySelector('[data-admin-score-year]'),
                        grade: app.querySelector('[data-admin-score-grade]'),
                        classRoom: app.querySelector('[data-admin-score-class]'),
                        subject: app.querySelector('[data-admin-score-subject]'),
                        teacher: app.querySelector('[data-admin-score-teacher]'),
                        semester: app.querySelector('[data-admin-score-semester]'),
                        search: app.querySelector('[data-admin-score-search]'),
                        reset: app.querySelector('[data-admin-score-reset]'),
                    };
                    const head = app.querySelector('[data-admin-score-head]');
                    const body = app.querySelector('[data-admin-score-body]');
                    const context = app.querySelector('[data-admin-score-context]');
                    const count = app.querySelector('[data-admin-score-count]');
                    const pager = app.querySelector('[data-admin-score-pager]');
                    const summaryHk1 = app.querySelector('[data-admin-score-summary-hk1]');
                    const summaryHk2 = app.querySelector('[data-admin-score-summary-hk2]');
                    const summaryYear = app.querySelector('[data-admin-score-summary-year]');
                    const modal = app.querySelector('[data-admin-score-modal]');
                    const modalTitle = app.querySelector('[data-admin-score-modal-title]');
                    const modalSubtitle = app.querySelector('[data-admin-score-modal-subtitle]');
                    const modalLedger = app.querySelector('[data-admin-score-ledger]');
                    const modalClose = app.querySelector('[data-admin-score-modal-close]');
                    const evalToggleEl = app.querySelector('[data-admin-eval-toggle]');
                    const grade10TabBtn = app.querySelector('[data-eval-tab="GRADE_10"]') || app.querySelector('[data-eval-tab="numeric"]');
                    const assessmentTabBtn = app.querySelector('[data-eval-tab="ASSESSMENT"]') || app.querySelector('[data-eval-tab="pass_fail"]');

                    let matrix = initial;
                    let debounceTimer = null;
                    let isResettingFilters = false;
                    let activeEvaluationType = initial.filters?.hinh_thuc_danh_gia || 'GRADE_10';

                    const params = () => new URLSearchParams({
                        school_year_id: controls.year?.value || '',
                        grade_level: controls.grade?.value || '',
                        class_id: controls.classRoom?.value || '',
                        subject_id: controls.subject?.value || '',
                        teacher_id: controls.teacher?.value || '',
                        semester_id: controls.semester?.value || '',
                        hinh_thuc_danh_gia: activeEvaluationType,
                        q: controls.search?.value || '',
                    });

                    const setOptions = (select, items, placeholder, selectedValue = '') => {
                        if (!select) {
                            return;
                        }

                        select.innerHTML = '';
                        if (placeholder !== null) {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = placeholder;
                            select.appendChild(option);
                        }

                        (items || []).forEach((item) => {
                            const option = document.createElement('option');
                            const fullLabel = item.name || '';
                            const shortLabel = fullLabel.length > 24 ? `${fullLabel.slice(0, 23)}…` : fullLabel;
                            option.value = item.id;
                            option.textContent = shortLabel;
                            option.title = fullLabel;
                            option.selected = String(item.id).trim() == String(selectedValue).trim();
                            select.appendChild(option);
                        });
                    };

                    const cell = (tag, text, className = '') => {
                        const el = document.createElement(tag);
                        el.textContent = text;
                        if (className) {
                            el.className = className;
                        }
                        return el;
                    };

                    const renderContext = (payload) => {
                        const classContext = payload.class_context;
                        if (!classContext) {
                            context.textContent = 'Chọn lớp để xem ma trận điểm tất cả các môn.';
                            return;
                        }

                        const teacher = classContext.class_teacher?.name || 'Chưa phân công GVCN';
                        const total = classContext.students?.length || 0;
                        context.textContent = `${classContext.name} · GVCN: ${teacher} · ${total} HS`;
                    };

                    const renderFilters = (payload) => {
                        setOptions(controls.classRoom, payload.classes || [], 'Tất cả lớp', payload.filters?.class_id || controls.classRoom?.value || '');
                        setOptions(controls.subject, payload.subjects || [], 'Tất cả môn học', controls.subject?.value || '');
                        if (payload.semesters) {
                            setOptions(controls.semester, payload.semesters, null, payload.filters?.semester_id || controls.semester?.value || '');
                        }
                    };

                    const termText = (value, locked = false) => {
                        const span = document.createElement('span');
                        span.className = locked || !value ? 'admin-score-term locked' : 'admin-score-value';
                        span.textContent = locked ? '—' : (value || '—');
                        return span;
                    };

                    const renderMatrix = (payload) => {
                        matrix = payload;
                        renderFilters(payload);
                        renderContext(payload);
                        const termIndex = Number(payload.selected_term_index || 1);
                        const lockYearColumns = termIndex === 1;
                        const mode = payload.mode || 'empty';
                        const allHeaders = payload.headers || [];

                        let visibleHeaderIndices = [];
                        let visibleHeaders = [];

                        if (mode === 'class_subjects') {
                            if (evalToggleEl) {
                                evalToggleEl.style.display = 'inline-flex';
                            }

                            if (grade10TabBtn && assessmentTabBtn) {
                                const activeClass = 'btn text-xs font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded px-2.5 py-1 cursor-pointer shadow-xs';
                                const inactiveClass = 'btn text-xs font-normal text-orange-700 bg-orange-50 border border-orange-100 rounded px-2.5 py-1 cursor-pointer hover:bg-orange-100 transition-all';
                                grade10TabBtn.className = (activeEvaluationType === 'GRADE_10' || activeEvaluationType === 'numeric') ? activeClass : inactiveClass;
                                assessmentTabBtn.className = (activeEvaluationType === 'ASSESSMENT' || activeEvaluationType === 'pass_fail') ? activeClass : inactiveClass;
                            }
                        } else {
                            if (evalToggleEl) {
                                evalToggleEl.style.display = 'none';
                            }
                        }

                        const appendTermHeaders = (row) => {
                            row.appendChild(cell('th', 'HK1', 'admin-score-term bg-orange-50/20'));
                            row.appendChild(cell('th', 'HK2', 'admin-score-term bg-orange-50/20'));
                            row.appendChild(cell('th', 'Cả Năm', 'admin-score-term bg-orange-50/20'));
                        };
                        const appendSummaryCells = (rowEl, row) => {
                            const hk1 = document.createElement('td');
                            hk1.className = 'admin-score-term bg-orange-50/20';
                            hk1.appendChild(termText(row.summary?.hk1_gpa));
                            rowEl.appendChild(hk1);

                            const hk2 = document.createElement('td');
                            hk2.className = 'admin-score-term bg-orange-50/20';
                            hk2.appendChild(termText(row.summary?.hk2_gpa, lockYearColumns));
                            rowEl.appendChild(hk2);

                            const year = document.createElement('td');
                            year.className = 'admin-score-term bg-orange-50/20';
                            year.appendChild(termText(row.summary?.year_gpa, lockYearColumns));
                            rowEl.appendChild(year);
                        };
                        const scoreText = (value) => {
                            const span = document.createElement('span');
                            span.className = value ? 'admin-score-value' : 'admin-score-empty';
                            span.textContent = value || '—';
                            return span;
                        };

                        head.innerHTML = '';
                        const tr = document.createElement('tr');
                        tr.appendChild(cell('th', 'Mã HS', 'w-24'));
                        tr.appendChild(cell('th', 'Họ tên', 'w-44'));
                        switch (mode) {
                            case 'grade_summary':
                                tr.appendChild(cell('th', 'Tên Lớp'));
                                appendTermHeaders(tr);
                                break;
                            case 'subject_details':
                                ['Điểm Miệng', '15 phút (1)', '15 phút (2)', 'Giữa kỳ', 'Cuối kỳ', 'TB Môn'].forEach((label) => {
                                    tr.appendChild(cell('th', label));
                                });
                                break;
                            case 'class_subjects':
                                allHeaders.forEach((header) => {
                                    tr.appendChild(cell('th', header.name));
                                });
                                appendTermHeaders(tr);
                                break;
                            default:
                                appendTermHeaders(tr);
                                break;
                        }
                        tr.appendChild(cell('th', 'Thao tác', 'text-end w-16'));
                        head.appendChild(tr);

                        body.innerHTML = '';
                        if (!payload.rows || payload.rows.length === 0) {
                            const empty = document.createElement('tr');
                            const emptyMessage = mode === 'empty'
                                ? 'Chọn khối hoặc lớp để tải ma trận điểm.'
                                : 'Không có học sinh phù hợp bộ lọc.';
                            const emptyCell = cell('td', emptyMessage, 'text-muted');
                            emptyCell.colSpan = mode === 'subject_details'
                                ? 9
                                : (allHeaders.length + (mode === 'grade_summary' ? 7 : 6));
                            empty.appendChild(emptyCell);
                            body.appendChild(empty);
                        } else {
                            payload.rows.forEach((row, index) => {
                                const rowEl = document.createElement('tr');
                                rowEl.appendChild(cell('td', row.student.student_code || '', 'fw-semibold text-gray-900'));

                                const nameCell = document.createElement('td');
                                nameCell.className = 'font-semibold text-gray-900';
                                nameCell.textContent = row.student.name || '-';
                                rowEl.appendChild(nameCell);

                                switch (mode) {
                                    case 'grade_summary':
                                        rowEl.appendChild(cell('td', row.student.class_name || '-', 'text-muted'));
                                        appendSummaryCells(rowEl, row);
                                        break;
                                    case 'subject_details': {
                                        const detail = row.detail_cells || {};
                                        ['oral', 'fifteen_1', 'fifteen_2', 'midterm', 'final', 'average'].forEach((key) => {
                                            const td = document.createElement('td');
                                            td.appendChild(scoreText(detail[key]));
                                            rowEl.appendChild(td);
                                        });
                                        break;
                                    }
                                    case 'class_subjects':
                                    default: {
                                        (row.cells || []).forEach((scoreCell) => {
                                            const valueCell = document.createElement('td');
                                            valueCell.appendChild(scoreText(scoreCell.value));
                                            rowEl.appendChild(valueCell);
                                        });
                                        appendSummaryCells(rowEl, row);
                                        break;
                                    }
                                }

                                const actionTd = document.createElement('td');
                                actionTd.className = 'text-end';
                                const eyeBtn = document.createElement('button');
                                eyeBtn.type = 'button';
                                eyeBtn.className = 'text-gray-500 bg-gray-50 p-2 rounded-md hover:bg-orange-50 hover:text-orange-600 transition-all shadow-xs inline-flex items-center justify-center border-0 cursor-pointer';
                                eyeBtn.title = 'Xem chi tiết';
                                eyeBtn.setAttribute('aria-label', 'Xem chi tiết');
                                eyeBtn.innerHTML = '👁️';
                                eyeBtn.addEventListener('click', () => openLedger(index));
                                actionTd.appendChild(eyeBtn);
                                rowEl.appendChild(actionTd);

                                body.appendChild(rowEl);
                            });
                        }

                        count.textContent = payload.pagination?.label || 'Hiển thị 0 trong tổng số 0 học sinh';
                        pager.innerHTML = '';
                        if (payload.pagination?.show_controls && payload.pagination?.total_pages > 1) {
                            ['Trước', 'Sau'].forEach((label) => {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'btn btn-sm btn-outline-warning';
                                button.textContent = label;
                                pager.appendChild(button);
                            });
                        }

                        summaryHk1.textContent = payload.summary?.hk1_gpa || '-';
                        summaryHk2.textContent = lockYearColumns ? '—' : (payload.summary?.hk2_gpa || '-');
                        summaryYear.textContent = lockYearColumns ? '—' : (payload.summary?.year_gpa || '-');
                    };

                    const renderTeachers = (teachers = []) => {
                        setOptions(controls.teacher, teachers, 'Chọn Giáo viên', controls.teacher?.value || '');
                    };

                    const requestJson = async (url, searchParams) => {
                        const response = await fetch(`${url}?${searchParams.toString()}`, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || 'Không thể tải dữ liệu điểm số.');
                        }

                        return payload;
                    };

                    const refreshCascade = async () => {
                        const payload = await requestJson(urls.cascade, params());
                        const hadSubject = Boolean(controls.subject?.value);
                        const hadTeacher = Boolean(controls.teacher?.value);
                        const selectedSubjectForDropdown = !isResettingFilters && (hadSubject || hadTeacher)
                            ? (payload.selected_subject_id || controls.subject?.value || '')
                            : '';
                        setOptions(controls.classRoom, payload.classes || [], 'Tất cả lớp', controls.classRoom?.value || '');
                        setOptions(controls.subject, payload.subjects || [], 'Tất cả môn học', selectedSubjectForDropdown);
                        setOptions(
                            controls.teacher,
                            payload.teachers || [],
                            'Chọn Giáo viên',
                            !isResettingFilters && hadTeacher ? (payload.selected_teacher_id || controls.teacher?.value || '') : ''
                        );

                        if (!isResettingFilters && (hadSubject || hadTeacher) && payload.selected_subject_id && controls.subject) {
                            controls.subject.value = payload.selected_subject_id;
                        }

                        if (!isResettingFilters && hadTeacher && payload.selected_teacher_id && controls.teacher) {
                            controls.teacher.value = payload.selected_teacher_id;
                        }
                    };

                    const refreshMatrix = async () => {
                        const payload = await requestJson(urls.matrix, params());
                        renderMatrix(payload);
                        if (controls.subject?.value) {
                            refreshCascade().catch(console.error);
                        }
                    };

                    const emptyMark = () => {
                        const span = document.createElement('span');
                        span.className = 'empty-mark text-gray-300 font-normal';
                        span.textContent = '—';
                        return span;
                    };

                    const detailByFamily = (details, family, index = 0) => {
                        const matches = details.filter((detail) => detail.family === family);
                        return matches[index] || null;
                    };

                    const markCell = (detail) => {
                        const td = document.createElement('td');
                        if (!detail || detail.value === null || detail.value === undefined || detail.value === '') {
                            td.appendChild(emptyMark());
                            return td;
                        }

                        const num = Number(detail.value);
                        td.textContent = isNaN(num) ? detail.value : num.toFixed(1);
                        return td;
                    };

                    const averageCell = (item) => {
                        const td = document.createElement('td');
                        if (item.average === null || item.average === undefined || item.average === '') {
                            td.appendChild(emptyMark());
                            return td;
                        }

                        const span = document.createElement('span');
                        span.className = 'average-mark';
                        const num = Number(item.average);
                        span.textContent = isNaN(num) ? item.average : num.toFixed(1);
                        td.appendChild(span);
                        return td;
                    };

                    const openLedger = (rowIndex) => {
                        const row = matrix.rows?.[rowIndex];
                        if (!row) {
                            return;
                        }

                        const selectedYear = (matrix.years || []).find((year) => String(year.id).trim() == String(matrix.filters?.school_year_id).trim());
                        modalTitle.textContent = `${(row.student.name || '').toUpperCase()} - ${row.student.student_code || ''}`;
                        modalSubtitle.textContent = `Lớp: ${matrix.class_context?.name || '-'} • Năm học: ${selectedYear?.name || '-'}`;
                        modalLedger.innerHTML = '';
                        const ledger = (row.ledger || []).filter((item) => String(item.assessment_type).trim().toUpperCase() !== 'NONE');

                        if (ledger.length === 0) {
                            const tr = document.createElement('tr');
                            const td = cell('td', 'Chưa có môn học được đánh giá.', 'text-muted');
                            td.colSpan = 7;
                            tr.appendChild(td);
                            modalLedger.appendChild(tr);
                        }

                        ledger.forEach((item) => {
                            const details = item.details || [];
                            const tr = document.createElement('tr');
                            tr.appendChild(cell('td', item.subject_name || '-', 'fw-semibold'));

                            if (item.assessment_type === 'ASSESSMENT') {
                                for (let i = 0; i < 5; i += 1) {
                                    const td = document.createElement('td');
                                    td.appendChild(emptyMark());
                                    tr.appendChild(td);
                                }
                                tr.appendChild(averageCell(item));
                                modalLedger.appendChild(tr);
                                return;
                            }

                            tr.appendChild(markCell(detailByFamily(details, 'oral')));
                            tr.appendChild(markCell(detailByFamily(details, 'fifteen', 0)));
                            tr.appendChild(markCell(detailByFamily(details, 'fifteen', 1)));
                            tr.appendChild(markCell(detailByFamily(details, 'midterm')));
                            tr.appendChild(markCell(detailByFamily(details, 'final')));
                            tr.appendChild(averageCell(item));
                            modalLedger.appendChild(tr);
                        });
                        modal.classList.add('active');
                        modal.setAttribute('aria-hidden', 'false');
                    };

                    modalClose?.addEventListener('click', () => {
                        modal.classList.remove('active');
                        modal.setAttribute('aria-hidden', 'true');
                    });
                    modal?.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            modal.classList.remove('active');
                            modal.setAttribute('aria-hidden', 'true');
                        }
                    });

                    controls.search?.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => refreshMatrix().catch(console.error), 300);
                    });

                    [controls.year, controls.grade, controls.classRoom, controls.semester].filter(Boolean).forEach((control) => {
                        control.addEventListener('change', () => {
                            refreshCascade()
                                .then(refreshMatrix)
                                .catch(console.error);
                        });
                    });

                    controls.subject?.addEventListener('change', () => {
                        refreshCascade()
                            .then(refreshMatrix)
                            .catch(console.error);
                    });

                    controls.teacher?.addEventListener('change', () => {
                        refreshCascade()
                            .then(refreshMatrix)
                            .catch(console.error);
                    });

                    controls.reset?.addEventListener('click', () => {
                        if (controls.search) controls.search.value = '';
                        if (controls.grade) controls.grade.value = '';
                        if (controls.classRoom) controls.classRoom.value = '';
                        if (controls.subject) controls.subject.value = '';
                        if (controls.teacher) controls.teacher.value = '';
                        if (controls.year) controls.year.value = initial.filters?.school_year_id || controls.year.value || '';
                        if (controls.semester) controls.semester.value = initial.filters?.semester_id || controls.semester.value || '';

                        isResettingFilters = true;
                        refreshCascade()
                            .then(refreshMatrix)
                            .catch(console.error)
                            .finally(() => {
                                isResettingFilters = false;
                            });
                    });

                    if (evalToggleEl) {
                        evalToggleEl.addEventListener('click', (e) => {
                            const btn = e.target.closest('[data-eval-tab]');
                            if (!btn) return;
                            const targetTab = String(btn.dataset.evalTab).trim().toUpperCase();
                            if (targetTab && activeEvaluationType !== targetTab) {
                                activeEvaluationType = targetTab;
                                refreshMatrix().catch(console.error);
                            }
                        });
                    }

                    if (grade10TabBtn) {
                        grade10TabBtn.addEventListener('click', () => {
                            if (activeEvaluationType !== 'GRADE_10') {
                                activeEvaluationType = 'GRADE_10';
                                refreshMatrix().catch(console.error);
                            }
                        });
                    }

                    if (assessmentTabBtn) {
                        assessmentTabBtn.addEventListener('click', () => {
                            if (activeEvaluationType !== 'ASSESSMENT') {
                                activeEvaluationType = 'ASSESSMENT';
                                refreshMatrix().catch(console.error);
                            }
                        });
                    }

                    renderMatrix(initial);
                    if (controls.classRoom?.value) {
                        refreshCascade().catch(console.error);
                    }
                })();
            </script>
        </div>
    @else

    <div class="score-entry-shell">
        <div class="score-entry-filter-card">
            <form method="GET" action="{{ route('scores.entry') }}" class="score-entry-filter" data-score-assignment-form>
                @if(! $isScoreAdmin && auth()->user()->isTeacher())
                    @php
                        $firstAssignment = $assignments->first();
                    @endphp
                    <input type="hidden" name="class_id" data-score-class-id value="{{ $firstAssignment?->class_id }}">
                    <input type="hidden" name="subject_id" data-score-subject-id value="{{ $firstAssignment?->subject_id }}">
                    <input type="hidden" name="semester_id" data-score-semester-id value="{{ $firstAssignment?->semester_id }}">
                    <div class="score-filter-field">
                        <label>Lớp</label>
                        <select class="form-select" data-score-assignment-class @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->classRoom?->name ?? 'Không rõ lớp' }}</option>
                            @empty
                                <option value="">Chưa có lớp</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Môn</label>
                        <select class="form-select" data-score-assignment-subject @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->subject?->name ?? 'Không rõ môn' }}</option>
                            @empty
                                <option value="">Chưa có môn</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Học kỳ</label>
                        <select class="form-select" data-score-assignment-semester @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->semester?->normalizedName() ?? 'Không rõ học kỳ' }}</option>
                            @empty
                                <option value="">Chưa có học kỳ</option>
                            @endforelse
                        </select>
                    </div>
                @else
                    <div class="score-filter-field compact">
                        <label>Khối</label>
                        <select name="grade_level" class="form-select" data-score-admin-grade>
                            <option value="">Tất cả khối</option>
                            @foreach($availableGrades as $grade)
                                <option value="{{ $grade }}">Khối {{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Lớp</label>
                        <select name="class_id" class="form-select" required data-score-admin-class>
                            <option value="">Chọn lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" data-grade="{{ $class->grade_level }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Chọn môn học</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Giáo viên</label>
                        <select name="teacher_id" class="form-select" data-score-admin-teacher>
                            <option value="">Tất cả giáo viên</option>
                            @foreach(($teachers ?? collect()) as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->teacher_code ? ' - ' . $teacher->teacher_code : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Học kỳ</label>
                        <select name="semester_id" class="form-select" required data-score-admin-semester>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>
                                    {{ $semester->normalizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button class="btn score-open-sheet-btn" @disabled((! $isScoreAdmin && auth()->user()->isTeacher()) && $assignments->isEmpty())>
                    <i class="bi bi-box-arrow-in-right"></i>
                    {{ $isScoreAdmin ? 'Tra cứu bảng điểm' : 'Mở bảng nhập' }}
                </button>
            </form>
        </div>

        @if(! $isScoreAdmin)
            <div class="score-assignment-strip">
                <div class="score-assignment-heading">
                    <span>Phân công của bạn</span>
                    <small>Chọn nhanh lớp và môn để bắt đầu nhập điểm</small>
                </div>
                <div class="score-assignment-badges">
                    @forelse($assignments as $assignment)
                        <a
                            href="{{ route('scores.entry', [
                                'class_id' => $assignment->class_id,
                                'subject_id' => $assignment->subject_id,
                                'semester_id' => $assignment->semester_id,
                            ]) }}"
                            class="score-assignment-badge"
                            data-score-assignment-badge="{{ $assignment->id }}"
                        >
                            <strong>{{ $assignment->classRoom?->name ?? 'Không rõ lớp' }}</strong>
                            <span>{{ $assignment->subject?->name ?? 'Không rõ môn' }} · {{ $assignment->semester?->normalizedName() ?? 'Không rõ học kỳ' }}</span>
                        </a>
                    @empty
                        <div class="score-entry-empty">
                            <i class="bi bi-inbox"></i>
                            Thầy/cô vui lòng chọn Lớp và Môn học để bắt đầu nhập điểm.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    <div class="score-entry-empty-panel">
        <i class="bi bi-clipboard-data"></i>
        <span>
            {{ $isScoreAdmin
                ? 'Vui lòng lựa chọn Khối, Lớp và Môn học để tiến hành tra cứu bảng điểm số.'
                : 'Thầy/cô vui lòng chọn Lớp và Môn học để bắt đầu nhập điểm.' }}
        </span>
    </div>

    @if($isScoreAdmin)
        <script type="application/json" id="score-admin-assignments">
            {!! $assignments->map(fn ($assignment) => [
                'teacher_id' => (string) $assignment->teacher_id,
                'class_id' => (string) $assignment->class_id,
                'subject_id' => (string) $assignment->subject_id,
                'semester_id' => (string) $assignment->semester_id,
            ])->values()->toJson() !!}
        </script>
    @endif

    <script>
        document.querySelectorAll('[data-score-assignment-form]').forEach((form) => {
            const selects = [
                form.querySelector('[data-score-assignment-class]'),
                form.querySelector('[data-score-assignment-subject]'),
                form.querySelector('[data-score-assignment-semester]'),
            ].filter(Boolean);

            if (selects.length) {
                const classInput = form.querySelector('[data-score-class-id]');
                const subjectInput = form.querySelector('[data-score-subject-id]');
                const semesterInput = form.querySelector('[data-score-semester-id]');

                const syncAssignment = (source) => {
                    const selectedId = source?.value || selects[0]?.value || '';

                    selects.forEach((select) => {
                        select.value = selectedId;
                    });

                    const option = selects[0]?.selectedOptions[0];
                    classInput.value = option?.dataset.classId || '';
                    subjectInput.value = option?.dataset.subjectId || '';
                    semesterInput.value = option?.dataset.semesterId || '';
                };

                selects.forEach((select) => {
                    select.addEventListener('change', () => syncAssignment(select));
                });

                syncAssignment(selects[0]);
            }

            const gradeSelect = form.querySelector('[data-score-admin-grade]');
            const classSelect = form.querySelector('[data-score-admin-class]');
            const subjectSelect = form.querySelector('[name="subject_id"]');
            const teacherSelect = form.querySelector('[data-score-admin-teacher]');
            const semesterSelect = form.querySelector('[data-score-admin-semester]');
            const assignmentPayload = document.getElementById('score-admin-assignments')?.textContent || '[]';
            const adminAssignments = JSON.parse(assignmentPayload);

            const syncAdminFilters = () => {
                if (! classSelect || ! subjectSelect) {
                    return;
                }

                const grade = gradeSelect?.value || '';
                const teacherId = teacherSelect?.value || '';
                const semesterId = semesterSelect?.value || '';
                const scopedAssignments = teacherId
                    ? adminAssignments.filter((assignment) => assignment.teacher_id === teacherId && (! semesterId || assignment.semester_id === semesterId))
                    : [];
                const allowedClassIds = new Set(scopedAssignments.map((assignment) => assignment.class_id));
                const allowedSubjectIds = new Set(scopedAssignments.map((assignment) => assignment.subject_id));

                Array.from(classSelect.options).forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    const matchesGrade = grade === '' || option.dataset.grade === grade;
                    const matchesTeacher = ! teacherId || allowedClassIds.has(option.value);
                    option.hidden = ! (matchesGrade && matchesTeacher);
                });

                Array.from(subjectSelect.options).forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = Boolean(teacherId) && ! allowedSubjectIds.has(option.value);
                });

                if (classSelect.selectedOptions[0]?.hidden) {
                    classSelect.value = '';
                }

                if (subjectSelect.selectedOptions[0]?.hidden) {
                    subjectSelect.value = '';
                }
            };

            if (gradeSelect && classSelect) {
                [gradeSelect, teacherSelect, semesterSelect].filter(Boolean).forEach((select) => {
                    select.addEventListener('change', syncAdminFilters);
                });
                syncAdminFilters();
            }
        });
    </script>
    @endif
@endif
@endsection
