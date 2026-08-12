@extends('layouts.app')
@section('title', 'Theo dõi điểm toàn lớp chủ nhiệm')

@section('content')
<style>
    .admin-score-grid th,
    .admin-score-grid td,
    .admin-score-ledger-table th,
    .admin-score-ledger-table td {
        color: #374151;
        font-size: .92rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .admin-score-grid th,
    .admin-score-ledger-table th {
        background: rgba(255, 247, 237, .42);
        color: #111827;
        font-weight: 400;
    }

    .admin-score-value {
        color: #ea580c;
        font-weight: 400;
    }

    .admin-score-app .text-orange-400\/80 {
        color: rgba(251, 146, 60, .8) !important;
    }

    .admin-score-app .hover\:text-orange-600:hover {
        color: #ea580c !important;
    }

    .admin-score-term.locked,
    .admin-score-empty {
        color: #d1d5db;
        font-weight: 400;
    }

    .admin-score-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, .45);
    }

    .admin-score-modal-backdrop.open {
        display: flex;
    }

    .admin-score-modal {
        width: 100%;
        max-width: 1024px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 24px 56px rgba(15, 23, 42, .28);
        padding: 1.25rem;
    }
</style>
<div class="page-heading w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
    <div class="w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
        <h5 class="text-xl font-semibold text-gray-900 !text-left" style="text-align: left !important;">Theo dõi điểm lớp chủ nhiệm {{ $homeroomClass?->name }}</h5>
        <div class="text-sm font-normal text-gray-400 mt-1 !text-left" style="text-align: left !important;">Bao quát ma trận điểm tất cả các môn học của học sinh lớp chủ nhiệm.</div>
    </div>
</div>

@if(! $homeroomClass)
    <div class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg text-sm text-left font-normal">
        ⚠️ Bạn chưa được phân công làm Giáo viên chủ nhiệm cho lớp học nào trong hệ thống.
    </div>
@else
    <div class="text-sm font-normal text-orange-800 bg-orange-50/60 border border-orange-200/80 rounded-lg px-3.5 py-2 mb-4 text-left flex items-center gap-2">
        💡 Phạm vi theo dõi: Lớp chủ nhiệm <span class="font-semibold text-orange-900">{{ $homeroomClass->name }}</span>
    </div>

    <div
        class="admin-score-app"
        data-admin-score-app
        data-cascade-url="{{ route('scores.cascade') }}"
        data-matrix-url="{{ route('scores.admin-matrix') }}"
    >
        <div class="admin-score-toolbar p-3 bg-white border border-orange-100 rounded-xl mb-3 flex flex-wrap items-center gap-2">
            <input
                type="search"
                class="form-control text-sm font-normal border-orange-100 rounded-lg"
                style="max-width: 240px;"
                placeholder="Tìm mã HS hoặc họ tên..."
                data-admin-score-search
                value="{{ $adminMatrix['filters']['q'] ?? '' }}"
            >
            <input type="hidden" data-admin-score-year value="{{ $adminMatrix['filters']['school_year_id'] ?? '' }}">
            <input type="hidden" data-admin-score-class value="{{ $homeroomClass->id }}">

            <select class="form-select text-sm font-normal border-orange-100 rounded-lg" style="max-width: 180px;" data-admin-score-subject>
                <option value="">Tất cả môn học</option>
                @foreach(($adminMatrix['subjects'] ?? []) as $subjectOption)
                    <option value="{{ $subjectOption['id'] }}">{{ $subjectOption['name'] }}</option>
                @endforeach
            </select>
            <select class="form-select text-sm font-normal border-orange-100 rounded-lg" style="max-width: 180px;" data-admin-score-semester>
                @foreach(($adminMatrix['semesters'] ?? []) as $semesterOption)
                    <option value="{{ $semesterOption['id'] }}" @selected((string) ($adminMatrix['filters']['semester_id'] ?? '') === (string) $semesterOption['id'])>{{ $semesterOption['name'] }}</option>
                @endforeach
            </select>
            <select class="form-select text-xs md:text-sm font-normal text-orange-800 bg-orange-50/60 border border-orange-100 rounded-lg cursor-pointer ms-auto" style="max-width: 240px;" data-admin-score-evaluation>
                <option value="GRADE_10">Xem theo Thang điểm số 10</option>
                <option value="ASSESSMENT">Xem theo Đánh giá nhận xét</option>
            </select>
            <div class="admin-eval-toggle d-inline-flex align-items-center gap-1 ms-auto" data-admin-eval-toggle style="display: none;">
                <button type="button" class="btn text-xs font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded px-2.5 py-1 cursor-pointer shadow-xs" data-eval-tab="GRADE_10">
                    📊 Môn chấm điểm
                </button>
                <button type="button" class="btn text-xs font-normal text-orange-700 bg-orange-50 border border-orange-100 rounded px-2.5 py-1 cursor-pointer hover:bg-orange-100 transition-all" data-eval-tab="ASSESSMENT">
                    ☑️ Môn nhận xét
                </button>
            </div>
            <button type="button" class="admin-score-reset-btn text-xs font-normal text-gray-600 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 cursor-pointer hover:bg-gray-100" data-admin-score-reset>
                <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại lọc
            </button>
        </div>

        <div class="admin-score-context text-xs font-normal text-gray-500 mb-2 text-left" data-admin-score-context>
            Ma trận điểm tất cả các môn của Lớp {{ $homeroomClass->name }}.
        </div>

        <div class="card border border-orange-100 rounded-xl overflow-hidden shadow-xs mb-3">
            <div class="table-responsive">
                <table class="table align-middle admin-score-grid w-full table-fixed max-w-full overflow-hidden mb-0" data-admin-table-skip>
                    <thead data-admin-score-head></thead>
                    <tbody data-admin-score-body></tbody>
                </table>
            </div>
            <div class="admin-score-footer p-3 bg-orange-50/20 border-t border-orange-100 flex items-center justify-between text-xs text-gray-600 font-normal">
                <span data-admin-score-count>{{ $adminMatrix['pagination']['label'] ?? '' }}</span>
                <div class="admin-score-pager" data-admin-score-pager></div>
            </div>
        </div>

        <div class="admin-score-summary text-sm font-normal flex flex-wrap gap-4 text-gray-700 p-3 bg-orange-50/30 border border-orange-100 rounded-xl">
            <span>🟢 Điểm TB HK1: <strong data-admin-score-summary-hk1 class="text-orange-700">-</strong></span>
            <span>🔵 Điểm TB HK2: <strong data-admin-score-summary-hk2 class="text-orange-700">-</strong></span>
            <span class="master font-semibold text-orange-900">🔥 ĐIỂM TRUNG BÌNH CẢ NĂM: <strong data-admin-score-summary-year class="text-orange-700 text-base">-</strong></span>
        </div>

        <div class="admin-score-modal-backdrop" data-admin-score-modal aria-hidden="true">
            <div class="admin-score-modal">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="admin-score-modal-title text-base font-semibold text-gray-900" data-admin-score-modal-title>Chi tiết điểm học sinh</h5>
                        <div class="admin-score-modal-subtitle text-xs text-gray-500 font-normal" data-admin-score-modal-subtitle></div>
                    </div>
                    <button type="button" class="btn-close" data-admin-score-modal-close aria-label="Đóng"></button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle admin-score-ledger-table w-full table-fixed max-w-full overflow-hidden text-sm font-normal" data-admin-table-skip>
                        <thead>
                            <tr class="bg-orange-50/40 text-gray-900 font-medium">
                                <th>Môn học</th>
                                <th>Điểm Miệng</th>
                                <th>15 phút (Lần 1)</th>
                                <th>15 phút (Lần 2)</th>
                                <th>Điểm Giữa kỳ</th>
                                <th>Điểm Cuối kỳ</th>
                                <th>Điểm TB Môn</th>
                            </tr>
                        </thead>
                        <tbody data-admin-score-ledger></tbody>
                    </table>
                </div>
            </div>
        </div>

        <script type="application/json" data-admin-score-initial>{!! $adminMatrixJson !!}</script>
        <script>
            (() => {
                const app = document.querySelector('[data-admin-score-app]');
                if (!app) return;

                const initial = JSON.parse(app.querySelector('[data-admin-score-initial]')?.textContent || '{}');
                const urls = {
                    cascade: app.dataset.cascadeUrl,
                    matrix: app.dataset.matrixUrl,
                };
                const controls = {
                    year: app.querySelector('[data-admin-score-year]'),
                    classRoom: app.querySelector('[data-admin-score-class]'),
                    subject: app.querySelector('[data-admin-score-subject]'),
                    semester: app.querySelector('[data-admin-score-semester]'),
                    evaluation: app.querySelector('[data-admin-score-evaluation]'),
                    search: app.querySelector('[data-admin-score-search]'),
                    reset: app.querySelector('[data-admin-score-reset]'),
                };
                const head = app.querySelector('[data-admin-score-head]');
                const body = app.querySelector('[data-admin-score-body]');
                const context = app.querySelector('[data-admin-score-context]');
                const count = app.querySelector('[data-admin-score-count]');
                const pager = app.querySelector('[data-admin-score-pager]');
                const summaryHk1 = app.querySelector('[data-admin-score-summary-hk1]');
                const summaryHk2 = app.querySelector('[data-admin-score-summary-hk2]');
                const summaryYear = app.querySelector('[data-admin-score-summary-year]');
                const modal = app.querySelector('[data-admin-score-modal]');
                const modalTitle = app.querySelector('[data-admin-score-modal-title]');
                const modalSubtitle = app.querySelector('[data-admin-score-modal-subtitle]');
                const modalLedger = app.querySelector('[data-admin-score-ledger]');
                const modalClose = app.querySelector('[data-admin-score-modal-close]');
                const evalToggleEl = app.querySelector('[data-admin-eval-toggle]');
                const grade10TabBtn = app.querySelector('[data-eval-tab="GRADE_10"]') || app.querySelector('[data-eval-tab="numeric"]');
                const assessmentTabBtn = app.querySelector('[data-eval-tab="ASSESSMENT"]') || app.querySelector('[data-eval-tab="pass_fail"]');

                let matrix = initial;
                let debounceTimer = null;
                let activeEvaluationType = initial.filters?.hinh_thuc_danh_gia || 'GRADE_10';
                let currentSortColumn = null;
                let currentSortDirection = 'desc';
                if (controls.evaluation) controls.evaluation.value = activeEvaluationType;

                const handleSort = (columnKey, getValueFn) => {
                    if (currentSortColumn === columnKey) {
                        currentSortDirection = currentSortDirection === 'desc' ? 'asc' : 'desc';
                    } else {
                        currentSortColumn = columnKey;
                        currentSortDirection = 'desc';
                    }

                    if (matrix && matrix.rows && matrix.rows.length > 0) {
                        matrix.rows.sort((a, b) => {
                            const rawA = getValueFn(a);
                            const rawB = getValueFn(b);
                            const numA = (rawA !== null && rawA !== undefined && rawA !== '' && rawA !== '—') ? Number(rawA) : (currentSortDirection === 'desc' ? -999 : 999);
                            const numB = (rawB !== null && rawB !== undefined && rawB !== '' && rawB !== '—') ? Number(rawB) : (currentSortDirection === 'desc' ? -999 : 999);
                            return currentSortDirection === 'desc' ? numB - numA : numA - numB;
                        });
                        renderTableData(matrix);
                    }
                };

                const cell = (tag, text, className = '') => {
                    const el = document.createElement(tag);
                    el.textContent = text;
                    if (className) el.className = className;
                    return el;
                };

                const termText = (value, locked = false) => {
                    const span = document.createElement('span');
                    span.className = locked || !value ? 'admin-score-term locked' : 'admin-score-value font-semibold text-orange-700';
                    span.textContent = locked ? '—' : (value || '—');
                    return span;
                };

                const setSubjectOptions = (subjects = []) => {
                    if (!controls.subject) return;
                    const selectedValue = controls.subject.value || '';
                    controls.subject.innerHTML = '<option value="">Tất cả môn học</option>';
                    subjects.forEach((subject) => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.name;
                        option.selected = String(subject.id) === String(selectedValue);
                        controls.subject.appendChild(option);
                    });
                    if (selectedValue && !subjects.some((subject) => String(subject.id) === String(selectedValue))) {
                        controls.subject.value = '';
                    }
                };

                const createSortableTh = (label, columnKey, getValueFn, extraClass = '') => {
                    const th = document.createElement('th');
                    th.className = `${extraClass} cursor-pointer select-none align-middle`.trim();
                    th.title = `Click để sắp xếp ${label}`;
                    th.appendChild(document.createTextNode(label));

                    const iconSpan = document.createElement('span');
                    iconSpan.className = 'text-orange-400/80 font-normal ml-1 cursor-pointer hover:text-orange-600 transition-colors select-none text-xs';
                    iconSpan.textContent = '↕';
                    th.appendChild(iconSpan);
                    th.addEventListener('click', (e) => {
                        e.stopPropagation();
                        handleSort(columnKey, getValueFn);
                    });
                    return th;
                };

                const renderTableData = (payload) => {
                    matrix = payload;
                    setSubjectOptions(payload.subjects || []);
                    head.innerHTML = '';
                    body.innerHTML = '';
                    const tr = document.createElement('tr');
                    tr.appendChild(cell('th', 'Mã HS', 'w-24 text-left font-medium'));
                    tr.appendChild(cell('th', 'Họ tên', 'w-44 text-left font-medium'));

                    const mode = payload.mode || 'class_subjects';
                    const allHeaders = payload.headers || [];
                    let visibleHeaders = [];

                    if (mode === 'class_subjects') {
                        if (evalToggleEl) evalToggleEl.style.display = 'none';
                        visibleHeaders = allHeaders;
                    } else if (evalToggleEl) {
                        evalToggleEl.style.display = 'none';
                    }

                    visibleHeaders.forEach((h, index) => {
                        tr.appendChild(createSortableTh(h.title || h.name, `subject_${h.id}`, r => (r.cells?.[index]?.score ?? r.cells?.[index]?.value ?? r.cells?.[h.id]?.score ?? r.cells?.[h.id]?.value)));
                    });

                    const detailHeaders = mode === 'subject_details'
                        ? [
                            { label: 'Miệng', key: 'oral' },
                            { label: '15p', key: 'fifteen_1' },
                            { label: '15p', key: 'fifteen_2' },
                            { label: 'Giữa kỳ', key: 'midterm' },
                            { label: 'Cuối kỳ', key: 'final' },
                            { label: 'TB', key: 'average' },
                        ]
                        : [];

                    detailHeaders.forEach((item) => {
                        tr.appendChild(createSortableTh(item.label, item.key, item.key === 'average' ? r => r.detail_cells?.average : r => r.detail_cells?.[item.key]));
                    });

                    tr.appendChild(createSortableTh('HK1', 'hk1', r => r.summary?.hk1_gpa, 'admin-score-term bg-orange-50/20'));
                    tr.appendChild(createSortableTh('HK2', 'hk2', r => r.summary?.hk2_gpa, 'admin-score-term bg-orange-50/20'));
                    tr.appendChild(createSortableTh('Cả Năm', 'year', r => r.summary?.year_gpa, 'admin-score-term bg-orange-50/20'));
                    tr.appendChild(cell('th', 'Thao tác', 'w-24 text-right font-medium'));
                    head.appendChild(tr);

                    (payload.rows || []).forEach(row => {
                        const rowEl = document.createElement('tr');
                        rowEl.className = 'hover:bg-orange-50/20 transition-colors';
                        const student = row.student || {};
                        const studentCode = row.student_code || student.student_code || '';
                        const studentName = row.student_name || student.name || '';
                        rowEl.appendChild(cell('td', studentCode, 'font-medium text-gray-900 text-left'));

                        const nameTd = cell('td', studentName, 'text-left font-normal text-gray-800');
                        rowEl.appendChild(nameTd);

                        if (mode === 'subject_details') {
                            detailHeaders.forEach((item) => {
                                const td = document.createElement('td');
                                td.className = 'text-left text-sm';
                                td.appendChild(termText(row.detail_cells?.[item.key]));
                                rowEl.appendChild(td);
                            });
                        } else {
                            visibleHeaders.forEach((h, index) => {
                                const cellData = row.cells?.[index] || row.cells?.[h.id];
                                const td = document.createElement('td');
                                td.className = 'text-left text-sm';
                                td.appendChild(termText(cellData?.score ?? cellData?.value));
                                rowEl.appendChild(td);
                            });
                        }

                        const hk1Td = document.createElement('td');
                        hk1Td.className = 'admin-score-term bg-orange-50/20 text-left';
                        hk1Td.appendChild(termText(row.summary?.hk1_gpa));
                        rowEl.appendChild(hk1Td);

                        const hk2Td = document.createElement('td');
                        hk2Td.className = 'admin-score-term bg-orange-50/20 text-left';
                        hk2Td.appendChild(termText(row.summary?.hk2_gpa));
                        rowEl.appendChild(hk2Td);

                        const yearTd = document.createElement('td');
                        yearTd.className = 'admin-score-term bg-orange-50/20 text-left';
                        yearTd.appendChild(termText(row.summary?.year_gpa));
                        rowEl.appendChild(yearTd);

                        const actionTd = document.createElement('td');
                        actionTd.className = 'text-right';
                        const detailBtn = document.createElement('button');
                        detailBtn.type = 'button';
                        detailBtn.className = 'text-gray-500 bg-gray-50 p-2 rounded-md hover:bg-orange-50 hover:text-orange-600 transition-all shadow-xs inline-flex items-center justify-center border-0 cursor-pointer';
                        detailBtn.title = 'Xem chi tiết';
                        detailBtn.setAttribute('aria-label', 'Xem chi tiết');
                        detailBtn.innerHTML = '👁️';
                        detailBtn.addEventListener('click', () => showLedgerModal(row));
                        actionTd.appendChild(detailBtn);
                        rowEl.appendChild(actionTd);

                        body.appendChild(rowEl);
                    });

                    if (summaryHk1) summaryHk1.textContent = payload.summary?.hk1_gpa || '-';
                    if (summaryHk2) summaryHk2.textContent = payload.summary?.hk2_gpa || '-';
                    if (summaryYear) summaryYear.textContent = payload.summary?.year_gpa || '-';
                };

                const showLedgerModal = (row) => {
                    if (!modal) return;
                    const student = row.student || {};
                    const studentCode = row.student_code || student.student_code || '';
                    const studentName = row.student_name || student.name || '';
                    modalTitle.textContent = `Chi tiết điểm - ${studentName} (${studentCode})`;
                    modalSubtitle.textContent = `Lớp: {{ $homeroomClass->name }}`;
                    modalLedger.innerHTML = '';

                    const detailValue = (details, family, index = 0) => {
                        const matches = (details || []).filter(detail => detail.family === family);
                        return matches[index]?.value || '—';
                    };

                    const ledger = (row.ledger || []).filter(item => item.assessment_type !== 'NONE');
                    ledger.forEach(item => {
                        const details = item.details || [];
                        const tr = document.createElement('tr');
                        tr.appendChild(cell('td', item.subject_name, 'font-medium'));
                        tr.appendChild(cell('td', detailValue(details, 'oral')));
                        tr.appendChild(cell('td', detailValue(details, 'fifteen', 0)));
                        tr.appendChild(cell('td', detailValue(details, 'fifteen', 1)));
                        tr.appendChild(cell('td', detailValue(details, 'midterm')));
                        tr.appendChild(cell('td', detailValue(details, 'final')));
                        tr.appendChild(cell('td', item.average || '—', 'font-semibold text-orange-700'));
                        modalLedger.appendChild(tr);
                    });

                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                };

                if (modalClose) {
                    modalClose.addEventListener('click', () => {
                        modal.classList.remove('open');
                        modal.setAttribute('aria-hidden', 'true');
                    });
                }

                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.classList.remove('open');
                        modal.setAttribute('aria-hidden', 'true');
                    }
                });

                const fetchMatrix = () => {
                    const params = new URLSearchParams({
                        school_year_id: controls.year?.value || '',
                        class_id: '{{ $homeroomClass->id }}',
                        subject_id: controls.subject?.value || '',
                        semester_id: controls.semester?.value || '',
                        hinh_thuc_danh_gia: activeEvaluationType,
                        q: controls.search?.value || '',
                    });

                    fetch(`${urls.matrix}?${params.toString()}`)
                        .then(res => res.json())
                        .then(data => {
                            renderTableData(data);
                        })
                        .catch(err => console.error(err));
                };

                const syncEvalButtons = () => {
                    const activeClass = 'btn text-xs font-semibold text-orange-800 bg-orange-100 border border-orange-200 rounded px-2.5 py-1 cursor-pointer shadow-xs';
                    const inactiveClass = 'btn text-xs font-normal text-orange-700 bg-orange-50 border border-orange-100 rounded px-2.5 py-1 cursor-pointer hover:bg-orange-100 transition-all';
                    if (grade10TabBtn) {
                        grade10TabBtn.className = activeEvaluationType === 'GRADE_10' ? activeClass : inactiveClass;
                    }
                    if (assessmentTabBtn) {
                        assessmentTabBtn.className = activeEvaluationType === 'ASSESSMENT' ? activeClass : inactiveClass;
                    }
                    if (controls.evaluation) {
                        controls.evaluation.value = activeEvaluationType;
                    }
                };

                [grade10TabBtn, assessmentTabBtn].forEach(btn => {
                    if (!btn) return;
                    btn.addEventListener('click', () => {
                        activeEvaluationType = btn.dataset.evalTab;
                        syncEvalButtons();
                        fetchMatrix();
                    });
                });

                if (controls.subject) controls.subject.addEventListener('change', fetchMatrix);
                if (controls.semester) controls.semester.addEventListener('change', fetchMatrix);
                if (controls.evaluation) {
                    controls.evaluation.addEventListener('change', () => {
                        activeEvaluationType = controls.evaluation.value || 'GRADE_10';
                        syncEvalButtons();
                        fetchMatrix();
                    });
                }
                if (controls.search) {
                    controls.search.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(fetchMatrix, 300);
                    });
                }
                if (controls.reset) {
                    controls.reset.addEventListener('click', () => {
                        if (controls.search) controls.search.value = '';
                        if (controls.subject) controls.subject.value = '';
                        activeEvaluationType = 'GRADE_10';
                        if (controls.evaluation) controls.evaluation.value = activeEvaluationType;
                        syncEvalButtons();
                        fetchMatrix();
                    });
                }

                syncEvalButtons();
                renderTableData(initial);
            })();
        </script>
    </div>
@endif
@endsection
