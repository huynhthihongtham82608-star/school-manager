@extends('layouts.app')
@section('title', 'Học sinh')

@section('content')
@php
    $gradeFilters = ['all' => 'Tất cả', '10' => 'Khối 10', '11' => 'Khối 11', '12' => 'Khối 12'];
    $genderFilters = ['all' => 'Tất cả'] + \App\Models\Student::genderLabels();
    $statusFilters = ['all' => 'Tất cả'] + \App\Models\Student::statuses();
@endphp

<x-page-header
    title="Quản lý học sinh"
    subtitle="Khởi tạo, tra cứu hồ sơ lý lịch, quản lý trạng thái học tập và thông tin liên hệ của học sinh toàn trường."
>
	    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
	        @unless($readOnly)
            <x-bulk-excel-actions
                module="students"
                :context="[
                    'school_year_id' => $selectedYearId,
                    'class_id' => $selectedClassId !== 'all' ? $selectedClassId : null,
                ]"
            />
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentCreateModal">
                <i class="bi bi-plus-lg me-1"></i>Tiếp nhận học sinh mới
            </button>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Lọc học sinh" aria-label="Lọc học sinh">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('students.index') }}" class="row g-3">
                    <div class="col-12">
                        <label class="form-label small mb-1">Năm học</label>
                        <select name="school_year_id" class="form-select">
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Khối</label>
                        <select name="grade_level" class="form-select">
                            @foreach($gradeFilters as $value => $label)
                                <option value="{{ $value }}" @selected($selectedGrade === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Lớp</label>
                        <select name="class_id" class="form-select">
                            <option value="all" @selected($selectedClassId === 'all')>Tất cả</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Trạng thái</label>
                        <select name="status" class="form-select">
                            @foreach($statusFilters as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Giới tính</label>
                        <select name="gender" class="form-select">
                            @foreach($genderFilters as $value => $label)
                                <option value="{{ $value }}" @selected($selectedGender === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('students.index') }}" class="btn btn-secondary">Đặt lại</a>
                        <button class="btn btn-primary">Lọc</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã học sinh</th>
                    <th>Họ tên</th>
                    <th>Lớp</th>
                    <th>Phụ huynh</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php
                    $deleteCheck = $deleteChecks[(string) $student->getKey()] ?? ['allowed' => false, 'message' => null];
                    $loginLocked = (int) ($student->user?->login_status ?? 1) !== 1;
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $student->student_code }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center overflow-hidden" style="width: 38px; height: 38px;">
                                @if($student->avatar)
                                    <img src="{{ asset('storage/'.$student->avatar) }}" alt="{{ $student->name }}" class="w-100 h-100 object-fit-cover">
                                @else
                                    <i class="bi bi-person text-muted"></i>
                                @endif
                            </div>
                            <div>
                                <div class="fw-semibold d-flex align-items-center gap-1.5 text-left">
                                    <span class="{{ $loginLocked ? 'text-red-600' : 'text-green-600' }} text-xs leading-none">{{ $loginLocked ? '🔴' : '🟢' }}</span>
                                    <span>{{ $student->name }}</span>
                                </div>
                                <div class="text-muted small">{{ $student->genderLabel() }}{{ $student->dob ? ' - '.$student->dob->format('d/m/Y') : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $student->classRoom->name ?? '-' }}</td>
                    <td>
                        <div>{{ $student->parent_phone ?: '-' }}</div>
                    </td>
	                    <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
	                    <td class="text-end">
	                        <div class="content-action-group justify-content-end">
	                            @unless($readOnly)
	                                <button type="button" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="modal" data-bs-target="#studentEdit{{ $student->id }}">
	                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
	                                </button>
	                            @endunless
	                            <div class="dropdown">
	                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
	                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
	                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#studentDetail{{ $student->id }}">
	                                        <i class="bi bi-eye me-2"></i>Xem chi tiết
	                                    </button>
	                                    @unless($readOnly)
                                            <form action="{{ route('students.reset-password', $student) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn đặt lại mật khẩu cho học sinh này?');">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-key me-2"></i>Đặt lại mật khẩu
                                                </button>
                                            </form>
                                            <form action="{{ route('students.toggle-login', $student) }}" method="POST" onsubmit="return confirm('{{ $loginLocked ? 'Bạn có chắc muốn mở khóa tài khoản đăng nhập học sinh này?' : 'Bạn có chắc muốn khóa tài khoản đăng nhập học sinh này?' }}');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item text-left {{ $loginLocked ? 'text-success' : 'text-orange-700' }}">
                                                    <i class="bi {{ $loginLocked ? 'bi-unlock' : 'bi-lock' }} me-2"></i>{{ $loginLocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
                                                </button>
                                            </form>
	                                        @if($deleteCheck['allowed'])
	                                            <div class="dropdown-divider"></div>
	                                            <form action="{{ route('students.destroy', $student) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa học sinh này? Hành động này không thể hoàn tác.');">
	                                                @csrf
	                                                @method('DELETE')
	                                                <button type="submit" class="dropdown-item text-danger">
	                                                    <i class="bi bi-trash me-2"></i>Xóa
	                                                </button>
	                                            </form>
	                                        @endif
	                                    @endunless
	                                </div>
	                            </div>
	                        </div>
	                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-person-dash"></i>Chưa có học sinh.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<?php if (! $readOnly): ?>
    <div class="modal fade student-form-modal" id="studentCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Tiếp nhận học sinh mới</h5>
                        <div class="text-muted small">Nhập đầy đủ thông tin học tập, cá nhân và liên hệ phụ huynh.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php echo $__env->make('students.partials.form', [
                        'action' => route('students.store'),
                        'student' => null,
                        'primaryParent' => null,
                        'classes' => $importClasses,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

@foreach($students as $student)
    <?php if (! $readOnly): ?>
        <?php
            $studentEditClasses = $importClasses;
            if ($student->classRoom && ! $studentEditClasses->contains('id', $student->class_id)) {
                $studentEditClasses = $studentEditClasses->concat([$student->classRoom])
                    ->sortBy([
                        ['grade_level', 'asc'],
                        ['name', 'asc'],
                    ])
                    ->values();
            }
        ?>
        <div class="modal fade student-form-modal" id="studentEdit{{ $student->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Chỉnh sửa thông tin học sinh</h5>
                            <div class="text-muted small">{{ $student->student_code }} - {{ $student->name }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $__env->make('students.partials.form', [
                            'action' => route('students.update', $student),
                            'student' => $student,
                            'primaryParent' => $student->parents->first(),
                            'classes' => $studentEditClasses,
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade content-modal profile-detail-modal student-profile-modal" id="studentDetail{{ $student->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="student-v2-kicker">Hồ sơ học sinh</div>
                        <h5 class="modal-title">Xem chi tiết</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="student-v2-shell">
                        <div class="student-v2-card-header">
                            <div class="student-v2-avatar">
                                @if($student->avatar)
                                    <img src="{{ asset('storage/'.$student->avatar) }}" alt="{{ $student->name }}">
                                @else
                                    <i class="bi bi-person-fill"></i>
                                @endif
                            </div>
                            <div class="student-v2-identity">
                                <div class="student-v2-kicker">Thẻ học sinh</div>
                                <h5>{{ $student->name }}</h5>
                                <div class="student-v2-code">{{ $student->student_code }}</div>
                            </div>
                            <span class="student-v2-status badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
                        </div>

                        <div class="student-compact-detail-grid">
                            <section class="student-compact-box">
                                <div class="student-compact-title">
                                    <i class="bi bi-mortarboard"></i>
                                    <h6>Thông tin học tập</h6>
                                </div>
                                <div class="student-compact-list">
                                    <div><span>Niên khóa</span><strong>{{ $student->cohortLabel() }}</strong></div>
                                    <div><span>Lớp</span><strong>{{ $student->classRoom->name ?? '-' }}</strong></div>
                                    <div><span>Loại nhập học</span><strong>{{ $student->admissionTypeLabel() }}</strong></div>
                                    <div><span>Ngày nhập học</span><strong>{{ $student->enrollment_date?->format('d/m/Y') ?? '-' }}</strong></div>
                                </div>
                            </section>

                            <section class="student-compact-box wide">
                                <div class="student-compact-title">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <h6>Thông tin cá nhân & liên hệ</h6>
                                </div>
                                <div class="student-compact-list two">
                                    <div><span>SĐT phụ huynh</span><strong>{{ $student->parent_phone ?: '-' }}</strong></div>
                                    <div><span>Ngày sinh</span><strong>{{ $student->dob?->format('d/m/Y') ?? '-' }}</strong></div>
                                    <div><span>Giới tính</span><strong>{{ $student->genderLabel() }}</strong></div>
                                    <div><span>Nơi sinh</span><strong>{{ $student->place_of_birth ?: '-' }}</strong></div>
                                    <div><span>Dân tộc</span><strong>{{ $student->ethnicity ?: '-' }}</strong></div>
                                    <div><span>Tôn giáo</span><strong>{{ $student->religion ?: '-' }}</strong></div>
                                    <div class="full"><span>Địa chỉ</span><strong>{{ $student->address ?: '-' }}</strong></div>
                                    <div class="full"><span>Ghi chú</span><strong>{{ $student->note ?: '-' }}</strong></div>
                                </div>
                            </section>

                            @if($student->admission_type === \App\Models\Student::ADMISSION_TRANSFER)
                                <section class="student-compact-box full">
                                    <div class="student-compact-title">
                                        <i class="bi bi-arrow-left-right"></i>
                                        <h6>Chuyển trường</h6>
                                    </div>
                                    <div class="student-compact-list two">
                                        <div><span>Trường cũ</span><strong>{{ $student->previous_school ?: '-' }}</strong></div>
                                        <div><span>Khối hiện tại</span><strong>{{ $student->transfer_grade_level ? 'Khối '.$student->transfer_grade_level : '-' }}</strong></div>
                                    </div>
                                </section>
                            @endif
                        </div>

                        <div class="student-v2-main-grid">
                            <article>
                                <i class="bi bi-calendar3"></i>
                                <span>Niên khóa</span>
                                <strong>{{ $student->cohortLabel() }}</strong>
                            </article>
                            <article>
                                <i class="bi bi-building"></i>
                                <span>Lớp</span>
                                <strong>{{ $student->classRoom->name ?? '-' }}</strong>
                            </article>
                            <article>
                                <i class="bi bi-calendar-event"></i>
                                <span>Ngày sinh</span>
                                <strong>{{ $student->dob?->format('d/m/Y') ?? '-' }}</strong>
                            </article>
                            <article>
                                <i class="bi bi-person-badge"></i>
                                <span>Giới tính</span>
                                <strong>{{ $student->genderLabel() }}</strong>
                            </article>
                        </div>

                        <div class="student-v2-sections">
                            <section class="student-v2-section">
                                <div class="student-v2-section-title">
                                    <i class="bi bi-mortarboard"></i>
                                    <h6>Thông tin học tập</h6>
                                </div>
                                <div class="student-v2-section-grid">
                                    <article>
                                        <span>Loại nhập học</span>
                                        <strong>{{ $student->admissionTypeLabel() }}</strong>
                                    </article>
                                    <article>
                                        <span>Ngày nhập học</span>
                                        <strong>{{ $student->enrollment_date?->format('d/m/Y') ?? '-' }}</strong>
                                    </article>
                                </div>
                            </section>

                            <section class="student-v2-section">
                                <div class="student-v2-section-title">
                                    <i class="bi bi-people"></i>
                                    <h6>Thông tin gia đình</h6>
                                </div>
                                <div class="student-v2-section-grid">
                                    <article>
                                        <span>SĐT phụ huynh</span>
                                        <strong>{{ $student->parent_phone ?: '-' }}</strong>
                                    </article>
                                </div>
                            </section>

                            <section class="student-v2-section">
                                <div class="student-v2-section-title">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <h6>Thông tin cá nhân</h6>
                                </div>
                                <div class="student-v2-section-grid three">
                                    <article>
                                        <span>Nơi sinh</span>
                                        <strong>{{ $student->place_of_birth ?: '-' }}</strong>
                                    </article>
                                    <article>
                                        <span>Dân tộc</span>
                                        <strong>{{ $student->ethnicity ?: '-' }}</strong>
                                    </article>
                                    <article>
                                        <span>Tôn giáo</span>
                                        <strong>{{ $student->religion ?: '-' }}</strong>
                                    </article>
                                </div>
                            </section>

                            <section class="student-v2-section">
                                <div class="student-v2-section-title">
                                    <i class="bi bi-journal-text"></i>
                                    <h6>Thông tin khác</h6>
                                </div>
                                <div class="student-v2-section-grid">
                                    <article class="wide">
                                        <span>Địa chỉ</span>
                                        <strong>{{ $student->address ?: '-' }}</strong>
                                    </article>
                                    <article class="wide">
                                        <span>Ghi chú</span>
                                        <strong>{{ $student->note ?: '-' }}</strong>
                                    </article>
                                </div>
                            </section>

                            @if($student->admission_type === \App\Models\Student::ADMISSION_TRANSFER)
                                <section class="student-v2-section transfer">
                                    <div class="student-v2-section-title">
                                        <i class="bi bi-arrow-left-right"></i>
                                        <h6>Thông tin chuyển trường</h6>
                                    </div>
                                    <div class="student-v2-section-grid">
                                        <article>
                                            <span>Trường cũ</span>
                                            <strong>{{ $student->previous_school ?: '-' }}</strong>
                                        </article>
                                        <article>
                                            <span>Khối hiện tại</span>
                                            <strong>{{ $student->transfer_grade_level ? 'Khối '.$student->transfer_grade_level : '-' }}</strong>
                                        </article>
                                    </div>
                                </section>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('students.partials.class-year-script')
@endsection
