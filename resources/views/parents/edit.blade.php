@extends('layouts.app')
@section('title', 'Sửa phụ huynh')

@section('content')
<x-page-header
    title="Chỉnh sửa thông tin phụ huynh"
    subtitle="Cập nhật thông tin tài khoản, liên hệ và danh sách học sinh liên kết."
>
    <a href="{{ route('parents.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
    </a>
</x-page-header>

@include('parents.partials.form', [
    'action' => route('parents.update', $parent),
    'parent' => $parent,
    'students' => $students,
    'nextParentCode' => null,
])
@include('parents.partials.student-picker')
@endsection
