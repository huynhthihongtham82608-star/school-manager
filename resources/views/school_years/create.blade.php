@extends('layouts.app')
@section('title', 'Thêm năm học')

@section('content')
<h5 class="mb-3">Thêm năm học</h5>
<form method="POST" action="{{ route('school-years.store') }}" class="card p-4 shadow-sm" data-school-year-form data-active-year="{{ $activeYear?->name }}">
    @csrf
    <input type="hidden" name="confirm_activation" value="0" data-confirm-activation>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Năm học</label>
            <div class="d-flex align-items-center gap-2">
                <input type="number" name="start_year" value="{{ old('start_year') }}" class="form-control" min="1900" max="2100" placeholder="2025" required data-start-year>
                <span class="fw-bold text-muted">-</span>
                <input type="number" name="end_year" value="{{ old('end_year') }}" class="form-control" min="1901" max="2101" placeholder="2026" required data-end-year>
            </div>
            <div class="form-text">Năm kết thúc phải bằng năm bắt đầu + 1.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control" data-start-date>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control" data-end-date>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active'))>
                <label class="form-check-label" for="is_active">Kích hoạt năm học này</label>
            </div>
            <div class="form-text">Mặc định năm học mới ở trạng thái chưa hoạt động.</div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-end gap-2">
        <a href="{{ route('school-years.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

<div class="modal fade content-modal" id="activateSchoolYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Xác nhận kích hoạt</div>
                    <h5 class="modal-title">Chuyển năm học hoạt động</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Đang có một năm học hoạt động. Bạn có muốn chuyển sang năm học <strong data-target-year-label></strong> không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" data-confirm-activation-submit>Xác nhận</button>
            </div>
        </div>
    </div>
</div>

@include('school_years.partials.form-script')
@endsection
