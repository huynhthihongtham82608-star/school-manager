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
        max-width: 448px;
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
                    <div class="score-formula-text">
                        Nhà trường đang áp dụng hệ số W1 = {{ $scoreSetting->weight_gdtx }} cho các cột đánh giá thường xuyên, W2 = {{ $scoreSetting->weight_dggk }} cho đánh giá giữa kỳ và W3 = {{ $scoreSetting->weight_dgck }} cho đánh giá cuối kỳ. Điểm trung bình môn học kỳ được tính theo tổng điểm thành phần đã nhân hệ số chia cho tổng hệ số của các cột có điểm.
                    </div>
                    <div class="score-formula-box">
                        ĐTBmhk = {{ $scoreSetting->formulaLabel() }}. Kết quả được làm tròn 1 chữ số thập phân.
                    </div>
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
    @endphp

    <x-page-header
        :title="$isScoreAdmin ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số học sinh'"
        :subtitle="$isScoreAdmin
            ? 'Tra cứu, giám sát tiến độ nhập điểm và phê duyệt yêu cầu sửa đổi điểm số của giáo viên toàn trường.'
            : 'Giáo viên bộ môn nhập điểm theo các cột điểm do Admin cấu hình.'"
    >
        @if(auth()->user()->hasPermission('scores.manage'))
            <button type="button" class="score-config-shortcut-btn" data-bs-toggle="modal" data-bs-target="#scoreColumnConfigModal">
                ⚙️ Quản lý cấu hình cột điểm
            </button>
        @endif
    </x-page-header>

    @if(auth()->user()->hasPermission('scores.manage') && $scoreColumnConfig)
        <x-score-column-config-modal id="scoreColumnConfigModal" :config="$scoreColumnConfig" />
    @endif

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
@endsection
