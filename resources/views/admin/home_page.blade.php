@extends('layouts.app')
@section('title', 'Quản lý trang chủ')

@section('content')
@php
    $banner = $contents->get('banner');
    $about = $contents->get('about');
    $contact = $contents->get('contact');
@endphp

<div class="page-heading">
    <div>
        <h5>Quản lý trang chủ</h5>
        <div class="text-muted">Cập nhật Banner, Slider, nội dung chào mừng, giới thiệu trường, hình ảnh và thông tin liên hệ trên Landing Page.</div>
    </div>
    <a href="{{ route('home') }}" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-box-arrow-up-right me-2"></i>Xem trang chủ
    </a>
</div>

@unless($tablesReady)
    <div class="alert alert-warning">Chưa có bảng home_page_contents. Vui lòng import SQL tạo bảng trước khi lưu nội dung.</div>
@endunless

<div class="card">
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
            <div class="col-md-6">
                <label class="form-label">Tiêu đề liên hệ</label>
                <input name="contact_title" class="form-control" value="{{ old('contact_title', $contact->title ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Giới thiệu trường</label>
                <textarea name="about_content" rows="5" class="form-control">{{ old('about_content', $about->content ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Nội dung liên hệ</label>
                <textarea name="contact_content" rows="3" class="form-control">{{ old('contact_content', $contact->content ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Điện thoại</label>
                <input name="contact_phone" class="form-control" value="{{ old('contact_phone', data_get($contact, 'extra.phone')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input name="contact_email" class="form-control" value="{{ old('contact_email', data_get($contact, 'extra.email')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Địa chỉ</label>
                <input name="contact_address" class="form-control" value="{{ old('contact_address', data_get($contact, 'extra.address')) }}">
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
