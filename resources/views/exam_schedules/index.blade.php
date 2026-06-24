@extends('layouts.app')
@section('title', 'Lịch thi')

@section('content')
<div class="page-heading">
    <div>
        <h5>Lịch thi</h5>
        <div class="text-muted">Theo dõi lịch kiểm tra, lịch thi, phòng thi và ghi chú.</div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isStaff())
    <div class="card mb-3">
        <div class="card-header">Thêm lịch thi</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exam-schedules.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6"><label class="form-label">Tiêu đề</label><input name="title" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Ngày thi</label><input type="date" name="exam_date" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Phòng thi</label><input name="room" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Lớp</label><select name="class_id" class="form-select"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Môn học</label><select name="subject_id" class="form-select"><option value="">Tất cả</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Học kỳ</label><select name="semester_id" class="form-select"><option value="">Tất cả</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Giờ bắt đầu</label><input type="time" name="start_time" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Giờ kết thúc</label><input type="time" name="end_time" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Ghi chú</label><input name="note" class="form-control"></div>
                <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Thêm lịch thi</button></div>
            </form>
        </div>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Lớp</th>
                    <th>Môn</th>
                    <th>Ngày thi</th>
                    <th>Giờ</th>
                    <th>Phòng</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td class="fw-semibold">{{ $schedule->title }}</td>
                    <td>{{ $schedule->classRoom->name ?? 'Tất cả' }}</td>
                    <td>{{ $schedule->subject->name ?? 'Tất cả' }}</td>
                    <td>{{ optional($schedule->exam_date)->format('d/m/Y') }}</td>
                    <td>{{ trim(($schedule->start_time ?: '') . ' - ' . ($schedule->end_time ?: ''), ' -') ?: 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->room ?: 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->note }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-calendar2-x"></i>Chưa có lịch thi.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($schedules, 'links'))
    <div class="mt-3">{{ $schedules->links() }}</div>
@endif
@endsection
