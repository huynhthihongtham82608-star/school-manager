@extends('layouts.app')
@section('title', 'Cấu hình cột điểm')

@section('content')
@php
    $typeOptions = \App\Models\ScoreColumn::TYPES;
@endphp

<x-page-header
    title="Cấu hình cột điểm"
    subtitle="Admin quản lý tên, loại và số lượng cột điểm theo năm học, khối và môn học."
>
    <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
        Quay lại điểm số
    </a>
</x-page-header>

<div class="card mb-3">
    <div class="card-header">Bộ lọc</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Năm học</label>
                <select name="school_year_id" class="form-select">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Khối</label>
                <select name="grade_level" class="form-select">
                    <option value="all" @selected($selectedGrade === 'all')>Tất cả</option>
                    @foreach([10, 11, 12] as $grade)
                        <option value="{{ $grade }}" @selected((string) $selectedGrade === (string) $grade)>Khối {{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Môn học</label>
                <select name="subject_id" class="form-select">
                    <option value="all" @selected($selectedSubjectId === 'all')>Tất cả môn học</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($selectedSubjectId === $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">
                    <i class="bi bi-search"></i>
                    Lọc
                </button>
                <a href="{{ route('score-columns.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Thêm cột điểm</div>
    <div class="card-body">
        <form method="POST" action="{{ route('score-columns.store') }}" class="row g-3 align-items-end" data-score-column-form>
            @csrf
            <div class="col-md-3">
                <label class="form-label">Năm học</label>
                <select name="school_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected(old('school_year_id', $selectedYearId) === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Khối</label>
                <select name="grade_level" class="form-select" required>
                    @foreach([10, 11, 12] as $grade)
                        <option value="{{ $grade }}" @selected((string) old('grade_level', $selectedGrade !== 'all' ? $selectedGrade : 10) === (string) $grade)>Khối {{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Môn học</label>
                <select name="subject_id" class="form-select" required>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id', $selectedSubjectId !== 'all' ? $selectedSubjectId : null) === $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tên cột điểm</label>
                <input name="name" class="form-control" value="{{ old('name') }}" placeholder="Ví dụ: Kiểm tra 15 phút lần 1" required maxlength="255">
            </div>
            <div class="col-md-3">
                <label class="form-label">Loại điểm</label>
                <select name="type" class="form-select" required>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Thứ tự</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" max="1000">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ngày mở</label>
                <input type="date" name="input_opens_at" class="form-control" value="{{ old('input_opens_at') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ngày khóa</label>
                <input type="date" name="input_closes_at" class="form-control" value="{{ old('input_closes_at') }}">
            </div>
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveAdd" checked>
                    <label class="form-check-label" for="isActiveAdd">Mở</label>
                </div>
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-plus-circle"></i>
                    Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Danh sách cột điểm</div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Năm học</th>
                    <th>Khối</th>
                    <th>Môn học</th>
                    <th>Tên cột điểm</th>
                    <th>Loại điểm</th>
                    <th>Thời gian nhập</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($columns as $column)
                <tr>
                    <td>{{ $column->schoolYear?->name ?? '-' }}</td>
                    <td class="fw-semibold">Khối {{ $column->grade_level }}</td>
                    <td>{{ $column->subject?->name ?? '-' }}</td>
                    <td class="fw-semibold">{{ $column->name }}</td>
                    <td>{{ $column->typeLabel() }}</td>
                    <td>
                        <div class="small">
                            Mở: {{ $column->input_opens_at?->format('d/m/Y') ?? 'Không giới hạn' }}
                        </div>
                        <div class="small text-muted">
                            Khóa: {{ $column->input_closes_at?->format('d/m/Y') ?? 'Chưa khóa' }}
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $column->inputStatusBadgeClass() }}">{{ $column->inputStatusLabel() }}</span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#editScoreColumn{{ $column->id }}" title="Chỉnh sửa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('score-columns.destroy', $column) }}" class="d-inline" onsubmit="return confirm('Xóa cột điểm này?');">
                            @csrf
                            @method('DELETE')
                            <button class="content-action-btn icon-only delete" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state"><i class="bi bi-table"></i>Chưa có cột điểm nào.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $columns->links() }}</div>

@foreach($columns as $column)
    <div class="modal fade content-modal" id="editScoreColumn{{ $column->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('score-columns.update', $column) }}" data-score-column-form>
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">Cột điểm</div>
                            <h5 class="modal-title">Chỉnh sửa cột điểm</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Năm học</label>
                                <select name="school_year_id" class="form-select" required>
                                    @foreach($years as $year)
                                        <option value="{{ $year->id }}" @selected($column->school_year_id === $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Khối</label>
                                <select name="grade_level" class="form-select" required>
                                    @foreach([10, 11, 12] as $grade)
                                        <option value="{{ $grade }}" @selected($column->grade_level === $grade)>Khối {{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Môn học</label>
                                <select name="subject_id" class="form-select" required>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected($column->subject_id === $subject->id)>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tên cột điểm</label>
                                <input name="name" class="form-control" value="{{ $column->name }}" required maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Loại điểm</label>
                                <select name="type" class="form-select" required>
                                    @foreach($typeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($column->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Thứ tự</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ $column->sort_order }}" min="0" max="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày mở</label>
                                <input type="date" name="input_opens_at" class="form-control" value="{{ $column->input_opens_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày khóa</label>
                                <input type="date" name="input_closes_at" class="form-control" value="{{ $column->input_closes_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive{{ $column->id }}" @checked($column->is_active)>
                                    <label class="form-check-label" for="isActive{{ $column->id }}">Đang sử dụng</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.querySelectorAll('[data-score-column-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const openInput = form.querySelector('[name="input_opens_at"]');
            const closeInput = form.querySelector('[name="input_closes_at"]');

            if (openInput?.value && closeInput?.value && closeInput.value < openInput.value) {
                event.preventDefault();
                closeInput.setCustomValidity('Ngày khóa nhập điểm phải sau hoặc bằng ngày mở.');
                closeInput.reportValidity();
                return;
            }

            closeInput?.setCustomValidity('');
        });
    });
</script>
@endsection
