@extends('layouts.app')
@section('title', 'Lớp học')

@section('content')
<div class="page-heading">
    <div>
        <h5>Lớp học</h5>
        <div class="text-muted">Quản lý lớp 10/11/12, GVCN và sĩ số.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm lớp</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên lớp</th>
                    <th>Khối</th>
                    <th>Năm học</th>
                    <th>GVCN</th>
                    <th>Sĩ số</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td class="fw-semibold">{{ $class->name }}</td>
                    <td>{{ $class->grade_level }}</td>
                    <td>{{ $class->schoolYear->name ?? '' }}</td>
                    <td>{{ $class->homeroomTeacher->name ?? '-' }}</td>
                    <td>{{ $class->students->count() }} / {{ $class->capacity }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('classes.edit', $class) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <form action="{{ route('classes.destroy', $class) }}" method="POST" class="d-inline">
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
                    <td colspan="6"><div class="empty-state"><i class="bi bi-building"></i>Chưa có lớp học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
