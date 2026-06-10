@extends('layouts.app')
@section('title', 'Nhập điểm')

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Chọn lớp/môn/học kỳ</div>
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
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Phân công của bạn</div>
            <div class="card-body p-0">
                <table class="table mb-0">
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
                            <td>{{ $as->classRoom->name }}</td>
                            <td>{{ $as->subject->name }}</td>
                            <td>{{ $as->schoolYear->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có phân công</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
