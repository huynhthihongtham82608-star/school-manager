@extends('layouts.app')
@section('title', 'Sửa năm học')

@section('content')
<h5 class="mb-3">Sửa năm học</h5>
<form method="POST" action="{{ route('school-years.update', $schoolYear) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên năm học</label>
            <input type="text" name="name" value="{{ $schoolYear->name }}" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ $schoolYear->start_date }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ $schoolYear->end_date }}" class="form-control">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ $schoolYear->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Kích hoạt</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('school-years.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
