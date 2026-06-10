@extends('layouts.app')
@section('title', 'Chạy AI phân tích')

@section('content')
<h5 class="mb-3">Chạy AI phân tích</h5>
<div class="card shadow-sm p-4">
    <form method="POST" action="{{ route('ai.run') }}" class="row g-3">
        @csrf
        <div class="col-md-4">
            <label class="form-label">Lớp</label>
            <select class="form-select" name="class_id" required>
                <option value="">-- Chọn lớp --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Học kỳ</label>
            <select class="form-select" name="semester_id" required>
                <option value="">-- Chọn học kỳ --</option>
                @foreach($semesters as $s)
                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->schoolYear->name ?? '' }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 align-self-end">
            <button class="btn btn-primary w-100"><i class="bi bi-cpu me-1"></i>Chạy phân tích</button>
        </div>
        <div class="col-12 text-muted small">
            AI (rule-based) sẽ tổng hợp điểm TB học kỳ, so sánh học kỳ trước để phát hiện xu hướng giảm và tạo cảnh báo cho GVCN/BGH.
        </div>
    </form>
</div>
@endsection
