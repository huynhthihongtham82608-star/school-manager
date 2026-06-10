@extends('layouts.app')
@section('title', 'Học sinh')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Học sinh</h5>
        <div class="text-muted">Quản lý học sinh</div>
    </div>
    <a class="btn btn-primary" href="{{ route('students.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học sinh</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Mã HS</th>
                    <th>Họ tên</th>
                    <th>Lớp</th>
                    <th>Năm học</th>
                    <th>Trạng thái</th>
                    <th>Tài khoản</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($students as $student)
                <tr>
                    <td>{{ $student->student_code }}</td>
                    <td>{{ $student->name }}<br><span class="text-muted small">{{ $student->parent_phone }}</span></td>
                    <td>{{ $student->classRoom->name ?? '' }}</td>
                    <td>{{ $student->schoolYear->name ?? '' }}</td>
                    <td><span class="badge bg-success">{{ $student->status }}</span></td>
                    <td>{{ $student->user?->username }}</td>
                    <td class="text-end">
                        <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa học sinh?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
