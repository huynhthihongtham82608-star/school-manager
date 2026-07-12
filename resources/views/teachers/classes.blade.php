@extends('layouts.app')
@section('title', 'Lớp đang giảng dạy')

@section('content')
<div class="page-heading">
    <div>
        <h5>Lớp đang giảng dạy</h5>
        <div class="text-muted">Danh sách lớp được phân công giảng dạy hoặc chủ nhiệm.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Khối</th>
                    <th>Năm học</th>
                    <th>Học kỳ</th>
                    <th>Sĩ số</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td class="fw-semibold">{{ $class->name }}</td>
                    <td>Khối {{ $class->grade_level }}</td>
                    <td>{{ $class->schoolYear?->name ?? '-' }}</td>
                    <td>{{ $class->semester?->name ?? '-' }}</td>
                    <td>{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</td>
                    <td class="text-end">
                        <a href="{{ route('teacher.classes.students', $class) }}" class="content-action-btn" title="Xem danh sách học sinh">
                            <i class="bi bi-people me-1"></i>Xem học sinh
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>Chưa có lớp được phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
