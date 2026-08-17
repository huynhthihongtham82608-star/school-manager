@extends('layouts.app')
@section('title', 'Học phí & Khoản thu')

@section('content')
<style>
    .tuition-portal,
    .tuition-portal * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .tuition-portal-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .tuition-portal-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-portal-table th {
        padding: .85rem .75rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 500;
        background: rgba(255, 247, 237, .7);
        border-bottom: 1px solid rgba(254, 215, 170, .7);
    }

    .tuition-portal-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-portal-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .18);
    }

    .tuition-portal-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .tuition-portal-badge.paid,
    .tuition-portal-badge.exempt {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .tuition-portal-badge.unpaid {
        color: #b91c1c;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .tuition-portal-filter {
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

    .tuition-qr-box {
        width: 100%;
        aspect-ratio: 1 / 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #9a3412;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 14px;
    }

    .tuition-qr-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #fff;
    }
</style>

<div class="tuition-portal w-full text-left font-sans">
    <x-page-header
        title="Học phí & Khoản thu"
        subtitle="Theo dõi các khoản thu của học sinh theo cấu hình hiện hành của nhà trường."
    />

    <form method="GET" action="{{ route('parent.tuition-fees.index') }}" class="w-full flex flex-wrap items-center gap-3 bg-white p-3.5 rounded-xl border border-orange-100 shadow-2xs text-left mb-4">
        <select name="semester_id" class="tuition-portal-filter" onchange="this.form.submit()">
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                    {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                </option>
            @endforeach
        </select>
        @if($student)
            <span class="text-sm font-normal text-orange-700/80">Học sinh: {{ $student->student_code }} - {{ $student->name }} • Lớp {{ $student->classRoom->name ?? '-' }}</span>
        @endif
    </form>

    <div class="flex flex-col lg:flex-row gap-6 w-full text-left">
        <div class="w-full lg:w-2/3 tuition-portal-card">
            <table class="tuition-portal-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">STT</th>
                        <th style="width: 34%;">Khoản thu</th>
                        <th style="width: 20%;">Số tiền</th>
                        <th style="width: 20%;">Diện đối tượng</th>
                        <th style="width: 18%;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeItems as $index => $item)
                        @php
                            $status = $item['status'] ?? \App\Models\TuitionFee::STATUS_UNPAID;
                            $exemption = $item['exemption_label'] ?? '';
                        @endphp
                        <tr>
                            <td class="text-gray-500">{{ $index + 1 }}</td>
                            <td>{{ $item['label'] ?? '-' }}</td>
                            <td>{{ number_format((float) ($item['amount'] ?? 0), 0, ',', '.') }}đ</td>
                            <td>
                                <span class="text-xs font-normal text-gray-500">{{ $exemption ?: ($fee?->exemptionLabel() ?? 'Mặc định') }}</span>
                            </td>
                            <td>
                                @if($exemption)
                                    <span class="tuition-portal-badge exempt">{{ $exemption }}</span>
                                @else
                                    <span class="tuition-portal-badge {{ $status }}">
                                        {{ $status === \App\Models\TuitionFee::STATUS_PAID ? '🟢 Đã đóng' : '🔴 Chưa đóng' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-gray-400">Chưa có khoản thu để hiển thị.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="w-full lg:w-1/3 bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left">
            <h2 class="text-base font-semibold text-gray-900 mb-1 text-left">QR chuyển khoản</h2>
            <p class="text-sm font-normal text-orange-700/70 mb-4 text-left">Phụ huynh quét mã nhận tiền toàn trường do Admin cấu hình.</p>
            <div class="tuition-qr-box">
                @if($qrImageUrl)
                    <img src="{{ $qrImageUrl }}" alt="QR nhận tiền học phí">
                @else
                    <span class="text-sm font-normal">Chưa có QR</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
