@extends('layouts.app')
@section('title', 'Nhập điểm')

@section('content')
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
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
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
                            <th>Năm học</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $as)
                        <tr>
                            <td class="fw-semibold">{{ $as->classRoom->name }}</td>
                            <td>{{ $as->subject->name }}</td>
                            <td>{{ $as->schoolYear->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"><div class="empty-state"><i class="bi bi-inbox"></i>Không có phân công.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
