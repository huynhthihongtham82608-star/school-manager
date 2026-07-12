@extends('layouts.app')
@section('title', 'Danh sách học sinh')

@section('content')
<div class="page-heading">
    <div>
        <h5>Danh sách học sinh lớp {{ $class->name }}</h5>
        <div class="text-muted">{{ $class->schoolYear?->name ?? '-' }} · {{ $class->semester?->name ?? '-' }}</div>
    </div>
    <a href="{{ route('teacher.classes') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã học sinh</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Giới tính</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($class->students as $student)
                <tr>
                    <td class="fw-semibold">{{ $student->student_code }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->dob?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $student->genderLabel() }}</td>
                    <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
                    <td class="text-end">
                        <button type="button" class="content-action-btn icon-only" data-bs-toggle="modal" data-bs-target="#studentProfile{{ $student->id }}" title="Xem hồ sơ">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($class->students as $student)
    <div class="modal fade content-modal" id="studentProfile{{ $student->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="student-card mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="student-avatar">
                                @if($student->avatar)
                                    <img src="{{ asset($student->avatar) }}" alt="{{ $student->name }}">
                                @else
                                    <span>{{ mb_substr($student->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $student->name }}</h5>
                                <div class="text-muted">{{ $student->student_code }}</div>
                                <div class="mt-2">
                                    <span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
                                    <span class="badge bg-light text-dark border">{{ $class->name }}</span>
                                    <span class="badge bg-light text-dark border">{{ $student->cohortLabel() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="profile-info-card">
                                <div class="profile-info-title">Thông tin học sinh</div>
                                <div class="profile-info-grid">
                                    <span>Giới tính</span><strong>{{ $student->genderLabel() }}</strong>
                                    <span>Ngày sinh</span><strong>{{ $student->dob?->format('d/m/Y') ?? '-' }}</strong>
                                    <span>Loại nhập học</span><strong>{{ $student->admissionTypeLabel() }}</strong>
                                    <span>Ngày nhập học</span><strong>{{ $student->enrollment_date?->format('d/m/Y') ?? '-' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="profile-info-card">
                                <div class="profile-info-title">Thông tin phụ huynh</div>
                                <div class="profile-info-grid">
                                    <span>SĐT phụ huynh</span><strong>{{ $student->parent_phone ?: '-' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="profile-info-card">
                                <div class="profile-info-title">Thông tin bổ sung</div>
                                <div class="profile-info-grid two-columns">
                                    <span>Nơi sinh</span><strong>{{ $student->place_of_birth ?: '-' }}</strong>
                                    <span>Dân tộc</span><strong>{{ $student->ethnicity ?: 'Kinh' }}</strong>
                                    <span>Tôn giáo</span><strong>{{ $student->religion ?: 'Không' }}</strong>
                                    <span>Địa chỉ</span><strong>{{ $student->address ?: '-' }}</strong>
                                    <span>Ghi chú</span><strong>{{ $student->note ?: '-' }}</strong>
                                </div>
                            </div>
                        </div>
                        @if($student->admission_type === \App\Models\Student::ADMISSION_TRANSFER)
                            <div class="col-12">
                                <div class="profile-info-card">
                                    <div class="profile-info-title">Thông tin chuyển trường</div>
                                    <div class="profile-info-grid">
                                        <span>Trường cũ</span><strong>{{ $student->previous_school ?: '-' }}</strong>
                                        <span>Khối hiện tại</span><strong>{{ $student->transfer_grade_level ?: '-' }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
