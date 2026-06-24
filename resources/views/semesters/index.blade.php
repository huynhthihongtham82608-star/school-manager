@extends('layouts.app')
@section('title', 'Học kỳ')

@section('content')
<div class="page-heading">
    <div>
        <h5>Học kỳ</h5>
        <div class="text-muted">Quản lý học kỳ theo năm học.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('semesters.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học kỳ</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Thứ tự</th>
                    <th>Năm học</th>
                    <th>Nhập điểm</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($semesters as $semester)
                <tr>
                    <td class="fw-semibold">{{ $semester->name }}</td>
                    <td>{{ $semester->order }}</td>
                    <td>{{ $semester->schoolYear->name ?? '' }}</td>
                    <td>{!! $semester->is_score_input_open ? '<span class="badge bg-success">Mở</span>' : '<span class="badge bg-secondary">Khóa</span>' !!}</td>
                    <td class="text-end">
                        <a href="{{ route('semesters.edit', $semester) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('semesters.destroy', $semester) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa học kỳ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-range"></i>Chưa có học kỳ.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
