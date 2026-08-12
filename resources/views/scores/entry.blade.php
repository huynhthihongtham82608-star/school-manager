@extends('layouts.app')
@section('title', auth()->user()->isAdmin() || auth()->user()->isStaff() ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số')

@section('content')
@php
    $isScoreAdmin = auth()->user()->isAdmin() || auth()->user()->isStaff();
    $usesPassFailAssessment = $subject->usesPassFailAssessment();
    $renderRetestBadge = function ($detail) {
        if (! $detail?->is_retest || $detail->original_value === null) {
            return '';
        }

        $originalValue = rtrim(rtrim(number_format((float) $detail->original_value, 1, '.', ''), '0'), '.');
        $tooltip = 'Điểm gốc: ' . $originalValue . '. Cập nhật ngày: ' . ($detail->retest_updated_at?->format('d/m/Y') ?? '-');

        return '<span class="score-retest-badge" data-tooltip="' . e($tooltip) . '">Bù</span>';
    };
    $compactScoreColumnLabel = function ($column) {
        $normalized = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $column->name));

        if ($column->type === \App\Models\ScoreColumn::TYPE_MIDTERM || str_contains($normalized, 'giua')) {
            return 'Giữa Kỳ';
        }

        if ($column->type === \App\Models\ScoreColumn::TYPE_FINAL || str_contains($normalized, 'cuoi')) {
            return 'Cuối Kỳ';
        }

        if (str_contains($normalized, '15')) {
            return '15p';
        }

        return 'Miệng';
    };
@endphp

<style>
    .score-entry-header,
    .score-sheet {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #374151;
    }

    .score-sheet .form-control:disabled,
    .score-sheet .form-select:disabled {
        border-color: #e5e7eb;
        color: #6b7280;
        background: #f3f4f6;
        box-shadow: none;
        cursor: not-allowed;
        opacity: 1;
    }

    .score-sheet th,
    .score-sheet td {
        padding: .75rem;
        color: #4b5563;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
        white-space: nowrap;
    }

    .score-sheet .text-gray-650 {
        color: #4b5563 !important;
    }

    .score-sheet .form-control,
    .score-sheet .form-select,
    .score-sheet .score-readonly-value {
        color: #4b5563;
        font-size: .75rem;
        font-weight: 400;
    }

    .score-sheet th {
        color: #111827;
        font-weight: 400;
        background: #fff7ed;
    }

    @media (min-width: 768px) {
        .score-sheet th,
        .score-sheet td {
            font-size: .875rem;
        }
    }

    .score-sheet .score-student-code-col {
        width: 128px;
        min-width: 128px;
    }

    .score-sheet .score-student-name-col {
        width: 320px;
        min-width: 320px;
    }

    .score-sheet .score-student-code-cell,
    .score-sheet .score-student-name-cell {
        color: #4b5563;
        font-size: .75rem;
        font-weight: 400;
        white-space: nowrap;
        padding: .75rem;
        text-align: left;
    }

    @media (min-width: 768px) {
        .score-sheet .score-student-code-cell,
        .score-sheet .score-student-name-cell,
        .score-sheet .form-control,
        .score-sheet .form-select,
        .score-sheet .score-readonly-value {
            font-size: .875rem;
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

    .score-annual-col {
        background: rgba(255, 247, 237, .38) !important;
    }

    .score-annual-value {
        color: #ea580c;
        font-size: 1rem;
        font-weight: 700;
    }

    .score-annual-muted {
        color: #9ca3af;
        font-size: .92rem;
        font-weight: 400;
    }

    .score-formula-rule-btn {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .38rem .65rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        color: #c2410c;
        background: #fff7ed;
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.2;
        text-decoration: none;
        transition: all .18s ease;
        white-space: nowrap;
    }

    .score-formula-rule-btn:hover {
        color: #ea580c;
        background: #ffedd5;
    }

    .score-formula-rule-modal .modal-dialog {
        max-width: 576px;
    }

    .score-formula-rule-modal .modal-content {
        padding: 1.5rem;
        border: 0;
        border-radius: 8px;
        box-shadow: 0 24px 56px rgba(15, 23, 42, .28);
    }

    .score-formula-rule-title {
        display: flex;
        align-items: center;
        margin: 0;
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
    }

    .score-formula-rule-title::before {
        width: 4px;
        height: 16px;
        margin-right: .5rem;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .score-sheet .text-orange-400\/80 {
        color: rgba(251, 146, 60, .8) !important;
    }

    .score-sheet .hover\:text-orange-600:hover {
        color: #ea580c !important;
    }

    .score-entry-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        text-align: left;
    }

    .score-entry-title {
        margin: 0;
        color: #111827;
        font-size: 1.15rem;
        font-weight: 400;
        line-height: 1.35;
    }

    .score-entry-actions,
    .score-entry-actions .bulk-excel-actions {
        display: flex !important;
        align-items: center !important;
        gap: .5rem !important;
        flex-wrap: wrap !important;
        justify-content: flex-end !important;
    }

    @media (min-width: 640px) {
        .score-entry-actions,
        .score-entry-actions .bulk-excel-actions {
            flex-wrap: nowrap !important;
        }
    }

    .score-entry-action-btn,
    .score-entry-actions .bulk-excel-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #c2410c !important;
        background: #fff7ed !important;
        border: 1px solid #fed7aa !important;
        border-radius: 6px !important;
        padding: .5rem .75rem !important;
        font-size: .875rem !important;
        font-weight: 400 !important;
        line-height: 1.2 !important;
        text-decoration: none !important;
        transition: all .16s ease;
        cursor: pointer;
    }

    .score-entry-action-btn:hover,
    .score-entry-actions .bulk-excel-btn:hover {
        color: #9a3412 !important;
        background: #ffedd5 !important;
    }

    @media (max-width: 991.98px) {
        .score-entry-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .score-entry-actions {
            justify-content: flex-start !important;
        }
    }
</style>

<div class="score-entry-header">
    <div class="min-w-0">
        <h5 class="score-entry-title">
            {{ $isScoreAdmin ? 'Bảng điểm tập trung' : 'Nhập điểm số' }} — Lớp {{ $class->name }} / Môn {{ $subject->name }} / {{ $semester->normalizedName() }}
        </h5>
    </div>
    <div class="score-entry-actions flex items-center gap-2 flex-wrap sm:flex-nowrap">
        @if(! $isScoreAdmin)
            <button type="button" class="score-entry-action-btn" data-bs-toggle="modal" data-bs-target="#scoreFormulaRuleModal" title="Quy tắc tính điểm">
                Quy tắc tính điểm
            </button>
        @endif
        <x-bulk-excel-actions
            module="scores"
            :context="[
                'school_year_id' => $semester->school_year_id,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
            ]"
            :allow-import="! $isScoreAdmin"
        />
    </div>
</div>

@if(! $isScoreAdmin)
    <div class="modal fade score-formula-rule-modal" id="scoreFormulaRuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <h5 class="score-formula-rule-title">Quy tắc tính điểm số</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <x-score-formula-box :score-setting="$scoreSetting" class="mt-0" />
            </div>
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2">
        @forelse($scoreColumns as $column)
            @php
                $permission = $columnPermissions[$column->id] ?? ['editable' => false, 'reason' => 'Chỉ xem'];
            @endphp
            <span class="badge {{ $permission['editable'] ? 'bg-success' : 'bg-secondary' }}" title="{{ $permission['reason'] }}">
                {{ $column->name }}: {{ $permission['editable'] ? 'Đang mở' : 'Chỉ xem' }}
            </span>
        @empty
            <span class="badge bg-warning text-dark">Admin chưa cấu hình cột điểm cho môn, khối và năm học này.</span>
        @endforelse
    </div>
</div>

@if($isScoreAdmin)
    <div class="score-permission-alert mb-3">
        <i class="bi bi-shield-check me-1"></i>
        Chế độ quản trị: bảng điểm đang ở trạng thái chỉ xem để phục vụ tra cứu, giám sát và đối soát dữ liệu.
    </div>
@endif

<form method="POST" action="{{ route('scores.store') }}" data-score-entry-form>
    @csrf
    <input type="hidden" name="class_id" value="{{ $class->id }}">
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">
    <input type="hidden" name="semester_id" value="{{ $semester->id }}">
    <div class="card score-sheet">
        @unless($isScoreAdmin)
            <div class="score-permission-alert">
                <i class="bi bi-lightbulb me-1"></i>
                Lưu ý: Hệ thống chỉ mở quyền nhập/sửa điểm cho Giáo viên bộ môn được phân công giảng dạy lớp và môn học này trong thời hạn quy định.
            </div>
        @endunless
        <div class="table-responsive">
            <table class="table align-middle w-full table-fixed max-w-full overflow-hidden" data-admin-table-skip>
                <thead>
                    <tr>
                        <th class="score-student-code-col text-xs md:text-sm font-normal text-gray-650 whitespace-nowrap p-3 text-left">Mã HS</th>
                        <th class="score-student-name-col text-xs md:text-sm font-normal text-gray-650 whitespace-nowrap p-3 text-left">Họ và tên</th>
                        @foreach($scoreColumns as $column)
                            <th class="text-xs md:text-sm font-normal text-gray-650 text-left p-3 whitespace-nowrap" style="min-width: 92px;">
                                <div>{{ $compactScoreColumnLabel($column) }}<span class="text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs">↕</span></div>
                            </th>
                        @endforeach
                        <th class="text-xs md:text-sm font-normal text-gray-650 text-left p-3 whitespace-nowrap">TB<span class="text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs">↕</span></th>
                        @if($isScoreAdmin)
                            <th class="score-annual-col text-xs md:text-sm font-normal text-gray-650 text-left p-3 whitespace-nowrap">HK1<span class="text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs">↕</span></th>
                            <th class="score-annual-col text-xs md:text-sm font-normal text-gray-650 text-left p-3 whitespace-nowrap">HK2<span class="text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs">↕</span></th>
                            <th class="score-annual-col text-xs md:text-sm font-normal text-gray-650 text-left p-3 whitespace-nowrap">Cả Năm<span class="text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs">↕</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $header = $headers[$student->id] ?? null;
                    @endphp
                    <tr>
                        <td class="score-student-code-cell text-xs md:text-sm font-normal text-gray-650 whitespace-nowrap p-3 text-left">{{ $student->student_code }}</td>
                        <td class="score-student-name-cell text-xs md:text-sm font-normal text-gray-650 whitespace-nowrap p-3 text-left">{{ $student->name }}</td>
                        @foreach($scoreColumns as $column)
                            @php
                                $permission = $columnPermissions[$column->id] ?? ['editable' => false, 'reason' => 'Chỉ xem'];
                                $detail = $header?->details?->firstWhere('score_column_id', $column->id);
                                $fieldName = "scores[{$column->id}][{$student->id}]";
                                $fieldKey = "scores.{$column->id}.{$student->id}";
                                $displayValue = old($fieldKey, $detail?->value !== null ? rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.') : '');
                                if ($usesPassFailAssessment) {
                                    $displayValue = old($fieldKey, $detail?->value !== null ? ((float) $detail->value >= 0.5 ? 'pass' : 'fail') : '');
                                }
                                $passFailLabel = match ($displayValue) {
                                    'pass', '1' => 'Đạt',
                                    'fail', '0' => 'Chưa đạt',
                                    default => '',
                                };
                            @endphp
                            <td>
                                @if($isScoreAdmin)
                                    <span class="score-readonly-value {{ $displayValue === '' ? 'empty' : '' }}">
                                        @if($usesPassFailAssessment)
                                            {{ $passFailLabel !== '' ? $passFailLabel : '-' }}
                                        @else
                                            {{ $displayValue !== '' ? $displayValue : '-' }}
                                        @endif
                                        {!! $renderRetestBadge($detail) !!}
                                    </span>
                                @elseif($usesPassFailAssessment)
                                    <select
                                        name="{{ $fieldName }}"
                                        class="form-select form-select-sm {{ $errors->has($fieldKey) ? 'is-invalid' : '' }}"
                                        data-score-input
                                        data-score-type="{{ $column->type }}"
                                        @disabled(! $permission['editable'])
                                    >
                                        <option value="">Chọn</option>
                                        <option value="pass" @selected($displayValue === 'pass' || $displayValue === '1')>Đạt (Đ)</option>
                                        <option value="fail" @selected($displayValue === 'fail' || $displayValue === '0')>Chưa đạt (CĐ)</option>
                                    </select>
                                    @if($errors->has($fieldKey))
                                        <div class="invalid-feedback">{{ $errors->first($fieldKey) }}</div>
                                    @endif
                                    {!! $renderRetestBadge($detail) !!}
                                @else
                                    <input
                                        type="text"
                                        name="{{ $fieldName }}"
                                        class="form-control form-control-sm {{ $errors->has($fieldKey) ? 'is-invalid' : '' }}"
                                        value="{{ $displayValue }}"
                                        inputmode="decimal"
                                        pattern="^(10(\.0)?|[0-9](\.[0-9])?)$"
                                        data-score-input
                                        data-score-type="{{ $column->type }}"
                                        @disabled(! $permission['editable'])
                                    >
                                    @if($errors->has($fieldKey))
                                        <div class="invalid-feedback">{{ $errors->first($fieldKey) }}</div>
                                    @endif
                                    {!! $renderRetestBadge($detail) !!}
                                @endif
                            </td>
                        @endforeach
                        <td class="fw-semibold text-primary" data-row-average>{{ $header?->average !== null ? rtrim(rtrim(number_format($header->average, 1), '0'), '.') : '-' }}</td>
                        @if($isScoreAdmin)
                            @php
                                $annualAverage = $subjectAnnualAverages->get($student->id, ['hk1' => null, 'hk2' => null, 'year' => null]);
                                $formatAnnualAverage = fn ($value) => $value !== null ? number_format((float) $value, 1, '.', '') : null;
                            @endphp
                            <td class="score-annual-col">
                                @if($annualAverage['hk1'] !== null)
                                    <span class="score-annual-value">{{ $formatAnnualAverage($annualAverage['hk1']) }}</span>
                                @else
                                    <span class="score-annual-muted">-</span>
                                @endif
                            </td>
                            <td class="score-annual-col">
                                @if($annualAverage['hk2'] !== null)
                                    <span class="score-annual-value">{{ $formatAnnualAverage($annualAverage['hk2']) }}</span>
                                @else
                                    <span class="score-annual-muted">-</span>
                                @endif
                            </td>
                            <td class="score-annual-col">
                                @if($annualAverage['year'] !== null)
                                    <span class="score-annual-value">{{ $formatAnnualAverage($annualAverage['year']) }}</span>
                                @else
                                    <span class="score-annual-muted">-</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $scoreColumns->count() + ($isScoreAdmin ? 6 : 3) }}"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3 text-end">
        @if(! $isScoreAdmin && $canSubmitScores)
            <button class="btn btn-primary">Lưu điểm</button>
        @elseif(! $isScoreAdmin)
            <span class="text-muted">Bạn đang ở chế độ chỉ xem hoặc chưa có cột điểm nào được mở.</span>
        @else
            <span class="text-muted">Admin đang xem bảng điểm ở chế độ giám sát, không có thao tác lưu điểm.</span>
        @endif
    </div>
</form>

@unless($isScoreAdmin)
<script>
    document.querySelectorAll('[data-score-entry-form]').forEach((form) => {
        const scoreWeights = {
            regular: Number(@json($scoreSetting->weight_gdtx ?? 1)),
            midterm: Number(@json($scoreSetting->weight_dggk ?? 2)),
            final: Number(@json($scoreSetting->weight_dgck ?? 3)),
        };
        const usesPassFail = @json($usesPassFailAssessment);
        const weightFor = (type) => scoreWeights[type] || scoreWeights.regular;
        const recalculateRowAverage = (row) => {
            if (usesPassFail) {
                return;
            }

            let weightedSum = 0;
            let totalWeight = 0;
            row.querySelectorAll('[data-score-input]').forEach((input) => {
                const value = input.value.trim();
                if (value === '' || !/^(10(\.0)?|[0-9](\.[0-9])?)$/.test(value)) {
                    return;
                }

                const weight = weightFor(input.dataset.scoreType);
                weightedSum += Number(value) * weight;
                totalWeight += weight;
            });

            const target = row.querySelector('[data-row-average]');
            if (!target) {
                return;
            }

            target.textContent = totalWeight > 0 ? (weightedSum / totalWeight).toFixed(1) : '-';
        };

        form.querySelectorAll('[data-score-input]').forEach((input) => {
            input.addEventListener('blur', () => recalculateRowAverage(input.closest('tr')));
        });

        form.addEventListener('submit', (event) => {
            let hasError = false;
            form.querySelectorAll('[data-score-input]:not(:disabled)').forEach((input) => {
                const value = input.value.trim();
                input.setCustomValidity('');

                if (usesPassFail || value === '') {
                    return;
                }

                if (!/^(10(\.0)?|[0-9](\.[0-9])?)$/.test(value)) {
                    input.setCustomValidity('Điểm phải là số từ 0 đến 10 và tối đa 1 chữ số thập phân.');
                    hasError = true;
                }
            });

            if (hasError) {
                event.preventDefault();
                form.querySelector('[data-score-input]:invalid')?.reportValidity();
            }
        });
    });
</script>
@endunless
@endsection
