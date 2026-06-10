@extends('layouts.app')
@section('title', 'Giáo viên')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Giáo viên</h5>
        <div class="text-muted">Quản lý thông tin giáo viên</div>
    </div>
    <a class="btn btn-primary" href="{{ route('teachers.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm giáo viên</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Họ tên</th>
                    <th>Môn chính</th>
                    <th>GVCN</th>
                    <th>Tài khoản</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($teachers as $teacher)
                <tr>
                    <td>{{ $teacher->teacher_code }}</td>
                    <td>{{ $teacher->name }}<br><span class="text-muted small">{{ $teacher->phone }}</span></td>
                    <td>{{ $teacher->main_subject }}</td>
                    <td>{!! $teacher->is_homeroom ? '<span class="badge bg-info">Có</span>' : '-' !!}</td>
                    <td>{{ $teacher->user?->username }}</td>
                    <td class="text-end">
                        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('teachers.destroy', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa giáo viên?')">
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
