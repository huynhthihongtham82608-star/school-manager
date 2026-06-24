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
                        <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa lớp này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
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
