@extends('layouts.app')
@section('title', 'Năm học')

@section('content')
<div class="page-heading">
    <div>
        <h5>Năm học</h5>
        <div class="text-muted">Quản lý năm học và trạng thái sử dụng.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('school-years.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm năm học</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Bắt đầu</th>
                    <th>Kết thúc</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($years as $year)
                    <tr>
                        <td class="fw-semibold">{{ $year->name }}</td>
                        <td>{{ $year->start_date }}</td>
                        <td>{{ $year->end_date }}</td>
                        <td>{!! $year->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
                        <td class="text-end">
                            <a href="{{ route('school-years.edit', $year) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                            <form action="{{ route('school-years.destroy', $year) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa năm học?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có năm học.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
