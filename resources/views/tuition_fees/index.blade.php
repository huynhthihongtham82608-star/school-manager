@extends('layouts.app')
@section('title', 'Quản lý học phí')

@section('content')
<style>
    .tuition-page,
    .tuition-page * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .tuition-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .tuition-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
        margin-bottom: 1rem;
        padding: .875rem;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
    }

    .tuition-filter-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .tuition-filter,
    .tuition-input {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .45rem .7rem;
        outline: none;
    }

    .tuition-filter {
        min-width: 150px;
    }

    .tuition-filter:focus,
    .tuition-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .18rem rgba(255, 237, 213, .82);
    }

    .tuition-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin: 0;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-table th {
        padding: .85rem .75rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 500;
        background: rgba(255, 247, 237, .7);
        border-bottom: 1px solid rgba(254, 215, 170, .7);
    }

    .tuition-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .18);
    }

    .tuition-table tbody tr:hover {
        background: rgba(255, 247, 237, .45);
    }

    .tuition-status-pill,
    .tuition-item-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .tuition-status-pill.paid,
    .tuition-item-badge.paid,
    .tuition-item-badge.exempt {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .tuition-status-pill.unpaid,
    .tuition-item-badge.unpaid {
        color: #b91c1c;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .tuition-action-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .42rem .72rem;
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 400;
        cursor: pointer;
        transition: all .15s ease;
    }

    .tuition-action-btn:hover {
        background: #ffedd5;
        color: #9a3412;
    }

    .tuition-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem 1rem;
        color: #6b7280;
        font-size: .82rem;
        font-weight: 400;
    }

    #tuition-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 1050 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, .4) !important;
    }

    #tuition-modal.d-none {
        display: none !important;
    }

    #tuition-modal:not(.d-none) {
        display: flex !important;
    }

    .tuition-modal-card {
        width: 100%;
        max-width: 30rem;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
    }

    .tuition-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        align-items: center;
        width: 100%;
        padding: .7rem .75rem;
        background: rgba(255, 247, 237, .35);
        border: 1px solid rgba(254, 215, 170, .7);
        border-radius: 10px;
    }

    .tuition-qr-wrap {
        display: none;
        width: 100%;
        padding: .85rem;
        background: rgba(255, 247, 237, .42);
        border: 1px solid rgba(254, 215, 170, .8);
        border-radius: 12px;
    }

    .tuition-qr-wrap.is-visible {
        display: block;
    }

    .tuition-qr-demo {
        width: 132px;
        height: 132px;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        background:
            linear-gradient(90deg, #111827 10px, transparent 10px) 14px 14px / 38px 38px no-repeat,
            linear-gradient(#111827 10px, transparent 10px) 14px 14px / 38px 38px no-repeat,
            linear-gradient(90deg, #111827 10px, transparent 10px) 80px 14px / 38px 38px no-repeat,
            linear-gradient(#111827 10px, transparent 10px) 80px 14px / 38px 38px no-repeat,
            linear-gradient(90deg, #111827 10px, transparent 10px) 14px 80px / 38px 38px no-repeat,
            linear-gradient(#111827 10px, transparent 10px) 14px 80px / 38px 38px no-repeat,
            repeating-linear-gradient(45deg, #111827 0 4px, transparent 4px 8px),
            #fff7ed;
    }

    .tuition-qr-demo img,
    img.tuition-qr-demo {
        object-fit: contain;
        background: #fff;
    }
</style>

<div class="tuition-page w-full text-left font-sans">
    <x-page-header
        title="Quản lý học phí"
        subtitle="Theo dõi học phí và lệ phí của học sinh theo học kỳ, lớp học và trạng thái thu."
    />

    <form method="GET" action="{{ route('tuition-fees.index') }}" class="tuition-toolbar">
        <div class="tuition-filter-group">
            <select name="semester_id" class="tuition-filter" onchange="this.form.submit()">
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                        {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                    </option>
                @endforeach
            </select>

            <select name="class_id" class="tuition-filter" onchange="this.form.submit()">
                <option value="all" @selected($selectedClassId === 'all')>Tất cả lớp học</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>

            <select name="status" class="tuition-filter" onchange="this.form.submit()">
                <option value="all" @selected($selectedStatus === 'all')>Tất cả trạng thái</option>
                @foreach($statusLabels as $status => $label)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="tuition-card">
        <table class="tuition-table">
            <thead>
                <tr>
                    <th style="width: 6%;">STT</th>
                    <th style="width: 12%;">Mã HS</th>
                    <th style="width: 22%;">Họ và Tên</th>
                    <th style="width: 10%;">Lớp</th>
                    <th style="width: 18%;">Số tiền phải đóng</th>
                    <th style="width: 18%;">Trạng thái</th>
                    <th style="width: 14%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fees as $index => $fee)
                    @php
                        $feeItems = $fee->normalizedFeeItems();
                        $computedStatus = collect($feeItems)->every(fn (array $item) => ($item['status'] ?? null) === \App\Models\TuitionFee::STATUS_PAID)
                            ? \App\Models\TuitionFee::STATUS_PAID
                            : \App\Models\TuitionFee::STATUS_UNPAID;
                        $savedStatuses = collect($feeItems)->keyBy('key');
                        $modalItems = collect(\App\Models\TuitionFee::configuredFeeItems())
                            ->map(function (array $item) use ($savedStatuses) {
                                $saved = $savedStatuses->get($item['key'], []);

                                return [
                                    'key' => $item['key'],
                                    'label' => $item['label'],
                                    'amount' => $item['amount'],
                                    'status' => $saved['status'] ?? $item['status'],
                                ];
                            })
                            ->values()
                            ->all();
                    @endphp
                    <tr>
                        <td class="text-gray-500">{{ $index + 1 }}</td>
                        <td>{{ $fee->student->student_code ?? '-' }}</td>
                        <td>{{ $fee->student->name ?? '-' }}</td>
                        <td>{{ $fee->classRoom->name ?? '-' }}</td>
                        <td>{{ number_format((float) collect($feeItems)->sum('amount'), 0, ',', '.') }}đ</td>
                        <td>
                            <span class="tuition-status-pill {{ $computedStatus }}">
                                {{ $computedStatus === \App\Models\TuitionFee::STATUS_PAID ? '🟢 Đã đóng' : '🔴 Chưa đóng' }}
                            </span>
                        </td>
                        <td>
                            @if(! $readOnly)
                                <button
                                    type="button"
                                    class="tuition-action-btn"
                                    data-tuition-open
                                    data-action="{{ route('tuition-fees.update', $fee) }}"
                                    data-student="{{ e(($fee->student->student_code ?? '-') . ' - ' . ($fee->student->name ?? '-')) }}"
                                    data-class="{{ e($fee->classRoom->name ?? '-') }}"
                                    data-method="{{ $fee->payment_method ?: \App\Models\TuitionFee::PAYMENT_CASH }}"
                                    data-exemption="{{ $fee->exemption_type ?: \App\Models\TuitionFee::EXEMPTION_DEFAULT }}"
                                    data-note="{{ e($fee->note) }}"
                                    data-items='@json($modalItems)'
                                >
                                    <i class="bi bi-pencil-square"></i><span>Cập nhật</span>
                                </button>
                            @else
                                <span class="text-xs font-normal text-gray-400">Chỉ xem</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="bi bi-cash-coin"></i>Chưa có dữ liệu học phí.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="tuition-footer">
            <span>Hiển thị {{ $fees->count() }} dòng học phí</span>
        </div>
    </div>
</div>

@if(! $readOnly)
    <div id="tuition-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 font-sans font-normal d-none" aria-hidden="true">
        <div class="tuition-modal-card w-full max-w-md bg-white p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left border border-orange-100">
            <div class="w-full text-left">
                <h2 class="text-base font-semibold text-gray-900 text-left mb-1">Cập nhật học phí</h2>
                <p class="text-xs font-normal text-orange-700/70 text-left mb-0" data-tuition-student>Thông tin học sinh</p>
            </div>

            <form id="tuition-form" method="POST" class="flex flex-col gap-3 text-left">
                @csrf
                @method('PUT')

                <div id="tuition-items" class="flex flex-col gap-2 text-left"></div>

                <div class="bg-orange-50/40 border border-orange-100 rounded-xl p-3 text-left">
                    <div class="text-xs font-normal text-gray-500 mb-1">Tổng tiền phải đóng</div>
                    <div class="text-lg font-semibold text-orange-700 text-left" data-tuition-total>0đ</div>
                </div>

                <div class="text-left">
                    <label for="tuition-exemption-type" class="form-label text-sm font-normal text-gray-700">Diện đối tượng thu</label>
                    <select id="tuition-exemption-type" name="exemption_type" class="tuition-input w-full" required>
                        @foreach($exemptionLabels as $exemption => $label)
                            <option value="{{ $exemption }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-left">
                    <label for="tuition-payment-method" class="form-label text-sm font-normal text-gray-700">Phương thức thu</label>
                    <select id="tuition-payment-method" name="payment_method" class="tuition-input w-full" required>
                        @foreach($paymentMethodLabels as $method => $label)
                            <option value="{{ $method }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="tuition-qr-wrap" class="tuition-qr-wrap text-left">
                    <div class="text-sm font-normal text-orange-900 mb-2">QR chuyển khoản</div>
                    @if($qrImageUrl ?? null)
                        <img src="{{ $qrImageUrl }}" alt="QR nhận tiền học phí" class="tuition-qr-demo">
                    @else
                        <div class="tuition-qr-demo" aria-label="QR chuyển khoản demo"></div>
                    @endif
                    <p class="text-xs font-normal text-gray-500 mt-2 mb-0">Vui lòng quét mã để tự động gạch nợ học phí.</p>
                </div>

                <div class="text-left">
                    <label for="tuition-note" class="form-label text-sm font-normal text-gray-700">Ghi chú</label>
                    <textarea id="tuition-note" name="note" class="tuition-input w-full" rows="2" placeholder="Nội dung đối soát nếu có"></textarea>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" data-tuition-close>Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Xác nhận thu tiền</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('tuition-modal');
    const form = document.getElementById('tuition-form');
    const studentLabel = document.querySelector('[data-tuition-student]');
    const itemsWrap = document.getElementById('tuition-items');
    const methodInput = document.getElementById('tuition-payment-method');
    const exemptionInput = document.getElementById('tuition-exemption-type');
    const qrWrap = document.getElementById('tuition-qr-wrap');
    const noteInput = document.getElementById('tuition-note');
    const totalLabel = document.querySelector('[data-tuition-total]');
    let currentBaseItems = [];

    if (!modal || !form || !itemsWrap || !methodInput || !exemptionInput) {
        return;
    }

    const money = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';

    const syncQr = () => {
        qrWrap?.classList.toggle('is-visible', methodInput.value === 'transfer');
    };

    const itemBadge = (status, exemptionLabel = '') => {
        if (exemptionLabel) {
            return `<span class="tuition-item-badge exempt" data-item-badge>${exemptionLabel}</span>`;
        }

        const paid = status === 'paid';
        return `<span class="tuition-item-badge ${paid ? 'paid' : 'unpaid'}" data-item-badge>${paid ? '🟢 Đã đóng' : '🔴 Chưa đóng'}</span>`;
    };

    const applyExemption = (items) => {
        const exemption = exemptionInput.value;
        return items.map((item) => {
            const next = { ...item, exemption_label: '' };
            if (next.key !== 'health_insurance') {
                return next;
            }

            if (exemption === 'policy_exempt') {
                next.amount = 0;
                next.status = 'paid';
                next.exemption_label = '🟢 Miễn đóng BHYT';
            }

            if (exemption === 'local_paid') {
                next.amount = 0;
                next.status = 'paid';
                next.exemption_label = '🟢 Đã đóng BHYT tại địa phương';
            }

            return next;
        });
    };

    const syncTotal = (items) => {
        const total = items.reduce((sum, item) => sum + Number(item.amount || 0), 0);
        if (totalLabel) {
            totalLabel.textContent = money(total);
        }
    };

    const renderItems = () => {
        const items = applyExemption(currentBaseItems);
        itemsWrap.innerHTML = '';

        items.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'tuition-item-row';
            const locked = Boolean(item.exemption_label);
            const selectName = `fee_items[${index}][status]`;
            row.innerHTML = `
                <div class="min-w-0">
                    <input type="hidden" name="fee_items[${index}][key]" value="${item.key}">
                    ${locked ? `<input type="hidden" name="${selectName}" value="paid">` : ''}
                    <div class="text-sm font-normal text-gray-700 truncate">${item.label}</div>
                    <div class="text-xs font-normal text-orange-700/80 mt-1">${money(item.amount)}</div>
                    <div class="mt-2">${itemBadge(item.status, item.exemption_label)}</div>
                </div>
                <select name="${selectName}" class="tuition-input" ${locked ? 'disabled' : ''} data-status-select>
                    <option value="unpaid" ${item.status === 'unpaid' ? 'selected' : ''}>Chưa đóng</option>
                    <option value="paid" ${item.status === 'paid' ? 'selected' : ''}>Đã đóng</option>
                </select>
            `;

            const statusSelect = row.querySelector('[data-status-select]');
            statusSelect?.addEventListener('change', () => {
                const badge = row.querySelector('[data-item-badge]');
                if (badge) {
                    badge.outerHTML = itemBadge(statusSelect.value);
                }
                const source = currentBaseItems.find((baseItem) => baseItem.key === item.key);
                if (source) {
                    source.status = statusSelect.value;
                }
            });

            itemsWrap.appendChild(row);
        });

        syncTotal(items);
    };

    const openModal = (button) => {
        form.action = button.dataset.action || '';
        studentLabel.textContent = `${button.dataset.student || 'Học sinh'} • Lớp ${button.dataset.class || '-'}`;
        methodInput.value = button.dataset.method || 'cash';
        exemptionInput.value = button.dataset.exemption || 'default';
        noteInput.value = button.dataset.note || '';

        try {
            currentBaseItems = JSON.parse(button.dataset.items || '[]').map((item) => ({ ...item }));
        } catch (error) {
            currentBaseItems = [];
        }

        renderItems();
        syncQr();
        modal.classList.remove('d-none');
        modal.setAttribute('aria-hidden', 'false');
    };

    document.querySelectorAll('[data-tuition-open]').forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    document.querySelectorAll('[data-tuition-close]').forEach((button) => {
        button.addEventListener('click', () => {
            modal.classList.add('d-none');
            modal.setAttribute('aria-hidden', 'true');
        });
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('d-none');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    methodInput.addEventListener('change', syncQr);
    exemptionInput.addEventListener('change', renderItems);
});
</script>
@endpush
@endsection
