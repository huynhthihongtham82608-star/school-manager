@extends('layouts.app')
@section('title', 'Giáo viên')

@section('content')
<div class="page-heading">
    <div>
        <h5>Giáo viên</h5>
        <div class="text-muted">Quản lý thông tin giáo viên và tài khoản liên kết.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('teachers.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm giáo viên</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
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
            @forelse($teachers as $teacher)
                <tr>
                    <td class="fw-semibold">{{ $teacher->teacher_code }}</td>
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
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-person-badge"></i>Chưa có giáo viên.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
