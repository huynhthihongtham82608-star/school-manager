@extends('layouts.app')
@section('title', (auth()->user()->isStudent() || auth()->user()->isParent()) ? 'Điểm số' : 'Nhập điểm')

@section('content')
@if(auth()->user()->isStudent() || auth()->user()->isParent())
    @php
        $detailLabels = $detailLabels ?? [];
        $normalizeScoreType = function ($type) {
            return match ($type) {
                'oral', 'quiz', 'test', 'regular' => 'regular',
                'final', 'final_test' => 'final',
                default => $type,
            };
        };
        $formatScoreList = function ($score, array $types) use ($detailLabels) {
            $items = $score->details->filter(function ($detail) use ($types) {
                $normalizedType = match ($detail->type) {
                    'oral', 'quiz', 'test', 'regular' => 'regular',
                    'final', 'final_test' => 'final',
                    default => $detail->type,
                };

                return in_array($normalizedType, $types, true);
            });

            if ($items->isEmpty()) {
                return '<span class="text-muted">Chưa có</span>';
            }

            return $items
                ->sortBy(fn ($detail) => $detail->scoreColumn?->sort_order ?? 999)
                ->map(function ($detail) use ($detailLabels) {
                    $label = $detail->scoreColumn?->name ?: ($detail->name ?: ($detailLabels[$detail->type] ?? $detail->type));
                    $value = rtrim(rtrim(number_format((float) $detail->value, 2, '.', ''), '0'), '.');

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
    />

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

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Đánh giá thường xuyên</th>
                        <th>Đánh giá giữa kỳ</th>
                        <th>Đánh giá cuối kỳ</th>
                        <th>Điểm trung bình môn</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($studentScores as $score)
                    <tr>
                        <td class="fw-semibold">{{ $score->subject->name ?? '-' }}</td>
                        <td>
                            <div class="score-chip-list">{!! $formatScoreList($score, ['regular']) !!}</div>
                        </td>
                        <td>
                            <div class="score-chip-list">{!! $formatScoreList($score, ['midterm']) !!}</div>
                        </td>
                        <td>
                            <div class="score-chip-list">{!! $formatScoreList($score, ['final']) !!}</div>
                        </td>
                        <td>
                            @if($score->average !== null)
                                <span class="badge bg-info">{{ rtrim(rtrim(number_format($score->average, 2), '0'), '.') }}</span>
                            @else
                                <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu điểm trong học kỳ này.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
<x-page-header
    title="Nhập điểm"
    subtitle="Giáo viên bộ môn nhập điểm theo các cột điểm do Admin cấu hình."
>
    @if(auth()->user()->hasPermission('scores.manage'))
        <a href="{{ route('score-columns.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-sliders"></i>
            Cấu hình cột điểm
        </a>
    @endif
</x-page-header>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Chọn lớp/môn/học kỳ</div>
            <div class="card-body">
                <form method="GET" action="{{ route('scores.entry') }}" class="row g-3" data-score-assignment-form>
                    @if(auth()->user()->isTeacher() && ! auth()->user()->isAdmin())
                        <input type="hidden" name="class_id" data-score-class-id>
                        <input type="hidden" name="subject_id" data-score-subject-id>
                        <input type="hidden" name="semester_id" data-score-semester-id>
                        <div class="col-12">
                            <label class="form-label">Phân công giảng dạy</label>
                            <select class="form-select" data-score-assignment-select required @disabled($assignments->isEmpty())>
                                @forelse($assignments as $assignment)
                                    <option
                                        value="{{ $assignment->id }}"
                                        data-class-id="{{ $assignment->class_id }}"
                                        data-subject-id="{{ $assignment->subject_id }}"
                                        data-semester-id="{{ $assignment->semester_id }}"
                                    >
                                        {{ $assignment->classRoom?->name ?? 'Không rõ lớp' }}
                                        - {{ $assignment->subject?->name ?? 'Không rõ môn' }}
                                        - {{ $assignment->semester?->normalizedName() ?? 'Không rõ học kỳ' }}
                                    </option>
                                @empty
                                    <option value="">Chưa có phân công giảng dạy đang hoạt động</option>
                                @endforelse
                            </select>
                            <div class="text-muted small mt-2">Danh sách này chỉ gồm các lớp và môn thầy/cô đang được phân công.</div>
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label">Lớp</label>
                            <select name="class_id" class="form-select" required>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Môn</label>
                            <select name="subject_id" class="form-select" required>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Học kỳ</label>
                            <select name="semester_id" class="form-select" required>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>
                                        {{ $semester->normalizedName() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12 text-end">
                        <button class="btn btn-primary" @disabled((auth()->user()->isTeacher() && ! auth()->user()->isAdmin()) && $assignments->isEmpty())>Mở bảng nhập</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Phân công của bạn</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Lớp</th>
                            <th>Môn</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td class="fw-semibold">{{ $assignment->classRoom->name }}</td>
                            <td>{{ $assignment->subject->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2"><div class="empty-state"><i class="bi bi-inbox"></i>Không có phân công.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-score-assignment-form]').forEach((form) => {
        const select = form.querySelector('[data-score-assignment-select]');
        if (!select) {
            return;
        }

        const classInput = form.querySelector('[data-score-class-id]');
        const subjectInput = form.querySelector('[data-score-subject-id]');
        const semesterInput = form.querySelector('[data-score-semester-id]');

        const syncAssignment = () => {
            const option = select.selectedOptions[0];
            classInput.value = option?.dataset.classId || '';
            subjectInput.value = option?.dataset.subjectId || '';
            semesterInput.value = option?.dataset.semesterId || '';
        };

        select.addEventListener('change', syncAssignment);
        syncAssignment();
    });
</script>
@endif
@endsection
