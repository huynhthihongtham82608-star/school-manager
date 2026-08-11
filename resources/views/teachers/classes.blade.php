@extends('layouts.app')
@section('title', 'Lớp giảng dạy')

@section('content')
<style>
    .teacher-class-shell {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .teacher-class-table th,
    .teacher-class-table td,
    .teacher-student-modal-table th,
    .teacher-student-modal-table td {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .teacher-class-table th,
    .teacher-student-modal-table th {
        background: rgba(255, 247, 237, .42);
        color: #111827;
    }

    .teacher-class-table td,
    .teacher-student-modal-table td {
        border-color: rgba(255, 237, 213, .72);
    }

    .teacher-class-action {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .45rem .7rem;
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        font-size: .875rem;
        font-weight: 400;
        text-decoration: none;
        transition: all .16s ease;
        white-space: nowrap;
    }

    .teacher-class-action:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .teacher-student-modal {
        display: none;
    }

    .teacher-student-modal.is-open {
        display: flex;
    }

    .teacher-student-modal-close {
        color: #6b7280;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: .45rem .85rem;
        font-size: .875rem;
        font-weight: 400;
    }

    .teacher-student-status {
        display: inline-flex;
        align-items: center;
        padding: .24rem .58rem;
        border-radius: 999px;
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        font-size: .75rem;
        font-weight: 400;
    }
</style>

<div class="page-heading flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h5 class="text-xl font-normal text-gray-900 mb-1">Lớp đang giảng dạy</h5>
        <div class="text-sm text-gray-500 font-normal">Danh sách lớp được phân công tiết dạy bộ môn thực tế.</div>
    </div>
</div>

<div class="teacher-class-shell">
    <div class="table-responsive">
        <table class="table align-middle mb-0 teacher-class-table" data-admin-table-skip>
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Khối</th>
                    <th>Năm học</th>
                    <th>Học kỳ</th>
                    <th>Sĩ số</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td class="text-gray-900" title="{{ $class->name }}">{{ $class->name }}</td>
                    <td>Khối {{ $class->grade_level }}</td>
                    <td title="{{ $class->schoolYear?->name ?? '-' }}">{{ $class->schoolYear?->name ?? '-' }}</td>
                    <td title="{{ $class->semester?->name ?? '-' }}">{{ $class->semester?->name ?? '-' }}</td>
                    <td>{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</td>
                    <td class="text-end">
                        <button type="button" class="teacher-class-action" data-open-student-modal="teacherClassStudents{{ $loop->iteration }}" title="Xem danh sách học sinh">
                            <i class="bi bi-people"></i>
                            <span>Xem học sinh</span>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state"><i class="bi bi-people"></i>Chưa có lớp được phân công.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($classes as $class)
    <div id="teacherClassStudents{{ $loop->iteration }}" class="teacher-student-modal fixed inset-0 bg-black/40 items-center justify-center z-50 animate-fade-in-up" data-student-modal aria-hidden="true">
        <div class="w-full max-w-3xl bg-white rounded-xl shadow-2xl border border-orange-100 p-5 text-left">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="text-lg font-normal text-gray-900 mb-1">Danh sách học sinh lớp {{ $class->name }}</h5>
                    <div class="text-sm text-gray-500 font-normal">{{ $class->students->count() }} học sinh trong danh sách lớp bộ môn.</div>
                </div>
                <button type="button" class="teacher-student-modal-close" data-close-student-modal>Đóng</button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 teacher-student-modal-table" data-admin-table-skip>
                    <thead>
                        <tr>
                            <th style="width: 72px;">STT</th>
                            <th>Mã Học Sinh</th>
                            <th>Họ và Tên</th>
                            <th>Trạng thái học vụ</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($class->students as $student)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $student->student_code }}</td>
                            <td>{{ $student->name }}</td>
                            <td>
                                <span class="teacher-student-status">
                                    {{ $student->status === \App\Models\Student::STATUS_STUDYING ? 'Đang học' : $student->statusLabel() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state"><i class="bi bi-person-dash"></i>Chưa có học sinh trong lớp này.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="teacher-student-modal-close" data-close-student-modal>Đóng</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-open-student-modal]');
        if (openButton) {
            const modal = document.getElementById(openButton.dataset.openStudentModal);
            modal?.classList.add('is-open');
            modal?.setAttribute('aria-hidden', 'false');
            return;
        }

        const closeButton = event.target.closest('[data-close-student-modal]');
        if (closeButton) {
            const modal = closeButton.closest('[data-student-modal]');
            modal?.classList.remove('is-open');
            modal?.setAttribute('aria-hidden', 'true');
            return;
        }

        const backdrop = event.target.closest('[data-student-modal]');
        if (backdrop && event.target === backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('aria-hidden', 'true');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-student-modal].is-open').forEach((modal) => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
    });
</script>
@endsection
