@extends('layouts.app')
@section('title', 'Lớp giảng dạy')

@section('content')
<style>
    .teacher-class-shell,
    .teacher-student-modal-panel {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }

    .teacher-class-shell {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .teacher-class-table,
    .teacher-student-modal-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        overflow: hidden;
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
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .teacher-class-table th,
    .teacher-student-modal-table th {
        background: rgba(255, 247, 237, .42);
        color: #111827;
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
        white-space: nowrap;
    }

    .teacher-student-modal {
        position: fixed;
        inset: 0;
        display: none !important;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, .4);
        z-index: 50;
    }

    .teacher-student-modal.is-open {
        display: flex !important;
    }

    .teacher-student-modal-panel {
        max-width: 32rem;
        max-height: min(78vh, 620px);
        overflow: hidden;
    }

    .teacher-student-modal-body {
        max-height: 360px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: .25rem;
        scrollbar-width: thin;
    }

    .teacher-student-modal-close {
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        padding: .375rem .75rem;
        font-size: .75rem;
        font-weight: 400;
        transition: background-color .16s ease, color .16s ease;
        cursor: pointer;
    }

    .teacher-student-modal-close:hover {
        background: #ffedd5;
    }

    .teacher-student-modal-table th,
    .teacher-student-modal-table td {
        font-size: .75rem;
        padding: .6rem .65rem;
    }

    .teacher-student-modal-table thead th {
        background: rgba(255, 247, 237, .3) !important;
        color: #9a3412 !important;
        font-weight: 500 !important;
        font-size: .75rem !important;
        padding: .5rem .75rem !important;
        text-align: left !important;
    }

    .teacher-student-modal-table tbody tr:nth-child(odd) {
        background: #fff;
    }

    .teacher-student-modal-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .1);
    }

    .teacher-student-modal-table tbody tr:hover {
        background: rgba(255, 247, 237, .3);
    }

    .teacher-student-status {
        display: inline-block;
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        padding: .125rem .5rem;
        font-size: .75rem;
        font-weight: 400;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .teacher-student-modal-table th,
        .teacher-student-modal-table td {
            font-size: .875rem;
        }
    }

    .animate-scale-up {
        animation: teacherModalScaleUp .16s ease-out;
    }

    @keyframes teacherModalScaleUp {
        from {
            opacity: .88;
            transform: scale(.97);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div class="page-heading w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
    <div class="w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
        <h5 class="text-xl font-semibold text-gray-900 !text-left" style="text-align: left !important;">Lớp đang giảng dạy</h5>
        <div class="text-sm font-normal text-gray-400 mt-1 !text-left" style="text-align: left !important;">Danh sách lớp được phân công tiết dạy bộ môn thực tế.</div>
    </div>
</div>

<div class="teacher-class-shell">
    <div class="w-full max-w-full overflow-hidden">
        <table class="table align-middle mb-0 teacher-class-table w-full table-fixed max-w-full overflow-hidden" data-admin-table-skip>
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
                    <td title="{{ $class->name }}">{{ $class->name }}</td>
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
                    <td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>Chưa có lớp được phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($classes as $class)
    <div id="teacherClassStudents{{ $loop->iteration }}" class="teacher-student-modal fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 animate-fade-in" data-student-modal aria-hidden="true">
        <div class="teacher-student-modal-panel w-full max-w-lg bg-white border border-orange-100 p-5 rounded-xl shadow-2xl flex flex-col gap-4 text-left animate-scale-up">
            <div class="bg-orange-50/60 p-3 rounded-t-xl border-b border-orange-100/70 flex items-center justify-between">
                <div class="min-w-0 text-left">
                    <h5 class="text-base font-semibold text-gray-900 mb-1">📊 Danh sách học sinh lớp bộ môn</h5>
                    <div class="text-xs text-gray-500 font-normal">{{ $class->name }} • {{ $class->students->count() }} học sinh</div>
                </div>
            </div>

            <div class="teacher-student-modal-body w-full max-h-[360px] overflow-y-auto pr-1 flex flex-col gap-2 scrollbar-thin">
                <table class="table align-middle mb-0 teacher-student-modal-table w-full table-fixed max-w-full overflow-hidden" data-admin-table-skip>
                    <thead>
                        <tr>
                            <th class="bg-orange-50/30 text-orange-800 font-medium text-xs py-2 px-3 text-left" style="width: 56px;">STT</th>
                            <th class="bg-orange-50/30 text-orange-800 font-medium text-xs py-2 px-3 text-left">Mã Học Sinh</th>
                            <th class="bg-orange-50/30 text-orange-800 font-medium text-xs py-2 px-3 text-left">Họ và Tên</th>
                            <th class="bg-orange-50/30 text-orange-800 font-medium text-xs py-2 px-3 text-left">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($class->students as $student)
                        <tr class="odd:bg-white even:bg-orange-50/10 hover:bg-orange-50/30 transition-colors">
                            <td class="text-xs md:text-sm font-normal text-gray-700 text-left">{{ $loop->iteration }}</td>
                            <td class="text-xs md:text-sm font-normal text-gray-700 text-left" title="{{ $student->student_code }}">{{ $student->student_code }}</td>
                            <td class="text-xs md:text-sm font-normal text-gray-700 text-left" title="{{ $student->name }}">{{ $student->name }}</td>
                            <td class="text-left" style="overflow: visible; text-overflow: clip;">
                                <span class="bg-green-50 text-green-700 border border-green-200 text-xs font-normal px-2.5 py-0.5 rounded-full inline-block whitespace-nowrap">🟢 Đang học</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><div class="empty-state"><i class="bi bi-person-dash"></i>Chưa có học sinh trong lớp này.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="w-full flex">
                <button type="button" class="teacher-student-modal-close text-xs font-normal text-orange-700 bg-orange-50 border border-orange-200 px-3 py-1.5 rounded-md hover:bg-orange-100 transition-colors ml-auto cursor-pointer" data-close-student-modal>Đóng</button>
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
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-student-modal].is-open').forEach((modal) => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
    });
</script>
@endsection
