@extends('layouts.app')
@section('title', auth()->user()->isStudent() ? 'Điểm số' : 'Nhập điểm')

@section('content')
@if(auth()->user()->isStudent())
    @php
        $detailLabels = [
            'oral' => 'Miệng',
            'quiz' => '15 phút',
            'test' => 'Một tiết',
            'midterm' => 'Giữa kỳ',
            'final' => 'Cuối kỳ',
        ];
    @endphp
    <div class="page-heading">
        <div>
            <h5>Điểm số của tôi</h5>
            <div class="text-muted">Chỉ hiển thị điểm của học sinh đang đăng nhập.</div>
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

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Học kỳ</th>
                        <th>Điểm thành phần</th>
                        <th>Điểm trung bình</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($studentScores as $score)
                    <tr>
                        <td class="fw-semibold">{{ $score->subject->name ?? '-' }}</td>
                        <td>{{ $score->semester?->normalizedName() ?? '-' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($score->details->groupBy('type') as $type => $items)
                                    <span class="badge bg-light text-dark border">
                                        {{ $detailLabels[$type] ?? $type }}:
                                        {{ $items->pluck('value')->map(fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'))->implode(', ') }}
                                    </span>
                                @empty
                                    <span class="text-muted">Chưa có điểm thành phần</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            @if($score->average !== null)
                                <span class="badge bg-info">{{ number_format($score->average, 2) }}</span>
                            @else
                                <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu điểm trong học kỳ này.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
<div class="page-heading">
    <div>
        <h5>Nhập điểm</h5>
        <div class="text-muted">Chọn lớp, môn và học kỳ để mở bảng nhập điểm.</div>
    </div>
    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
        <a href="{{ route('grade-windows.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-lock"></i>
            Cấu hình khóa nhập điểm
        </a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Chọn lớp/môn/học kỳ</div>
            <div class="card-body">
                <form method="GET" action="{{ route('scores.entry') }}" class="row g-3">
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
                    <div class="col-12 text-end">
                        <button class="btn btn-primary">Mở bảng nhập</button>
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
@endif
@endsection
