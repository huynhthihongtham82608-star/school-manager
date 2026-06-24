@extends('layouts.app')
@section('title', 'Nhập điểm')

@section('content')
<div class="page-heading">
    <div>
        <h5>Nhập điểm - {{ $class->name }} / {{ $subject->name }} / {{ $semester->name }}</h5>
        <div class="text-muted">Nhập nhiều giá trị cách nhau bởi dấu phẩy. HS1: miệng + 15p, HS2: 1 tiết + giữa kỳ, HS3: cuối kỳ.</div>
    </div>
    <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">Quay lại</a>
</div>

<form method="POST" action="{{ route('scores.store') }}">
    @csrf
    <input type="hidden" name="class_id" value="{{ $class->id }}">
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">
    <input type="hidden" name="semester_id" value="{{ $semester->id }}">
    <div class="card score-sheet">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        <th>Miệng</th>
                        <th>15 phút</th>
                        <th>1 tiết</th>
                        <th>Giữa kỳ</th>
                        <th>Cuối kỳ</th>
                        <th>TB</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $header = $headers[$student->id] ?? null;
                        $group = [
                            'oral' => $header?->details?->where('type','oral')->pluck('value')->join(', '),
                            'quiz' => $header?->details?->where('type','quiz')->pluck('value')->join(', '),
                            'test' => $header?->details?->where('type','test')->pluck('value')->join(', '),
                            'midterm' => $header?->details?->where('type','midterm')->pluck('value')->join(', '),
                            'final' => $header?->details?->where('type','final')->pluck('value')->join(', '),
                        ];
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $student->student_code }}</td>
                        <td>{{ $student->name }}</td>
                        <td><input type="text" name="scores[{{ $student->id }}][oral]" class="form-control form-control-sm" value="{{ $group['oral'] }}"></td>
                        <td><input type="text" name="scores[{{ $student->id }}][quiz]" class="form-control form-control-sm" value="{{ $group['quiz'] }}"></td>
                        <td><input type="text" name="scores[{{ $student->id }}][test]" class="form-control form-control-sm" value="{{ $group['test'] }}"></td>
                        <td><input type="text" name="scores[{{ $student->id }}][midterm]" class="form-control form-control-sm" value="{{ $group['midterm'] }}"></td>
                        <td><input type="text" name="scores[{{ $student->id }}][final]" class="form-control form-control-sm" value="{{ $group['final'] }}"></td>
                        <td class="fw-semibold text-primary">{{ $header?->average }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3 text-end">
        <button class="btn btn-primary">Lưu điểm</button>
    </div>
</form>
@endsection
