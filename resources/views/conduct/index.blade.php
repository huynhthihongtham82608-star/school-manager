@extends('layouts.app')
@section('title', 'Hạnh kiểm')

@section('content')
@php
    $conductLabels = \App\Models\Conduct::LEVELS;
    $conductBadgeClasses = [
        'excellent' => 'conduct-level-badge excellent',
        'good' => 'conduct-level-badge good',
        'average' => 'conduct-level-badge average',
        'weak' => 'conduct-level-badge weak',
    ];
    $conductQuickComments = [
        'Chăm ngoan, có ý thức kỷ luật tốt.',
        'Có tinh thần học tập và rèn luyện ổn định.',
        'Cần cố gắng chuyên cần hơn.',
    ];
@endphp

<style>
    .conduct-toolbar,
    .conduct-matrix-table,
    .conduct-summary-bar,
    .conduct-save-footer {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #4b5563;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
    }

    .conduct-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .75rem;
        width: 100%;
        padding: .9rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    @media (min-width: 992px) {
        .conduct-toolbar {
            flex-wrap: nowrap;
        }
    }

    .conduct-toolbar-field {
        min-width: 180px;
        flex: 0 0 auto;
    }

    .conduct-toolbar-field.search {
        min-width: 260px;
        flex: 1 1 260px;
    }

    .conduct-toolbar label {
        color: #374151;
        font-size: .88rem;
        font-weight: 400;
    }

    .conduct-search-wrap {
        position: relative;
    }

    .conduct-search-wrap i {
        position: absolute;
        left: .75rem;
        top: 50%;
        color: #9ca3af;
        transform: translateY(-50%);
    }

    .conduct-search-wrap .form-control {
        padding-left: 2.15rem;
        border-color: rgba(255, 237, 213, .5);
        border-radius: 8px;
        background: rgba(255, 247, 237, .1);
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .conduct-toolbar .form-select,
    .conduct-toolbar .form-control,
    .conduct-note-input,
    .conduct-template-select {
        border-color: #e5e7eb;
        border-radius: 8px;
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .conduct-toolbar .form-select:focus,
    .conduct-toolbar .form-control:focus,
    .conduct-note-input:focus,
    .conduct-template-select:focus {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 .25rem rgba(255, 237, 213, .55);
    }

    .conduct-matrix-table {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        table-layout: fixed;
        overflow: hidden;
    }

    .conduct-matrix-table th,
    .conduct-matrix-table td {
        color: #4b5563;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .conduct-matrix-table th {
        color: #111827;
        font-weight: 500;
        background: #fff;
    }

    .conduct-student-code {
        color: #4b5563;
        font-size: .875rem;
        font-weight: 400;
    }

    .conduct-student-name {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .conduct-attendance-summary {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .25rem;
    }

    .conduct-level-group {
        width: 100%;
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: .25rem;
    }

    .conduct-level-option {
        flex: 1 1 0;
        min-width: 0;
        position: relative;
        margin: 0;
        cursor: pointer;
    }

    .conduct-level-option input {
        position: absolute;
        inset: 0;
        opacity: 0;
        pointer-events: none;
    }

    .conduct-level-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-width: 0;
        padding: .34rem .32rem;
        border-radius: 8px;
        font-size: clamp(.68rem, .78vw, .82rem);
        font-weight: 400;
        line-height: 1.2;
        white-space: nowrap;
        border: 1px solid transparent;
        transition: all .16s ease;
    }

    .conduct-attendance-badge {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .125rem .5rem;
        border: 1px solid rgba(229, 231, 235, .6);
        border-radius: 999px;
        color: #6b7280;
        background: #f9fafb;
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.25;
        white-space: nowrap;
    }

    .conduct-level-badge.excellent {
        color: #15803d;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .conduct-level-badge.good {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .conduct-level-badge.average {
        color: #c2410c;
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .conduct-level-badge.weak {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .conduct-level-option input + .conduct-level-badge {
        color: #9ca3af !important;
        background: #f3f4f6 !important;
        border-color: #e5e7eb !important;
        border-radius: 8px;
        font-weight: 400;
    }

    .conduct-level-option input:checked + .conduct-level-badge {
        color: #fff !important;
        background: #ea580c !important;
        border-color: #ea580c !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .12);
        font-weight: 500;
    }

    .conduct-level-option input:disabled + .conduct-level-badge {
        color: #6b7280;
        background: #f3f4f6;
        border-color: #e5e7eb;
        cursor: not-allowed;
    }

    .conduct-level-option input:disabled:checked + .conduct-level-badge {
        color: #fff !important;
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    .conduct-warning-cell {
        border: 1px solid #ef4444 !important;
        background: #fef2f2 !important;
    }

    .conduct-warning-text {
        display: block;
        margin-top: .35rem;
        color: #b91c1c;
        font-size: .78rem;
        font-weight: 400;
        white-space: normal;
    }

    .conduct-suggestion-badge {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        padding: .24rem .55rem;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        color: #9a3412;
        background: rgba(255, 247, 237, .65);
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.25;
        white-space: normal;
    }

    .conduct-suggestion-muted {
        color: #9ca3af;
        font-size: .75rem;
        font-weight: 400;
    }

    .conduct-comment-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .4rem;
        align-items: start;
        width: 100%;
        min-width: 0;
    }

    .conduct-note-input,
    .conduct-template-select {
        width: 100%;
        min-width: 0;
        font-size: .78rem;
        font-weight: 400;
        text-align: left;
    }

    .conduct-note-input {
        min-height: 38px;
        resize: vertical;
    }

    .conduct-save-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: .9rem;
    }

    .conduct-save-count {
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .conduct-save-btn {
        color: #fff;
        background: #ea580c;
        border-color: #ea580c;
        border-radius: 8px;
        padding: .62rem 1.35rem;
        font-weight: 500;
    }

    .conduct-save-btn:hover,
    .conduct-save-btn:focus {
        color: #fff;
        background: #c2410c;
        border-color: #c2410c;
    }

    .conduct-comment-tooltip {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        color: #ea580c;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        background: #fff7ed;
        cursor: pointer;
    }

    .conduct-comment-tooltip:hover,
    .conduct-comment-tooltip:focus {
        color: #c2410c;
        background: #ffedd5;
    }

    .conduct-comment-bubble {
        position: absolute;
        right: 0;
        bottom: calc(100% + 9px);
        z-index: 30;
        width: max-content;
        max-width: 360px;
        padding: .65rem .75rem;
        color: #374151;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .14);
        font-size: .88rem;
        font-weight: 400;
        line-height: 1.45;
        text-align: left;
        white-space: normal;
        overflow-wrap: anywhere;
        opacity: 0;
        pointer-events: none;
        transform: translateY(5px);
        transition: opacity .16s ease, transform .16s ease;
    }

    .conduct-comment-tooltip:hover .conduct-comment-bubble,
    .conduct-comment-tooltip:focus .conduct-comment-bubble {
        opacity: 1;
        transform: translateY(0);
    }
</style>

@if(auth()->user()->isStudent() || auth()->user()->isParent())
    @php
        $conductBadges = [
            'excellent' => 'bg-success',
            'good' => 'bg-primary',
            'average' => 'bg-warning text-dark',
            'weak' => 'bg-secondary',
        ];
        $latestConduct = $studentConductRecords->first();
    @endphp
    <x-page-header
        title="Hạnh kiểm"
        :subtitle="auth()->user()->isParent()
            ? 'Xem xếp loại hạnh kiểm và nhận xét của học sinh đang chọn.'
            : 'Chỉ hiển thị dữ liệu hạnh kiểm của học sinh đang đăng nhập.'"
    />

    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row gap-3 justify-content-between">
            <div>
                <div class="text-muted small">Học sinh</div>
                <div class="fw-bold">{{ $viewStudent?->student_code }} - {{ $viewStudent?->name }}</div>
            </div>
            <div>
                <div class="text-muted small">Lớp</div>
                <div class="fw-bold">{{ $viewStudent?->classRoom?->name ?? 'Chưa phân lớp' }}</div>
            </div>
        </div>
    </div>

    @if($latestConduct)
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="student-stat-card h-100">
                    <span class="student-stat-icon text-success"><i class="bi bi-award"></i></span>
                    <div>
                        <div class="student-stat-label">Xếp loại gần nhất</div>
                        <div class="student-stat-value">
                            <span class="badge {{ $conductBadges[$latestConduct->conduct_level] ?? 'bg-light text-dark border' }}">
                                {{ $conductLabels[$latestConduct->conduct_level] ?? $latestConduct->conduct_level }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header">Nhận xét của giáo viên chủ nhiệm</div>
                    <div class="card-body">
                        <p class="mb-0">{{ $latestConduct->comment ?: 'Chưa có nhận xét chi tiết.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Lịch sử hạnh kiểm</div>
        <div class="table-responsive">
            <table class="table w-full table-fixed max-w-full overflow-hidden" data-admin-table-skip>
                <thead>
                    <tr>
                        <th>Năm học</th>
                        <th>Học kỳ</th>
                        <th>Lớp</th>
                        <th>Hạnh kiểm</th>
                        <th>Nhận xét</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($studentConductRecords as $record)
                    <tr>
                        <td>{{ $record->schoolYear?->name ?? $record->semester?->schoolYear?->name ?? '-' }}</td>
                        <td class="fw-semibold">{{ $record->semester?->normalizedName() ?? '-' }}</td>
                        <td>{{ $record->classRoom?->name ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $conductBadges[$record->conduct_level] ?? 'bg-light text-dark border' }}">
                                {{ $conductLabels[$record->conduct_level] ?? $record->conduct_level }}
                            </span>
                        </td>
                        <td>{{ $record->comment ?: 'Không có nhận xét' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state"><i class="bi bi-clipboard-check"></i>Chưa có dữ liệu hạnh kiểm trong học kỳ này.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    @php
        $currentUser = auth()->user();
        $isAdminConductView = $currentUser->isAdmin() || $currentUser->isStaff();
        $rowLevels = $students->mapWithKeys(function ($student) use ($records) {
            $record = $records[$student->id] ?? null;
            return [$student->id => $record?->conduct_level ?? \App\Models\Conduct::LEVEL_GOOD];
        });
        $conductCounts = collect(array_keys($conductLabels))->mapWithKeys(fn ($level) => [$level => $rowLevels->filter(fn ($value) => $value === $level)->count()]);
    @endphp

    <x-page-header
        title="Kết quả rèn luyện"
        subtitle="Ma trận hạnh kiểm học kỳ liên kết trực tiếp với dữ liệu chuyên cần."
    >
        <x-bulk-excel-actions
            module="conduct"
            :context="[
                'school_year_id' => $selectedYearId,
                'class_id' => $selectedClass?->id,
                'semester_id' => $selectedSemester?->id,
            ]"
            :allow-import="! $isAdminConductView && ! ($readOnly ?? false)"
        />
    </x-page-header>

    <form method="GET" action="{{ route('conduct.index') }}" class="conduct-toolbar mb-3">
        <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
        @if(! $isAdminConductView && $selectedClass)
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
        @endif
        <div class="conduct-toolbar-field search">
            <label class="form-label">Tìm kiếm</label>
            <div class="conduct-search-wrap">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control" placeholder="Tìm mã HS hoặc họ tên" data-conduct-search>
            </div>
        </div>
        @if($isAdminConductView)
            <div class="conduct-toolbar-field">
                <label class="form-label">Lớp</label>
                <select name="class_id" class="form-select" required>
                    <option value="">Chọn lớp</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClass && $class->id === $selectedClass->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        @elseif($selectedClass)
            <div class="conduct-toolbar-field">
                <label class="form-label">Lớp chủ nhiệm</label>
                <input type="text" class="form-control" value="{{ $selectedClass->name }}" readonly>
            </div>
        @endif
        <div class="conduct-toolbar-field">
            <label class="form-label">Học kỳ</label>
            <select name="semester_id" class="form-select" required>
                <option value="">Chọn học kỳ</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemester && $semester->id === $selectedSemester->id)>{{ $semester->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="conduct-toolbar-field" style="min-width: 52px;">
            <button class="btn btn-primary w-100" title="Mở danh sách">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    @if($selectedClass && $selectedSemester)
        <div class="conduct-summary-bar mb-3">
            @foreach($conductLabels as $level => $label)
                <div class="conduct-summary-card {{ $level }}">
                    <i class="bi {{ $level === 'weak' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }}"></i>
                    <span>{{ $label }}</span>
                    <strong>{{ $conductCounts[$level] ?? 0 }}</strong>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('conduct.store') }}" data-conduct-form>
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
            <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
            <div class="card">
                <div class="table-responsive">
                    <table class="table conduct-matrix-table w-full table-fixed max-w-full overflow-hidden mb-0" data-admin-table-skip>
                        <thead>
                            <tr>
                                <th style="width: 20%;">Học sinh</th>
                                <th style="width: 16%;">Chuyên cần</th>
                                <th style="width: 32%;">Xếp loại rèn luyện</th>
                                <th style="width: 14%;">Gợi ý hệ thống</th>
                                <th style="width: 18%;">Lời phê / Nhận xét của giáo viên</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            @php
                                $record = $records[$student->id] ?? null;
                                $attendance = $attendanceSummaries[$student->id] ?? [
                                    'excused' => 0,
                                    'absent' => 0,
                                    'late' => 0,
                                    'semester_unexcused_absent' => 0,
                                    'school_year_unexcused_absent' => 0,
                                    'force_weak' => false,
                                ];
                                $forcedWeak = (bool) ($attendance['force_weak'] ?? false);
                                $systemSuggestion = $conductSuggestions[$student->id] ?? ['text' => null, 'average' => null];
                                $selectedLevel = old("conduct.{$student->id}.conduct_level", $record?->conduct_level ?? \App\Models\Conduct::LEVEL_GOOD);
                                $commentValue = old("conduct.{$student->id}.comment", $record?->comment);
                                $searchText = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim($student->student_code . ' ' . $student->name)));
                            @endphp
                            <tr
                                data-conduct-row
                                data-student-id="{{ $student->id }}"
                                data-absent-semester="{{ (int) ($attendance['semester_unexcused_absent'] ?? 0) }}"
                                data-absent-year="{{ (int) ($attendance['school_year_unexcused_absent'] ?? 0) }}"
                                data-semester-limit="{{ (int) ($absencePolicy['semester_unexcused_limit'] ?? 22) }}"
                                data-year-limit="{{ (int) ($absencePolicy['school_year_unexcused_limit'] ?? 45) }}"
                                data-conduct-search-text="{{ $searchText }}"
                            >
                                <td>
                                    <div class="conduct-student-code">{{ $student->student_code }}</div>
                                    <div class="conduct-student-name">{{ $student->name }}</div>
                                </td>
                                <td>
                                    <div class="conduct-attendance-summary">
                                        <span class="conduct-attendance-badge">{{ (int) ($attendance['excused'] ?? 0) }} Có phép</span>
                                        <span class="conduct-attendance-badge">{{ (int) ($attendance['absent'] ?? 0) }} Không phép</span>
                                        <span class="conduct-attendance-badge">{{ (int) ($attendance['late'] ?? 0) }} Đi muộn</span>
                                    </div>
                                </td>
                                <td @class(['conduct-warning-cell' => $forcedWeak]) data-conduct-level-cell>
                                    @if($canEditConduct)
                                        <div class="conduct-level-group">
                                            @foreach($conductLabels as $level => $label)
                                                <label class="conduct-level-option">
                                                    <input
                                                        type="radio"
                                                        name="conduct[{{ $student->id }}][conduct_level]"
                                                        value="{{ $level }}"
                                                        @checked($selectedLevel === $level)
                                                        data-conduct-level
                                                    >
                                                    <span class="{{ $conductBadgeClasses[$level] ?? 'conduct-level-badge' }}">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <span class="conduct-warning-text {{ $forcedWeak ? '' : 'd-none' }}" data-conduct-warning>
                                            ⚠️ Cảnh báo: Học sinh vắng quá số buổi quy định
                                        </span>
                                    @else
                                        <span class="{{ $conductBadgeClasses[$selectedLevel] ?? 'conduct-level-badge empty' }}">
                                            {{ $conductLabels[$selectedLevel] ?? $selectedLevel }}
                                        </span>
                                        @if($forcedWeak)
                                            <span class="conduct-warning-text">⚠️ Cảnh báo: Học sinh vắng quá số buổi quy định</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-left">
                                    @if($systemSuggestion['text'] ?? null)
                                        <span class="conduct-suggestion-badge" title="Điểm TB: {{ $systemSuggestion['average'] !== null ? number_format((float) $systemSuggestion['average'], 2) : '-' }}">
                                            {{ $systemSuggestion['text'] }}
                                        </span>
                                    @else
                                        <span class="conduct-suggestion-muted">Chưa có gợi ý</span>
                                    @endif
                                </td>
                                <td>
                                    @if($canEditConduct)
                                        <div class="conduct-comment-grid">
                                            <textarea
                                                name="conduct[{{ $student->id }}][comment]"
                                                class="form-control conduct-note-input"
                                                rows="2"
                                                placeholder="Nhập lời phê / nhận xét..."
                                                data-conduct-comment
                                            >{{ $commentValue }}</textarea>
                                            <select class="form-select conduct-template-select" data-conduct-template>
                                                <option value="">Mẫu lời phê</option>
                                                @foreach($conductQuickComments as $quickComment)
                                                    <option value="{{ $quickComment }}">{{ $quickComment }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @else
                                        @if(trim((string) $commentValue) !== '')
                                            <span class="conduct-comment-tooltip" tabindex="0" aria-label="{{ $commentValue }}">
                                                <i class="bi bi-journal-text"></i>
                                                <span class="conduct-comment-bubble">{{ $commentValue }}</span>
                                            </span>
                                        @else
                                            <span class="text-muted small">Chưa có nhận xét</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="conduct-save-footer">
                <span class="conduct-save-count" data-conduct-visible-count>
                    Hiển thị {{ $students->count() }} trong tổng số {{ $students->count() }} học sinh
                </span>
                @if($canEditConduct)
                    <button class="btn conduct-save-btn">
                        <i class="bi bi-save me-1"></i>
                        Lưu sổ hạnh kiểm học kỳ
                    </button>
                @else
                    <span class="text-muted small">Bảng hạnh kiểm đang ở chế độ chỉ xem.</span>
                @endif
            </div>
        </form>
    @endif
@endif

<script>
    function validateConductRanking(studentId) {
        const row = Array.from(document.querySelectorAll('[data-conduct-row]'))
            .find((item) => item.dataset.studentId === String(studentId));

        if (!row) {
            return false;
        }

        const absentSemester = Number.parseInt(row.dataset.absentSemester || '0', 10);
        const absentYear = Number.parseInt(row.dataset.absentYear || '0', 10);
        const semesterLimit = Number.parseInt(row.dataset.semesterLimit || '22', 10);
        const yearLimit = Number.parseInt(row.dataset.yearLimit || '45', 10);
        const mustLock = absentSemester > semesterLimit || absentYear > yearLimit;
        const levelCell = row.querySelector('[data-conduct-level-cell]');
        const warning = row.querySelector('[data-conduct-warning]');

        levelCell?.classList.toggle('conduct-warning-cell', mustLock);
        warning?.classList.toggle('d-none', !mustLock);

        return mustLock;
    }

    document.querySelectorAll('[data-conduct-row]').forEach((row) => {
        validateConductRanking(row.dataset.studentId);
    });

    function syncConductLevelButtons(row) {
        if (!row) {
            return;
        }

        row.querySelectorAll('[data-conduct-level]').forEach((input) => {
            const badge = input.closest('.conduct-level-option')?.querySelector('.conduct-level-badge');
            if (!badge) {
                return;
            }

            badge.classList.toggle('bg-orange-600', input.checked);
            badge.classList.toggle('text-white', input.checked);
            badge.classList.toggle('font-medium', input.checked);
            badge.classList.toggle('shadow-sm', input.checked);
            badge.classList.toggle('border-orange-600', input.checked);
            badge.classList.toggle('bg-gray-100', !input.checked);
            badge.classList.toggle('text-gray-400', !input.checked);
            badge.classList.toggle('border-gray-200', !input.checked);
        });
    }

    document.querySelectorAll('[data-conduct-row]').forEach((row) => {
        syncConductLevelButtons(row);
    });

    document.querySelectorAll('[data-conduct-level]').forEach((input) => {
        input.addEventListener('change', () => {
            syncConductLevelButtons(input.closest('[data-conduct-row]'));
        });
    });

    document.querySelectorAll('[data-conduct-template]').forEach((select) => {
        select.addEventListener('change', () => {
            const input = select.closest('tr')?.querySelector('[data-conduct-comment]');

            if (input && select.value) {
                input.value = select.value;
                input.focus();
            }

            select.value = '';
        });
    });

    document.querySelectorAll('[data-conduct-search]').forEach((input) => {
        const rows = Array.from(document.querySelectorAll('[data-conduct-row]'));
        const countLabel = document.querySelector('[data-conduct-visible-count]');
        let debounceTimer = null;
        const normalizeText = (value) => value.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        const applyFilter = () => {
            const keyword = normalizeText(input.value.trim());
            let visibleCount = 0;

            rows.forEach((row) => {
                const matched = !keyword || (row.dataset.conductSearchText || '').includes(keyword);
                row.hidden = !matched;

                if (matched) {
                    visibleCount += 1;
                }
            });

            if (countLabel) {
                countLabel.textContent = `Hiển thị ${visibleCount} trong tổng số ${rows.length} học sinh`;
            }
        };

        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(applyFilter, 300);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    });
</script>
@endsection
