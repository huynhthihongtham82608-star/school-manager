@extends('layouts.app')
@section('title', 'Nhập điểm')

@section('content')
<div class="page-heading">
    <div>
        <h5>Điểm số - {{ $class->name }} / {{ $subject->name }} / {{ $semester->normalizedName() }}</h5>
        <div class="text-muted">Điểm thường xuyên do giáo viên bộ môn nhập trực tiếp. Điểm giữa kỳ và cuối kỳ chỉ nhập trong thời gian Admin đã mở.</div>
    </div>
    <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">Quay lại</a>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2">
        @foreach($scoreTypes as $type => $meta)
            <span class="badge {{ $meta['editable'] ? 'bg-success' : 'bg-secondary' }}" title="{{ $meta['reason'] }}">
                {{ $meta['label'] }}: {{ $meta['editable'] ? 'Đang mở' : 'Chỉ xem' }}
            </span>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('scores.store') }}">
    @csrf
    <input type="hidden" name="class_id" value="{{ $class->id }}">
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">
    <input type="hidden" name="semester_id" value="{{ $semester->id }}">
    <div class="card score-sheet">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        @foreach($scoreTypes as $type => $meta)
                            <th style="min-width: 150px;">
                                <div>{{ $meta['label'] }}</div>
                                @if($meta['kind'] === 'regular')
                                    <input
                                        type="text"
                                        name="score_names[{{ $type }}]"
                                        class="form-control form-control-sm mt-1"
                                        placeholder="Tên bài kiểm tra"
                                        @disabled(! $meta['editable'])
                                    >
                                @else
                                    <div class="text-muted small mt-1">
                                        {{ $meta['schedule']?->displayName() ?? 'Chưa có kỳ kiểm tra' }}
                                    </div>
                                @endif
                            </th>
                        @endforeach
                        <th>TB</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $header = $headers[$student->id] ?? null;
                        $group = collect($scoreTypes)->mapWithKeys(fn ($meta, $type) => [
                            $type => $header?->details?->where('type', $type)->pluck('value')->join(', '),
                        ]);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $student->student_code }}</td>
                        <td>{{ $student->name }}</td>
                        @foreach($scoreTypes as $type => $meta)
                            <td>
                                <input
                                    type="text"
                                    name="scores[{{ $student->id }}][{{ $type }}]"
                                    class="form-control form-control-sm"
                                    value="{{ $group[$type] }}"
                                    placeholder="VD: 8, 9"
                                    @disabled(! $meta['editable'])
                                >
                                @unless($meta['editable'])
                                    <div class="text-muted small mt-1">{{ $meta['reason'] }}</div>
                                @endunless
                            </td>
                        @endforeach
                        <td class="fw-semibold text-primary">{{ $header?->average !== null ? number_format($header->average, 2) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($scoreTypes) + 3 }}"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3 text-end">
        @if($canSubmitScores)
            <button class="btn btn-primary">Lưu điểm</button>
        @else
            <span class="text-muted">Bạn đang ở chế độ chỉ xem hoặc chưa có cột điểm nào được mở.</span>
        @endif
    </div>
</form>
@endsection
