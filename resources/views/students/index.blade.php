@extends('layouts.app')
@section('title', 'Học sinh')

@section('content')
<div class="page-heading">
    <div>
        <h5>Học sinh</h5>
        <div class="text-muted">Quản lý hồ sơ học sinh, lớp và tài khoản liên kết.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('students.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học sinh</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
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
            @forelse($students as $student)
                <tr>
                    <td class="fw-semibold">{{ $student->student_code }}</td>
                    <td>{{ $student->name }}<br><span class="text-muted small">{{ $student->parent_phone }}</span></td>
                    <td>{{ $student->classRoom->name ?? '' }}</td>
                    <td>{{ $student->schoolYear->name ?? '' }}</td>
                    <td><span class="badge bg-success">{{ $student->status }}</span></td>
                    <td>{{ $student->user?->username }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('students.edit', $student) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                    <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-person-dash"></i>Chưa có học sinh.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
