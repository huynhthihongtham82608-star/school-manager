@extends('layouts.app')
@section('title', 'Thêm phụ huynh')

@section('content')
<x-page-header
    title="Thêm phụ huynh mới"
    subtitle="Khởi tạo tài khoản phụ huynh và liên kết với học sinh đang theo học."
>
    <a href="{{ route('parents.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
    </a>
</x-page-header>

@include('parents.partials.form', [
    'action' => route('parents.store'),
    'parent' => null,
    'students' => $students,
    'nextParentCode' => $nextParentCode,
])
@include('parents.partials.student-picker')
@endsection
