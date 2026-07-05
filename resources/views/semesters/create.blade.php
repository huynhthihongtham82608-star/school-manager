@extends('layouts.app')
@section('title', 'Thêm học kỳ')

@section('content')
<form method="POST" action="{{ route('semesters.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên học kỳ</label>
            <select name="name" class="form-select" required>
                @foreach($termOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('name') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected(old('school_year_id') == $year->id)>{{ $year->name }} - {{ $year->statusLabel() }}</option>
                @endforeach
            </select>
            @error('school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái mặc định</label>
            <div class="form-control bg-light">Bản nháp</div>
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('semesters.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>
@endsection
