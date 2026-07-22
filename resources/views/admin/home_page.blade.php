@extends('layouts.app')
@section('title', 'Quản lý trang chủ')

@section('content')
@php
    $banner = $contents->get('banner');
    $about = $contents->get('about');
@endphp

<x-page-header
    title="Cấu hình nội dung hệ thống"
    subtitle="Quản lý giao diện trang chủ, biên tập các bài viết tin tức, chỉnh sửa thư viện ảnh và thông tin hiển thị trên cổng thông tin nhà trường."
>
    <a class="btn btn-primary" href="#home-page-content">
        <i class="bi bi-plus-lg me-1"></i>Viết bài mới
    </a>
</x-page-header>

@unless($tablesReady)
    <div class="alert alert-warning">Chưa có bảng home_page_contents. Vui lòng import SQL tạo bảng trước khi lưu nội dung.</div>
@endunless

<div class="card" id="home-page-content">
    <div class="card-header">Nội dung Trang chủ</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.home-page.content') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Tiêu đề banner</label>
                <input name="banner_title" class="form-control" value="{{ old('banner_title', $banner->title ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nội dung chào mừng</label>
                <input name="banner_subtitle" class="form-control" value="{{ old('banner_subtitle', data_get($banner, 'extra.subtitle')) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Mô tả banner</label>
                <textarea name="banner_content" rows="3" class="form-control">{{ old('banner_content', $banner->content ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">URL hình ảnh Banner / Slider</label>
                <input name="banner_image_url" class="form-control" value="{{ old('banner_image_url', $banner->image_url ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Tiêu đề giới thiệu</label>
                <input name="about_title" class="form-control" value="{{ old('about_title', $about->title ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Giới thiệu trường</label>
                <textarea name="about_content" rows="5" class="form-control">{{ old('about_content', $about->content ?? '') }}</textarea>
            </div>
            <div class="col-12 d-flex justify-content-end flex-wrap gap-2">
                <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu nội dung</button>
                <a href="{{ route('home') }}" class="btn btn-outline-primary" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Xem trang chủ
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
