@extends('layouts.app')
@section('title', 'Năm học')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Năm học</h5>
        <div class="text-muted">Quản lý năm học</div>
    </div>
    <a class="btn btn-primary" href="{{ route('school-years.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm năm học</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
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
                @foreach($years as $year)
                    <tr>
                        <td>{{ $year->name }}</td>
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
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
