@extends('layouts.app')
@section('title', auth()->user()->isAdmin() || auth()->user()->isStaff() ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số')

@section('content')
@php
    $isScoreAdmin = auth()->user()->isAdmin() || auth()->user()->isStaff();
@endphp

<x-page-header
    :title="$isScoreAdmin
        ? 'Bảng điểm tập trung - ' . $class->name . ' / ' . $subject->name . ' / ' . $semester->normalizedName()
        : 'Nhập điểm số - ' . $class->name . ' / ' . $subject->name . ' / ' . $semester->normalizedName()"
    :subtitle="$isScoreAdmin
        ? 'Chế độ giám sát chỉ xem. Admin tra cứu điểm số toàn trường, không nhập hoặc sửa điểm trực tiếp tại màn hình này.'
        : 'Giáo viên bộ môn chỉ nhập điểm vào các cột điểm do Admin cấu hình và đang mở nhập.'"
>
    <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">Quay lại</a>
</x-page-header>

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
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        @foreach($scoreColumns as $column)
                            <th style="min-width: 150px;">
                                <div>{{ $column->name }}</div>
                                <div class="text-muted small mt-1">{{ $column->typeLabel() }}</div>
                            </th>
                        @endforeach
                        <th>TB</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $header = $headers[$student->id] ?? null;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $student->student_code }}</td>
                        <td>{{ $student->name }}</td>
                        @foreach($scoreColumns as $column)
                            @php
                                $permission = $columnPermissions[$column->id] ?? ['editable' => false, 'reason' => 'Chỉ xem'];
                                $detail = $header?->details?->firstWhere('score_column_id', $column->id);
                                $fieldName = "scores[{$column->id}][{$student->id}]";
                                $fieldKey = "scores.{$column->id}.{$student->id}";
                                $displayValue = old($fieldKey, $detail?->value !== null ? rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.') : '');
                            @endphp
                            <td>
                                @if($isScoreAdmin)
                                    <span class="score-readonly-value {{ $displayValue === '' ? 'empty' : '' }}">{{ $displayValue !== '' ? $displayValue : '-' }}</span>
                                @else
                                    <input
                                        type="text"
                                        name="{{ $fieldName }}"
                                        class="form-control form-control-sm {{ $errors->has($fieldKey) ? 'is-invalid' : '' }}"
                                        value="{{ $displayValue }}"
                                        inputmode="decimal"
                                        pattern="^(10(\.0)?|[0-9](\.[0-9])?)$"
                                        data-score-input
                                        @disabled(! $permission['editable'])
                                    >
                                    @if($errors->has($fieldKey))
                                        <div class="invalid-feedback">{{ $errors->first($fieldKey) }}</div>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                        <td class="fw-semibold text-primary">{{ $header?->average !== null ? rtrim(rtrim(number_format($header->average, 2), '0'), '.') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $scoreColumns->count() + 3 }}"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
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
        form.addEventListener('submit', (event) => {
            let hasError = false;
            form.querySelectorAll('[data-score-input]:not(:disabled)').forEach((input) => {
                const value = input.value.trim();
                input.setCustomValidity('');

                if (value === '') {
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
