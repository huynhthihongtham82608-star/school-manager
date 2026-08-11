@extends('layouts.app')
@section('title', 'Hồ sơ lớp chủ nhiệm')

@section('content')
<style>
    .homeroom-profile-modal {
        max-width: min(1024px, calc(100vw - 2rem));
    }

    .homeroom-profile-table th,
    .homeroom-profile-table td {
        color: #374151;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .homeroom-profile-table th,
        .homeroom-profile-table td {
            font-size: .875rem;
        }
    }

    .homeroom-profile-table th {
        background: rgba(255, 247, 237, .42);
        color: #111827;
    }

    .homeroom-profile-card {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .homeroom-detail-box {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: rgba(255, 247, 237, .18);
        padding: .85rem;
        text-align: left;
    }

    .homeroom-detail-title {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .65rem;
        color: #111827;
        font-size: .78rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .homeroom-detail-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .45rem .85rem;
        color: #374151;
        font-size: .78rem;
        font-weight: 400;
        text-align: left;
    }

    .homeroom-detail-list .full {
        grid-column: 1 / -1;
    }

    .homeroom-detail-list span {
        display: block;
        color: #6b7280;
        font-weight: 400;
    }

    .homeroom-detail-list strong {
        display: block;
        min-width: 0;
        color: #111827;
        font-weight: 400;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="page-heading flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h5 class="text-xl font-normal text-gray-900 mb-1">Hồ sơ sơ yếu lý lịch Lớp {{ $homeroomClass?->name ?? 'chủ nhiệm' }}</h5>
        <div class="text-xs text-gray-500 font-normal">
            {{ $homeroomClass?->schoolYear?->name ?? '-' }} • {{ $homeroomClass?->semester?->name ?? '-' }} • Sĩ số: {{ $homeroomClass?->students->count() ?? 0 }} học sinh
        </div>
    </div>
</div>

@if(! $homeroomClass)
    <div class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg text-sm text-left font-normal">
        Bạn chưa được phân công làm Giáo viên chủ nhiệm cho lớp học nào trong hệ thống.
    </div>
@else
    <div class="homeroom-profile-card overflow-hidden">
        <div class="p-3 bg-orange-50/20 border-b border-orange-100 flex items-center justify-between">
            <h6 class="text-sm font-normal text-gray-900 mb-0">Danh sách học sinh Lớp {{ $homeroomClass->name }}</h6>
            <div class="text-xs text-gray-500 font-normal">GVCN: {{ auth()->user()->display_name }}</div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle w-full table-fixed mb-0 homeroom-profile-table" data-admin-table-skip>
                <thead>
                    <tr>
                        <th class="w-36">Mã HS</th>
                        <th>Họ và tên</th>
                        <th class="w-36">Ngày sinh</th>
                        <th class="w-44">SĐT phụ huynh</th>
                        <th>Địa chỉ thường trú</th>
                        <th class="text-end w-24">Hồ sơ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-orange-100/60">
                @forelse($homeroomClass->students as $student)
                    <tr class="hover:bg-orange-50/20 transition-colors">
                        <td class="text-gray-900" title="{{ $student->student_code }}">{{ $student->student_code }}</td>
                        <td title="{{ $student->name }}">{{ $student->name }}</td>
                        <td>{{ $student->dob?->format('d/m/Y') ?? '-' }}</td>
                        <td title="{{ $student->parent_phone ?: '-' }}">{{ $student->parent_phone ?: '-' }}</td>
                        <td title="{{ $student->address }}">{{ $student->address ?: '-' }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center p-1.5 text-gray-500 hover:text-orange-600 bg-white border border-gray-200 hover:border-orange-300 rounded-md shadow-xs transition-colors cursor-pointer"
                                data-bs-toggle="modal"
                                data-bs-target="#studentProfile{{ $student->id }}"
                                title="Xem hồ sơ học sinh"
                                aria-label="Xem hồ sơ học sinh"
                            >
                                <i class="bi bi-eye text-base"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-gray-500 font-normal">Lớp chủ nhiệm chưa có học sinh nào.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($homeroomClass->students as $student)
        <div class="modal fade content-modal" id="studentProfile{{ $student->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered homeroom-profile-modal">
                <div class="modal-content max-w-4xl bg-white shadow-2xl rounded-xl z-50 animate-fade-in-up border-0 overflow-hidden">
                    <div class="modal-header bg-orange-50/60 border-b border-orange-100 px-4 py-3">
                        <div>
                            <div class="text-xs text-orange-700 font-normal">Hồ sơ học sinh</div>
                            <h5 class="modal-title text-base font-normal text-gray-900">Xem chi tiết - {{ $student->name }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body p-4 text-left font-normal text-sm">
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
                                        <div><span>Lớp</span><strong>{{ $student->classRoom->name ?? $homeroomClass->name }}</strong></div>
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

                                <section class="student-compact-box full">
                                    <div class="student-compact-title">
                                        <i class="bi bi-people"></i>
                                        <h6>Phụ huynh / người giám hộ</h6>
                                    </div>
                                    <div class="student-compact-list two">
                                        @forelse($student->parents as $parent)
                                            <div>
                                                <span>{{ $parent->pivot?->relation ?: 'Phụ huynh' }}</span>
                                                <strong>{{ $parent->name }}{{ $parent->phone ? ' • ' . $parent->phone : '' }}</strong>
                                            </div>
                                            <div>
                                                <span>Email</span>
                                                <strong>{{ $parent->email ?: '-' }}</strong>
                                            </div>
                                        @empty
                                            <div class="full"><span>Liên kết</span><strong>Chưa liên kết hồ sơ phụ huynh.</strong></div>
                                        @endforelse
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
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 border-t border-gray-200 px-4 py-2 flex justify-end">
                        <button type="button" class="btn btn-secondary text-xs px-3 py-1.5" data-bs-dismiss="modal">Đóng hồ sơ</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
