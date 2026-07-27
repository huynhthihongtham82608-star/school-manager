@extends('layouts.app')
@section('title', 'Hạnh kiểm')

@section('content')
@if(auth()->user()->isStudent() || auth()->user()->isParent())
    @php
        $conductLabels = \App\Models\Conduct::LEVELS;
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
            <table class="table">
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
    $conductLabels = \App\Models\Conduct::LEVELS;
    $conductBadgeClasses = [
        'excellent' => 'conduct-level-badge excellent',
        'good' => 'conduct-level-badge good',
        'average' => 'conduct-level-badge average',
        'weak' => 'conduct-level-badge weak',
    ];
    $conductSummaryMeta = [
        'excellent' => ['icon' => 'bi-check-circle-fill', 'label' => $conductLabels['excellent'] ?? 'Tốt'],
        'good' => ['icon' => 'bi-patch-check-fill', 'label' => $conductLabels['good'] ?? 'Khá'],
        'average' => ['icon' => 'bi-dash-circle-fill', 'label' => $conductLabels['average'] ?? 'Đạt'],
        'weak' => ['icon' => 'bi-exclamation-circle-fill', 'label' => $conductLabels['weak'] ?? 'Chưa đạt'],
    ];
    $conductCounts = collect(array_keys($conductSummaryMeta))
        ->mapWithKeys(fn ($level) => [$level => $records->where('conduct_level', $level)->count()]);
@endphp
<x-page-header
    title="Nhập hạnh kiểm"
    subtitle="Chọn lớp và học kỳ để cập nhật xếp loại hạnh kiểm và nhận xét của giáo viên chủ nhiệm."
/>

<form method="GET" class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Lớp</label>
        <select name="class_id" class="form-select" required>
            <option value="">--Chọn lớp--</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected($selectedClass && $class->id === $selectedClass->id)>{{ $class->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Học kỳ</label>
        <select name="semester_id" class="form-select" required>
            <option value="">--Chọn học kỳ--</option>
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected($selectedSemester && $semester->id === $selectedSemester->id)>{{ $semester->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 align-self-end">
        <button class="btn btn-primary w-100">Mở danh sách</button>
    </div>
</form>

@if($selectedClass && $selectedSemester)
    <div class="conduct-summary-bar mb-3">
        @foreach($conductSummaryMeta as $level => $meta)
            <div class="conduct-summary-card {{ $level }}">
                <i class="bi {{ $meta['icon'] }}"></i>
                <span>{{ $meta['label'] }}</span>
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
                <table class="table conduct-entry-table">
                    <thead>
                        <tr>
                            <th>Mã HS</th>
                            <th>Họ tên</th>
                            <th>Hạnh kiểm</th>
                            <th>Nhận xét</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($students as $student)
                        @php
                            $record = $records[$student->id] ?? null;
                            $selectedLevel = old("conduct.{$student->id}.conduct_level", $record?->conduct_level);
                            $commentValue = old("conduct.{$student->id}.comment", $record?->comment);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $student->student_code }}</td>
                            <td>{{ $student->name }}</td>
                            <td>
                                @if($canEditConduct)
                                    <select name="conduct[{{ $student->id }}][conduct_level]" class="form-select form-select-sm" data-conduct-level>
                                        <option value="" @selected($selectedLevel === null || $selectedLevel === '')>Chưa xếp loại</option>
                                        @foreach(\App\Models\Conduct::LEVELS as $k => $label)
                                            <option value="{{ $k }}" @selected($selectedLevel === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    @if($selectedLevel)
                                        <span class="{{ $conductBadgeClasses[$selectedLevel] ?? 'conduct-level-badge empty' }}">{{ $conductLabels[$selectedLevel] ?? $selectedLevel }}</span>
                                    @else
                                        <span class="conduct-readonly-text">Chưa xếp loại</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($canEditConduct)
                                    <input
                                        type="text"
                                        name="conduct[{{ $student->id }}][comment]"
                                        class="form-control form-control-sm conduct-note-input @error("conduct.{$student->id}.comment") is-invalid @enderror"
                                        value="{{ $commentValue }}"
                                        placeholder="Nhập nhận xét..."
                                        data-conduct-comment
                                    >
                                @else
                                    @if(trim((string) $commentValue) !== '')
                                        <span class="conduct-comment-tooltip" tabindex="0" aria-label="{{ $commentValue }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" title="{{ $commentValue }}">
                                            <i class="bi bi-journal-text"></i>
                                            <span class="conduct-comment-bubble">{{ $commentValue }}</span>
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 text-end">
            @if($canEditConduct)
                <button class="btn btn-primary">Lưu hạnh kiểm</button>
            @else
                <span class="text-muted small">Bảng hạnh kiểm đang ở chế độ chỉ xem.</span>
            @endif
        </div>
    </form>
@endif
@endif

<script>
    document.querySelectorAll('[data-conduct-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let firstInvalid = null;

            form.querySelectorAll('tbody tr').forEach((row) => {
                const level = row.querySelector('[data-conduct-level]');
                const comment = row.querySelector('[data-conduct-comment]');

                if (!level || !comment) {
                    return;
                }

                const isInvalid = level.value.trim() !== '' && comment.value.trim() === '';
                comment.classList.toggle('is-invalid', isInvalid);

                if (isInvalid && !firstInvalid) {
                    firstInvalid = comment;
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });

        form.querySelectorAll('[data-conduct-level], [data-conduct-comment]').forEach((field) => {
            field.addEventListener('input', () => {
                const row = field.closest('tr');
                const level = row?.querySelector('[data-conduct-level]');
                const comment = row?.querySelector('[data-conduct-comment]');

                if (!level || !comment) {
                    return;
                }

                if (level.value.trim() === '' || comment.value.trim() !== '') {
                    comment.classList.remove('is-invalid');
                }
            });

            field.addEventListener('change', () => field.dispatchEvent(new Event('input')));
        });
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        if (window.bootstrap?.Tooltip) {
            new bootstrap.Tooltip(element, {
                container: 'body',
                boundary: document.body,
            });
        }
    });
</script>
@endsection
