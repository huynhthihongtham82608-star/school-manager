@extends('layouts.app')
@section('title', 'Học phí lớp chủ nhiệm')

@section('content')
<style>
    .tuition-homeroom,
    .tuition-homeroom * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .tuition-homeroom-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .tuition-homeroom-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-homeroom-table th {
        padding: .85rem .75rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 500;
        background: rgba(255, 247, 237, .7);
        border-bottom: 1px solid rgba(254, 215, 170, .7);
    }

    .tuition-homeroom-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-homeroom-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .18);
    }

    .tuition-homeroom-table tbody tr:hover {
        background: rgba(255, 247, 237, .45);
    }

    .tuition-homeroom-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .tuition-homeroom-badge.paid {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .tuition-homeroom-badge.unpaid {
        color: #b91c1c;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .tuition-homeroom-filter {
        min-width: 170px;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        background: #f9fafb;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: .45rem .7rem;
        outline: none;
    }
</style>

<div class="tuition-homeroom w-full text-left font-sans">
    <x-page-header
        title="Học phí lớp chủ nhiệm"
        subtitle="Theo dõi tổng hợp học phí và khoản thu của học sinh lớp chủ nhiệm."
    />

    <form method="GET" action="{{ route('teacher.tuition-fees.homeroom') }}" class="w-full flex flex-wrap items-center gap-3 bg-white p-3.5 rounded-xl border border-orange-100 shadow-2xs text-left mb-4">
        <select name="semester_id" class="tuition-homeroom-filter" onchange="this.form.submit()">
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                    {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                </option>
            @endforeach
        </select>
        @if($homeroomClass)
            <span class="text-sm font-normal text-orange-700/80">Phạm vi theo dõi: Lớp chủ nhiệm {{ $homeroomClass->name }}</span>
        @endif
    </form>

    <div class="tuition-homeroom-card">
        <table class="tuition-homeroom-table">
            <thead>
                <tr>
                    <th style="width: 6%;">STT</th>
                    <th style="width: 12%;">Mã HS</th>
                    <th style="width: 28%;">Họ và Tên</th>
                    <th style="width: 12%;">Lớp</th>
                    <th style="width: 22%;">Tổng tiền phải đóng</th>
                    <th style="width: 20%;">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fees as $index => $fee)
                    @php
                        $items = $fee->normalizedFeeItems();
                        $total = collect($items)->sum('amount');
                        $computedStatus = collect($items)->every(fn (array $item) => ($item['status'] ?? null) === \App\Models\TuitionFee::STATUS_PAID)
                            ? \App\Models\TuitionFee::STATUS_PAID
                            : \App\Models\TuitionFee::STATUS_UNPAID;
                    @endphp
                    <tr>
                        <td class="text-gray-500">{{ $index + 1 }}</td>
                        <td>{{ $fee->student->student_code ?? '-' }}</td>
                        <td>{{ $fee->student->name ?? '-' }}</td>
                        <td>{{ $fee->classRoom->name ?? '-' }}</td>
                        <td>{{ number_format((float) $total, 0, ',', '.') }}đ</td>
                        <td>
                            <span class="tuition-homeroom-badge {{ $computedStatus }}">
                                {{ $computedStatus === \App\Models\TuitionFee::STATUS_PAID ? '🟢 Đã đóng' : '🔴 Chưa đóng' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-gray-400">Chưa có dữ liệu học phí của lớp chủ nhiệm.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
