@extends('layouts.app')
@section('title', 'Báo cáo - Thống kê')

@section('content')
@php
    $reportTypeLabels = [
        'multi_year' => 'So sánh nhiều năm',
        'school_year' => 'Theo năm học',
        'semester' => 'Theo học kì',
        'grade' => 'Theo khối',
        'class' => 'Theo lớp',
        'department' => 'Theo tổ chuyên môn',
        'subject' => 'Theo môn học',
        'teacher' => 'Theo giáo viên',
        'student' => 'Theo học sinh',
    ];
    $reportDescriptions = [
        'school_year' => 'Tổng hợp tình hình toàn trường trong năm học đã chọn.',
        'semester' => 'Thống kê dữ liệu của học kỳ đang chọn.',
        'grade' => 'Thống kê theo một khối cụ thể.',
        'class' => 'Thống kê theo một lớp cụ thể.',
        'teacher' => 'Thống kê theo giáo viên được chọn.',
        'department' => 'Thống kê theo tổ chuyên môn được chọn.',
        'subject' => 'Thống kê theo môn học được chọn.',
        'student' => 'Thống kê hồ sơ học tập của học sinh được chọn.',
        'multi_year' => 'So sánh dữ liệu qua nhiều năm học.',
    ];
    $activeReportType = $filters['report_type'] ?? 'school_year';
    $query = request()->query();
    $excelUrl = route('reports.index', array_merge($query, ['format' => 'excel']));
    $pdfUrl = route('reports.index', array_merge($query, ['format' => 'pdf']));
    $resetUrl = route('reports.index', ['report_type' => $activeReportType]);
    $charts = collect($reportDashboard['charts'] ?? [])->values();
    $cards = collect($reportDashboard['cards'] ?? [])->values();
    $profile = collect($reportDashboard['profile'] ?? [])->values();
    $table = $reportDashboard['table'] ?? ['title' => 'Bảng thống kê', 'headers' => [], 'rows' => []];
    $tableHeaders = collect($table['headers'] ?? [])->values();
    $tableRows = collect($table['rows'] ?? [])->values();
    $insights = collect($reportDashboard['insights'] ?? [])->values();
    $detailReports = ['student', 'teacher', 'department', 'subject', 'class'];
    $showProfileFirst = $profile->isNotEmpty() && in_array($reportFocus, $detailReports, true);
    $profileTitle = match ($reportFocus) {
        'student' => 'Hồ sơ học sinh',
        'teacher' => 'Thông tin giáo viên',
        'department' => 'Thông tin tổ chuyên môn',
        'subject' => 'Thông tin môn học',
        'class' => 'Thông tin lớp học',
        default => 'Thông tin chính',
    };
    $overviewTitle = match ($reportFocus) {
        'student' => 'Kết quả học tập',
        'teacher' => 'Kết quả giảng dạy',
        'department' => 'Tổng quan tổ chuyên môn',
        'subject' => 'Kết quả môn học',
        'class' => 'Tổng quan lớp học',
        'grade' => 'Tổng quan khối',
        'semester' => 'Tổng quan học kỳ',
        'multi_year' => 'Tổng quan so sánh',
        default => 'Tổng quan năm học',
    };
    $summaryOrder = [
        'Tổng học sinh',
        'Tổng học sinh phụ trách',
        'Tổng giáo viên',
        'Tổng lớp',
        'Tổng môn học',
        'Số lớp của khối',
        'Số học sinh',
        'Số lớp đang dạy',
        'Số giáo viên',
        'Tổ chuyên môn',
        'Môn phụ trách',
        'Tổ trưởng',
        'Điểm trung bình toàn trường',
        'Điểm trung bình học kỳ',
        'Điểm trung bình khối',
        'Điểm trung bình lớp',
        'Điểm trung bình các lớp',
        'Điểm trung bình môn',
        'Điểm trung bình mới nhất',
        'Điểm trung bình',
        'Tỷ lệ khá giỏi',
        'Tỷ lệ đạt',
        'Tỷ lệ chuyên cần',
        'Hạnh kiểm',
        'Tỷ lệ lên lớp',
        'Tỷ lệ tốt nghiệp',
        'Tiến độ nhập điểm',
    ];
    $summaryRank = array_flip($summaryOrder);
    $cards = $cards
        ->sortBy(fn ($card) => $summaryRank[$card['label'] ?? ''] ?? 999)
        ->values();
    $summaryContext = $activeReportType === 'multi_year'
        ? 'Từ ' . ($schoolYears->firstWhere('id', $filters['from_year_id'] ?? null)?->name ?: 'chưa chọn') . ' đến ' . ($schoolYears->firstWhere('id', $filters['to_year_id'] ?? null)?->name ?: ($selectedYear?->name ?: 'chưa chọn'))
        : ($selectedYear?->name ?: 'Chưa chọn năm học') . ' - ' . ($selectedSemester?->normalizedName() ?: 'Cả năm');
    $overviewTableModes = ['multi_year', 'school_year', 'semester'];
    $showFullReportTabs = in_array($activeReportType, $overviewTableModes, true);
    $singleReportTable = match ($activeReportType) {
        'grade' => 'grade',
        'class' => 'class',
        'subject' => 'subject',
        'teacher' => 'teacher',
        'student' => 'student',
        'department' => 'teacher',
        default => null,
    };
    $activeReportTableTab = $showFullReportTabs ? 'grade' : $singleReportTable;
    $showReportTables = $showFullReportTabs || $singleReportTable;
    $reportTableTabs = [
        'grade' => ['label' => 'Khối', 'title' => 'Tổng kết theo khối'],
        'class' => ['label' => 'Lớp', 'title' => 'Tổng kết theo lớp'],
        'subject' => ['label' => 'Môn học', 'title' => 'Tổng kết theo môn học'],
        'teacher' => ['label' => 'Giáo viên', 'title' => 'Tổng kết theo giáo viên'],
        'student' => ['label' => 'Học sinh', 'title' => 'Danh sách học sinh trong phạm vi báo cáo'],
    ];
@endphp

<div class="page-heading">
    <div>
        <h5>{{ $reportTitle }}</h5>
        <div class="text-muted">{{ $reportDescriptions[$activeReportType] ?? 'Dữ liệu được thống kê theo bộ lọc đã chọn.' }}</div>
        <div class="report-context-line">
            @if($activeReportType === 'multi_year')
                <span>Từ năm: {{ $schoolYears->firstWhere('id', $filters['from_year_id'] ?? null)?->name ?: 'Chưa chọn' }}</span>
                <span>Đến năm: {{ $schoolYears->firstWhere('id', $filters['to_year_id'] ?? null)?->name ?: ($selectedYear?->name ?: 'Chưa chọn') }}</span>
            @else
                <span>Năm học: {{ $selectedYear?->name ?: 'Chưa chọn' }}</span>
                <span>Học kỳ: {{ $selectedSemester?->normalizedName() ?: 'Cả năm' }}</span>
            @endif
            <span>{{ $reportTypeLabels[$activeReportType] ?? 'Báo cáo thống kê' }}</span>
        </div>
    </div>
</div>

<div class="report-page">
<form method="GET" class="management-card report-filter-card report-filter-horizontal mb-3" data-report-form>
    <div class="report-filter-row">
        <div class="report-filter-control report-filter-mode">
            <label class="form-label">Chế độ báo cáo</label>
            <select name="report_type" class="form-select" data-report-type>
                @foreach($reportTypeLabels as $type => $label)
                    <option value="{{ $type }}" @selected($activeReportType === $type)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control" data-report-field="school_year">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select">
                @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" @selected((string) $filters['school_year_id'] === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control" data-report-field="semester">
            <label class="form-label">Học kỳ</label>
            <select name="semester_id" class="form-select">
                <option value="">Cả năm</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->normalizedName() }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control" data-report-field="grade">
            <label class="form-label">Khối học</label>
            <select name="grade_level" class="form-select">
                <option value="">Chọn khối</option>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected((string) $filters['grade_level'] === (string) $grade)>Khối {{ $grade }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control report-filter-class" data-report-field="class">
            <label class="form-label">Lớp học</label>
            <select name="class_id" class="form-select">
                <option value="">Chọn lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" data-grade="{{ $class->grade_level }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control report-filter-person" data-report-field="teacher">
            <label class="form-label">Chọn giáo viên</label>
            <input type="search" class="form-control report-filter-search mb-1" placeholder="Tìm tên giáo viên" data-report-option-search data-target="teacher_id">
            <select name="teacher_id" class="form-select">
                <option value="">Chọn giáo viên</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" data-primary-subject="{{ $teacher->primary_subject_id }}" @selected((string) $filters['teacher_id'] === (string) $teacher->id)>{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control report-filter-person" data-report-field="department">
            <label class="form-label">Tổ chuyên môn</label>
            <select name="department_id" class="form-select">
                <option value="">Chọn tổ chuyên môn</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control report-filter-person" data-report-field="subject">
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select">
                <option value="">Chọn môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control report-filter-wide" data-report-field="student">
            <label class="form-label">Chọn học sinh</label>
            <input type="search" class="form-control report-filter-search mb-1" placeholder="Tìm mã hoặc tên học sinh" data-report-option-search data-target="student_id">
            <select name="student_id" class="form-select">
                <option value="">Chọn học sinh</option>
                @foreach($studentsForFilter as $student)
                    <option value="{{ $student->id }}" data-class="{{ $student->class_id }}" data-grade="{{ $student->classRoom?->grade_level }}" @selected((string) $filters['student_id'] === (string) $student->id)>
                        {{ $student->student_code }} - {{ $student->name }} @if($student->classRoom) - {{ $student->classRoom->name }} @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control" data-report-field="from_year">
            <label class="form-label">Từ năm học</label>
            <select name="from_year_id" class="form-select">
                @foreach($schoolYears->sortBy('start_date') as $year)
                    <option value="{{ $year->id }}" @selected((string) ($filters['from_year_id'] ?? '') === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-control" data-report-field="to_year">
            <label class="form-label">Đến năm học</label>
            <select name="to_year_id" class="form-select">
                @foreach($schoolYears->sortBy('start_date') as $year)
                    <option value="{{ $year->id }}" @selected((string) (($filters['to_year_id'] ?? '') ?: $filters['school_year_id']) === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-actions">
            <button class="btn btn-primary report-filter-submit">
                <i class="bi bi-search"></i>
                Xem báo cáo
            </button>
            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise"></i>
                Đặt lại
            </a>
        </div>
    </div>
</form>

<div class="management-card report-summary-card mb-2">
    <div class="report-summary-head">
        <div class="report-summary-titleblock">
            <h5 class="report-main-title">{{ $reportTitle }}</h5>
            <div class="report-main-description">Dữ liệu được tổng hợp theo bộ lọc đang chọn. Nếu chưa nhập đủ dữ liệu, hệ thống hiển thị trạng thái “Chưa có dữ liệu”.</div>
            <div class="report-summary-badges mt-2">
                <span class="badge report-scope-badge">{{ $reportTypeLabels[$activeReportType] ?? 'Báo cáo thống kê' }}</span>
                <span class="badge report-context-badge">{{ $summaryContext }}</span>
            </div>
        </div>
        <div class="report-action-toolbar" aria-label="Tác vụ báo cáo">
            <button type="button" class="btn btn-sm report-action-btn" data-bs-toggle="collapse" data-bs-target="#reportChartConfig" aria-expanded="false" aria-controls="reportChartConfig">
                <i class="bi bi-gear"></i>
                Cấu hình biểu đồ
            </button>
            <button type="button" class="btn btn-sm report-action-btn" onclick="window.print()">
                <i class="bi bi-printer"></i>
                In
            </button>
            <a href="{{ $pdfUrl }}" class="btn btn-sm report-action-btn" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i>
                Xuất PDF
            </a>
            <a href="{{ $excelUrl }}" class="btn btn-sm report-action-btn">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                Xuất Excel
            </a>
        </div>
    </div>

    @if($charts->isNotEmpty())
        <div class="collapse report-chart-config-panel" id="reportChartConfig">
            <div class="report-chart-config-inner">
                <span class="fw-semibold text-muted small">Hiển thị biểu đồ</span>
                @foreach($charts as $chart)
                    <label class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="checkbox" value="{{ $chart['id'] }}" data-report-chart-toggle checked>
                        <span class="form-check-label">{{ $chart['title'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    @if($cards->isNotEmpty())
        <div class="report-summary-grid">
            @foreach($cards->take(8) as $card)
                @php
                    $rawValue = trim((string) ($card['value'] ?? ''));
                    $displayValue = in_array($rawValue, ['', 'Chưa có dữ liệu'], true) ? '—' : $rawValue;
                @endphp
                <div class="report-summary-item">
                    <span><i class="bi {{ $card['icon'] ?? 'bi-clipboard-data' }}"></i> {{ $card['label'] }}</span>
                    <strong @class(['is-muted' => $displayValue === '—'])>{{ $displayValue }}</strong>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state"><i class="bi bi-clipboard-data"></i>{{ $reportDashboard['empty'] ?? 'Chưa có dữ liệu để lập báo cáo.' }}</div>
    @endif
</div>

@if($showProfileFirst)
    <div class="management-card mb-2">
        <div class="management-card-header">
            <div>
                <h6>{{ $profileTitle }}</h6>
                <p>Thông tin chính của đối tượng đang xem.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm report-info-table mb-0">
                <tbody>
                    @foreach($profile as $item)
                        <tr>
                            <th>{{ $item['label'] }}</th>
                            <td>{{ $item['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($profile->isNotEmpty())
    <div class="management-card mb-2">
        <div class="management-card-header">
            <div>
                <h6>Thông tin chính</h6>
                <p>Phạm vi báo cáo đang được thống kê.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm report-info-table mb-0">
                <tbody>
                    @foreach($profile as $item)
                        <tr>
                            <th>{{ $item['label'] }}</th>
                            <td>{{ $item['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($charts->isNotEmpty())
    <div @class(['report-chart-grid mb-2', 'single' => $charts->count() === 1])>
            @foreach($charts as $chart)
                <div class="card report-chart-card h-100" data-report-chart-card="{{ $chart['id'] }}">
                    <div class="card-body">
                        <h6 class="card-accent-title">{{ $chart['title'] }}</h6>
                        @php
                            $hasValues = collect($chart['datasets'] ?? [])->isNotEmpty()
                                ? collect($chart['datasets'])->flatMap(fn ($dataset) => $dataset['data'] ?? [])->sum() > 0
                                : collect($chart['values'] ?? [])->sum() > 0;
                        @endphp
                        @if($hasValues)
                            <canvas id="{{ $chart['id'] }}"></canvas>
                        @else
                            <div class="report-chart-placeholder">
                                <i class="bi bi-bar-chart"></i>
                                <span>Chưa đủ dữ liệu để dựng biểu đồ</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
            @for($placeholderIndex = $charts->count(); $placeholderIndex < 3; $placeholderIndex++)
                <div class="card report-chart-card h-100 report-chart-card-muted">
                    <div class="card-body">
                        <h6 class="card-accent-title">Biểu đồ bổ sung</h6>
                        <div class="report-chart-placeholder">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Chưa đủ dữ liệu để dựng biểu đồ</span>
                        </div>
                    </div>
                </div>
            @endfor
    </div>
@else
    <div class="report-chart-grid mb-2">
        @foreach(['Biểu đồ học lực', 'Biểu đồ hạnh kiểm', 'Tỷ lệ chuyên cần'] as $placeholderTitle)
            <div class="card report-chart-card h-100 report-chart-card-muted">
                <div class="card-body">
                    <h6 class="card-accent-title">{{ $placeholderTitle }}</h6>
                    <div class="report-chart-placeholder">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Chưa đủ dữ liệu để dựng biểu đồ</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@if(false)
<div class="management-card mb-2">
    <div class="management-card-header report-table-header">
        <div>
            <h6>{{ $table['title'] ?? 'Bảng thống kê' }}</h6>
            <p>Dữ liệu chi tiết theo bộ lọc báo cáo.</p>
        </div>
        <div class="report-table-tools">
            <span class="badge text-bg-light report-row-count">{{ $tableRows->count() }} dòng</span>
            <div class="input-group report-table-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" placeholder="Tìm kiếm trong bảng" data-report-table-search>
            </div>
        </div>
    </div>
    <div class="table-responsive report-table-wrap">
        <table class="table table-striped table-hover content-table align-middle mb-0" data-report-table>
            <thead>
                <tr>
                    @forelse($tableHeaders as $header)
                        <th data-sortable>{{ $header }}</th>
                    @empty
                        <th>Dữ liệu</th>
                    @endforelse
                </tr>
            </thead>
            <tbody>
                @forelse($tableRows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(1, $tableHeaders->count()) }}">
                            <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu phù hợp với bộ lọc.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="report-pagination" data-report-pagination hidden>
        <span class="text-muted small" data-report-page-info></span>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" data-report-prev>Trước</button>
            <button type="button" class="btn btn-outline-secondary" data-report-next>Tiếp theo</button>
        </div>
    </div>
</div>
@endif

@if($showReportTables)
    <div class="management-card report-tabs-card mb-2" data-report-table-zone>
        <div class="report-table-loading" aria-live="polite">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <span>Đang tải dữ liệu báo cáo...</span>
        </div>
        <div class="report-tabs-head">
            <div>
                <h6 class="card-accent-title mb-1">{{ $showFullReportTabs ? 'Bảng dữ liệu' : ($reportTableTabs[$activeReportTableTab]['title'] ?? 'Bảng dữ liệu') }}</h6>
                <p class="mb-0 text-muted small">{{ $showFullReportTabs ? 'Chọn một nhóm để xem bảng thống kê tương ứng.' : 'Bảng dữ liệu được lọc theo chế độ báo cáo đang chọn.' }}</p>
            </div>
            <div class="input-group report-table-search report-tabs-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" placeholder="Tìm kiếm trong bảng đang xem" data-report-tab-search>
            </div>
        </div>

        @if($showFullReportTabs)
        <ul class="nav report-data-tabs" role="tablist">
            @foreach($reportTableTabs as $tabKey => $tabInfo)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeReportTableTab === $tabKey ? 'active' : '' }}" type="button" data-bs-toggle="tab" data-bs-target="#report-tab-{{ $tabKey }}" role="tab" aria-selected="{{ $activeReportTableTab === $tabKey ? 'true' : 'false' }}">{{ $tabInfo['label'] }}</button>
                </li>
            @endforeach
        </ul>
        @endif

        <div class="tab-content report-tabs-content">
            @if($showFullReportTabs || $activeReportTableTab === 'grade')
            <div @class(['tab-pane fade', 'show active' => $activeReportTableTab === 'grade']) id="report-tab-grade" role="tabpanel" tabindex="0">
                <div class="table-responsive report-table-wrap report-table-wrap-auto">
                    <table class="table table-striped table-hover content-table align-middle mb-0" data-report-tab-table>
                        <thead>
                            <tr>
                                <th data-tab-sort>Khối</th>
                                <th data-tab-sort>Sĩ số</th>
                                <th data-tab-sort>Điểm trung bình</th>
                                <th data-tab-sort>Học sinh giỏi</th>
                                <th data-tab-sort>Chuyên cần</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($gradeSummary as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td>{{ $row['student_count'] }}</td>
                                <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                                <td>{{ $row['excellent_count'] }}</td>
                                <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state">Chưa có dữ liệu.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($showFullReportTabs || $activeReportTableTab === 'class')
            <div @class(['tab-pane fade', 'show active' => $activeReportTableTab === 'class']) id="report-tab-class" role="tabpanel" tabindex="0">
                <div class="table-responsive report-table-wrap report-table-wrap-auto">
                    <table class="table table-striped table-hover content-table align-middle mb-0" data-report-tab-table>
                        <thead>
                            <tr>
                                <th data-tab-sort>Lớp</th>
                                <th data-tab-sort>Sĩ số</th>
                                <th data-tab-sort>Điểm trung bình</th>
                                <th data-tab-sort>Học sinh giỏi</th>
                                <th data-tab-sort>Chuyên cần</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($classSummary as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td>{{ $row['student_count'] }}</td>
                                <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                                <td>{{ $row['excellent_count'] }}</td>
                                <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state">Chưa có dữ liệu.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($showFullReportTabs || $activeReportTableTab === 'subject')
            <div @class(['tab-pane fade', 'show active' => $activeReportTableTab === 'subject']) id="report-tab-subject" role="tabpanel" tabindex="0">
                <div class="table-responsive report-table-wrap report-table-wrap-auto">
                    <table class="table table-striped table-hover content-table align-middle mb-0" data-report-tab-table>
                        <thead>
                            <tr>
                                <th data-tab-sort>Môn học</th>
                                <th data-tab-sort>Số học sinh có điểm</th>
                                <th data-tab-sort>Điểm trung bình</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($subjectSummary as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td>{{ $row['student_count'] }}</td>
                                <td>{{ $row['average'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="empty-state">Chưa có dữ liệu điểm theo môn.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($showFullReportTabs || $activeReportTableTab === 'teacher')
            <div @class(['tab-pane fade', 'show active' => $activeReportTableTab === 'teacher']) id="report-tab-teacher" role="tabpanel" tabindex="0">
                <div class="table-responsive report-table-wrap report-table-wrap-auto">
                    <table class="table table-striped table-hover content-table align-middle mb-0" data-report-tab-table>
                        <thead>
                            <tr>
                                <th data-tab-sort>Giáo viên</th>
                                <th data-tab-sort>Lớp phụ trách</th>
                                <th data-tab-sort>Môn</th>
                                <th data-tab-sort>Tổng định mức tiết/tuần</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($teacherSummary as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td>{{ $row['class_count'] }}</td>
                                <td>{{ $row['subject_count'] }}</td>
                                <td>{{ $row['weekly_periods'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state">Chưa có dữ liệu phân công giảng dạy.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($showFullReportTabs || $activeReportTableTab === 'student')
            <div @class(['tab-pane fade', 'show active' => $activeReportTableTab === 'student']) id="report-tab-student" role="tabpanel" tabindex="0">
                <div class="table-responsive report-table-wrap">
                    <table class="table table-striped table-hover content-table align-middle mb-0" data-report-tab-table>
                        <thead>
                            <tr>
                                <th data-tab-sort>Mã học sinh</th>
                                <th data-tab-sort>Họ tên</th>
                                <th data-tab-sort>Lớp</th>
                                <th data-tab-sort>Điểm trung bình</th>
                                <th data-tab-sort>Học lực</th>
                                <th data-tab-sort>Hạnh kiểm</th>
                                <th data-tab-sort>Chuyên cần</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($studentRows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['student']->student_code }}</td>
                                <td>{{ $row['student']->name }}</td>
                                <td>{{ $row['student']->classRoom?->name ?? 'Chưa có lớp' }}</td>
                                <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                                <td>{{ $studyLabels[$row['study_rank']] ?? 'Chưa có dữ liệu' }}</td>
                                <td>{{ $conductLabels[$row['conduct']] ?? 'Chưa có dữ liệu' }}</td>
                                <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-clipboard-data"></i>Không có dữ liệu phù hợp với bộ lọc.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif

@if($insights->isNotEmpty())
    <div class="management-card report-insight-card">
        <div class="management-card-header">
            <div>
                <h6>Nhận xét thống kê</h6>
                <p>Nhận xét ngắn được tổng hợp từ dữ liệu hiện có.</p>
            </div>
        </div>
        <ul class="report-insight-list">
            @foreach($insights as $insight)
                <li>{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
@endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-report-form]');
        if (form) {
            const reportTypeSelect = form.querySelector('[data-report-type]');
            const gradeSelect = form.querySelector('[name="grade_level"]');
            const classSelect = form.querySelector('[name="class_id"]');
            const teacherSelect = form.querySelector('[name="teacher_id"]');
            const subjectSelect = form.querySelector('[name="subject_id"]');
            const studentSelect = form.querySelector('[name="student_id"]');
            const fields = form.querySelectorAll('[data-report-field]');
            const optionSearchInputs = form.querySelectorAll('[data-report-option-search]');
            const fieldMap = {
                multi_year: ['from_year', 'to_year'],
                school_year: ['school_year'],
                semester: ['school_year', 'semester'],
                grade: ['school_year', 'grade'],
                class: ['school_year', 'grade', 'class'],
                department: ['school_year', 'department'],
                subject: ['school_year', 'class', 'subject'],
                teacher: ['school_year', 'teacher'],
                student: ['school_year', 'class', 'student'],
            };

            const syncReportFields = () => {
                const visible = fieldMap[reportTypeSelect?.value || 'school_year'] || fieldMap.school_year;
                fields.forEach((field) => {
                    const shouldShow = visible.includes(field.dataset.reportField);
                    field.hidden = !shouldShow;
                    field.querySelectorAll('select, input').forEach((input) => input.disabled = !shouldShow);
                });
            };

            const syncClassOptions = () => {
                const gradeField = form.querySelector('[data-report-field="grade"]');
                const grade = gradeField?.hidden ? '' : (gradeSelect?.value || '');
                [...classSelect?.options || []].forEach((option) => {
                    option.hidden = Boolean(grade && option.value && option.dataset.grade !== grade);
                });
                if (classSelect?.selectedOptions[0]?.hidden) {
                    classSelect.value = '';
                }
            };

            const syncSubjectOptions = () => {
                const selectedTeacher = teacherSelect?.selectedOptions[0];
                const primarySubject = selectedTeacher?.dataset.primarySubject || '';
                [...subjectSelect?.options || []].forEach((option) => {
                    option.hidden = Boolean(primarySubject && option.value && option.value !== primarySubject);
                });
                if (subjectSelect?.selectedOptions[0]?.hidden) {
                    subjectSelect.value = '';
                }
            };

            const syncStudentOptions = () => {
                const classField = form.querySelector('[data-report-field="class"]');
                const classId = classField?.hidden ? '' : (classSelect?.value || '');
                [...studentSelect?.options || []].forEach((option) => {
                    const classMismatch = Boolean(classId && option.value && option.dataset.class !== classId);
                    option.hidden = classMismatch || option.dataset.searchHidden === '1';
                });
                if (studentSelect?.selectedOptions[0]?.hidden) {
                    studentSelect.value = '';
                }
            };

            const syncOptionSearch = (input) => {
                const target = form.querySelector(`[name="${input.dataset.target}"]`);
                const keyword = input.value.trim().toLocaleLowerCase('vi-VN');

                [...target?.options || []].forEach((option) => {
                    if (!option.value) {
                        option.dataset.searchHidden = '0';
                        return;
                    }

                    const matched = option.textContent.toLocaleLowerCase('vi-VN').includes(keyword);
                    option.dataset.searchHidden = matched ? '0' : '1';
                    option.hidden = !matched;
                });

                if (target?.selectedOptions[0]?.hidden) {
                    target.value = '';
                }
            };

            const syncFilters = () => {
                syncReportFields();
                optionSearchInputs.forEach(syncOptionSearch);
                syncClassOptions();
                syncSubjectOptions();
                syncStudentOptions();
            };

            [reportTypeSelect, gradeSelect, classSelect, teacherSelect].forEach((select) => {
                select?.addEventListener('change', syncFilters);
            });
            optionSearchInputs.forEach((input) => {
                input.addEventListener('input', syncFilters);
            });
            form.addEventListener('submit', () => {
                document.querySelector('[data-report-table-zone]')?.classList.add('is-loading');
                form.querySelectorAll('button, a.btn').forEach((control) => control.classList.add('disabled'));
            });
            syncFilters();
        }

        const table = document.querySelector('[data-report-table]');
        const searchInput = document.querySelector('[data-report-table-search]');
        const pagination = document.querySelector('[data-report-pagination]');
        const info = document.querySelector('[data-report-page-info]');
        const prevButton = document.querySelector('[data-report-prev]');
        const nextButton = document.querySelector('[data-report-next]');
        let page = 1;
        let sortIndex = null;
        let sortDirection = 1;
        const pageSize = 10;
        const allRows = table ? [...table.querySelectorAll('tbody tr')].filter((row) => !row.querySelector('.empty-state')) : [];
        const normalize = (value) => value.toString().trim().toLocaleLowerCase('vi-VN');

        const getFilteredRows = () => {
            const keyword = normalize(searchInput?.value || '');
            let rows = allRows.filter((row) => normalize(row.textContent).includes(keyword));
            if (sortIndex !== null) {
                rows = rows.sort((a, b) => {
                    const left = normalize(a.children[sortIndex]?.textContent || '');
                    const right = normalize(b.children[sortIndex]?.textContent || '');
                    return left.localeCompare(right, 'vi', { numeric: true }) * sortDirection;
                });
            }
            return rows;
        };

        const renderTable = () => {
            if (!table || allRows.length === 0) return;
            const rows = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            page = Math.min(page, totalPages);
            allRows.forEach((row) => row.hidden = true);
            rows.slice((page - 1) * pageSize, page * pageSize).forEach((row) => row.hidden = false);
            pagination.hidden = rows.length <= pageSize;
            info.textContent = `Trang ${page}/${totalPages} - ${rows.length} dòng`;
            prevButton.disabled = page <= 1;
            nextButton.disabled = page >= totalPages;
        };

        searchInput?.addEventListener('input', () => {
            page = 1;
            renderTable();
        });
        prevButton?.addEventListener('click', () => {
            page = Math.max(1, page - 1);
            renderTable();
        });
        nextButton?.addEventListener('click', () => {
            page += 1;
            renderTable();
        });
        table?.querySelectorAll('[data-sortable]').forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                if (sortIndex === index) {
                    sortDirection *= -1;
                } else {
                    sortIndex = index;
                    sortDirection = 1;
                }
                table.querySelectorAll('[data-sortable]').forEach((item) => item.textContent = item.textContent.replace(/[▲▼]/g, '').trim());
                header.textContent = `${header.textContent.replace(/[▲▼]/g, '').trim()} ${sortDirection === 1 ? '▲' : '▼'}`;
                renderTable();
            });
        });
        renderTable();

        const tabSearch = document.querySelector('[data-report-tab-search]');
        const tabTables = [...document.querySelectorAll('[data-report-tab-table]')];

        const getActiveTabTable = () => document.querySelector('.report-tabs-content .tab-pane.active [data-report-tab-table]');

        const renderActiveTabTable = () => {
            const activeTable = getActiveTabTable();
            const keyword = normalize(tabSearch?.value || '');

            tabTables.forEach((tabTable) => {
                const rows = [...tabTable.querySelectorAll('tbody tr')].filter((row) => !row.querySelector('.empty-state'));
                rows.forEach((row) => {
                    row.hidden = tabTable !== activeTable || !normalize(row.textContent).includes(keyword);
                });
            });
        };

        tabSearch?.addEventListener('input', renderActiveTabTable);
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tabButton) => {
            tabButton.addEventListener('shown.bs.tab', () => {
                if (tabSearch) {
                    tabSearch.value = '';
                }
                renderActiveTabTable();
            });
        });
        tabTables.forEach((tabTable) => {
            tabTable.querySelectorAll('[data-tab-sort]').forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const body = tabTable.tBodies[0];
                    const direction = header.dataset.sortDirection === 'asc' ? -1 : 1;
                    const rows = [...body.querySelectorAll('tr')].filter((row) => !row.querySelector('.empty-state'));
                    rows
                        .sort((a, b) => normalize(a.children[index]?.textContent || '').localeCompare(normalize(b.children[index]?.textContent || ''), 'vi', { numeric: true }) * direction)
                        .forEach((row) => body.appendChild(row));

                    tabTable.querySelectorAll('[data-tab-sort]').forEach((item) => {
                        item.dataset.sortDirection = '';
                        item.textContent = item.textContent.replace(/[▲▼↕]/g, '').trim();
                    });
                    header.dataset.sortDirection = direction === 1 ? 'asc' : 'desc';
                    header.textContent = header.textContent.replace(/[▲▼↕]/g, '').trim();
                    renderActiveTabTable();
                });
            });
        });
        renderActiveTabTable();

        document.querySelectorAll('[data-report-chart-toggle]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const chartCard = document.querySelector(`[data-report-chart-card="${checkbox.value}"]`);
                if (chartCard) {
                    chartCard.hidden = !checkbox.checked;
                }
            });
        });

        if (!window.Chart) return;

        const charts = @json($charts);
        const renderChart = (chart) => {
            const element = document.getElementById(chart.id);
            if (!element) return;
            Chart.getChart(element)?.destroy();

            const dataset = chart.datasets?.length
                ? chart.datasets.map((item) => ({
                    ...item,
                    pointRadius: chart.type === 'line' ? 2 : undefined,
                    pointHoverRadius: chart.type === 'line' ? 3 : undefined,
                    borderWidth: chart.type === 'line' ? 2 : undefined,
                }))
                : [{
                    data: chart.values,
                    backgroundColor: chart.colors,
                    borderColor: chart.type === 'line' ? chart.colors?.[0] : undefined,
                    borderRadius: chart.type === 'bar' ? 4 : 0,
                    maxBarThickness: 30,
                    borderWidth: 1,
                }];

            new Chart(element, {
                type: chart.type,
                data: {
                    labels: chart.labels,
                    datasets: dataset,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    resizeDelay: 250,
                    devicePixelRatio: Math.min(window.devicePixelRatio || 1, 1.5),
                    interaction: { mode: 'nearest', intersect: false },
                    plugins: {
                        legend: {
                            display: chart.type !== 'bar' || Boolean(chart.datasets?.length),
                            position: 'bottom',
                            labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } }
                        },
                        tooltip: { enabled: true }
                    },
                    scales: chart.type === 'bar' || chart.type === 'line' ? {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, .18)' },
                            ticks: { maxTicksLimit: 5, font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11 } }
                        }
                    } : {}
                }
            });
        };

        const renderCharts = () => charts.forEach((chart, index) => {
            window.setTimeout(() => renderChart(chart), index * 40);
        });

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(renderCharts, { timeout: 800 });
        } else {
            window.requestAnimationFrame(renderCharts);
        }
    });
</script>
@endsection
