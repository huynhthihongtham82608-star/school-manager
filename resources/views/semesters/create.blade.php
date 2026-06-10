@extends('layouts.app')
@section('title', 'Thêm học kỳ')

@section('content')
<h5 class="mb-3">Thêm học kỳ</h5>
<form method="POST" action="{{ route('semesters.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Thứ tự</label>
            <input type="number" name="order" class="form-control" value="1" min="1" max="4" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_score_input_open" value="1" id="is_open" checked>
                <label class="form-check-label" for="is_open">Mở nhập điểm</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('semesters.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
