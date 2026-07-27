@extends('layouts.app')
@section('title', 'Sửa giáo viên')

@section('content')
<x-page-header
    title="Chỉnh sửa hồ sơ giáo viên"
    subtitle="Cập nhật thông tin công tác, cá nhân và liên hệ của giáo viên."
>
    <a href="{{ route('teachers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
    </a>
</x-page-header>

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@include('teachers.partials.form', [
    'action' => route('teachers.update', $teacher),
    'teacher' => $teacher,
    'subjects' => $subjects,
    'departments' => $departments,
    'nextTeacherCode' => null,
])
@endsection
