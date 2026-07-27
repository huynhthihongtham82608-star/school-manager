@extends('layouts.app')
@section('title', 'Thêm giáo viên')

@section('content')
<x-page-header
    title="Thêm giáo viên mới"
    subtitle="Nhập thông tin công tác, cá nhân và liên hệ để khởi tạo hồ sơ giáo viên."
>
    <a href="{{ route('teachers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
    </a>
</x-page-header>

@include('teachers.partials.form', [
    'action' => route('teachers.store'),
    'teacher' => null,
    'subjects' => $subjects,
    'departments' => $departments,
    'nextTeacherCode' => $nextTeacherCode,
])
@endsection
