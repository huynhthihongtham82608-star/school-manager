@extends('layouts.app')
@section('title', 'Sửa môn học')

@section('content')
<h5 class="mb-3">Sửa môn học</h5>
<form method="POST" action="{{ route('subjects.update', $subject) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tên môn</label>
            <input type="text" name="name" class="form-control" value="{{ $subject->name }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tín chỉ</label>
            <input type="number" name="credit" class="form-control" value="{{ $subject->credit }}" min="1">
        </div>
        <div class="col-md-3 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_weighted" value="1" id="is_weighted" {{ $subject->is_weighted ? 'checked' : '' }}>
                <label class="form-check-label" for="is_weighted">Hệ số 2</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('subjects.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
