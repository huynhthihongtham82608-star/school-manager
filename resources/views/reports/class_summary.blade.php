@extends('layouts.app')
@section('title', 'Báo cáo')

@section('content')
@php
    $studyLabels = [
        'excellent' => 'Giỏi',
        'good' => 'Khá',
        'average' => 'Trung bình',
        'needs_support' => 'Cần hỗ trợ',
        'no_data' => 'Chưa có dữ liệu',
    ];
    $conductLabels = [
        'excellent' => 'Tốt',
        'good' => 'Khá',
        'average' => 'Trung bình',
        'weak' => 'Cần cố gắng',
    ];
    $scopeLabels = [
        'statistics' => '📊 Báo cáo thống kê',
        'multi_year' => '📈 So sánh nhiều năm',
    ];
    $focusLabels = [
        'school' => 'Toàn trường',
        'semester' => 'Theo học kỳ',
        'grade' => 'Theo khối',
        'class' => 'Theo lớp',
        'teacher' => 'Theo giáo viên',
        'subject' => 'Theo môn học',
        'student' => 'Theo học sinh',
        'multi_year' => 'So sánh nhiều năm',
    ];
    $query = request()->query();
    $excelUrl = route('reports.index', array_merge($query, ['format' => 'excel']));
    $pdfUrl = route('reports.index', array_merge($query, ['format' => 'pdf']));
    $studyChartLabels = collect($studyDistribution)->pluck('label')->values();
    $studyChartValues = collect($studyDistribution)->pluck('value')->values();
    $conductChartLabels = collect($conductDistribution)->pluck('label')->values();
    $conductChartValues = collect($conductDistribution)->pluck('value')->values();
    $attendanceChartLabels = collect(['Có mặt', 'Đi muộn', 'Có phép', 'Không phép']);
    $attendanceChartValues = collect([
        $attendanceDistribution['present'],
        $attendanceDistribution['late'],
        $attendanceDistribution['excused'],
        $attendanceDistribution['absent'],
    ]);
    $yearChartLabels = $yearComparison->pluck('label')->values();
    $yearAverageValues = $yearComparison->pluck('average')->map(fn ($value) => $value ?? 0)->values();
    $yearAttendanceValues = $yearComparison->pluck('attendance_rate')->map(fn ($value) => $value ?? 0)->values();
    $yearConductValues = $yearComparison->pluck('conduct_good_rate')->map(fn ($value) => $value ?? 0)->values();
    $yearPromotionValues = $yearComparison->pluck('promotion_rate')->map(fn ($value) => $value ?? 0)->values();
    $yearGraduationValues = $yearComparison->pluck('graduation_rate')->map(fn ($value) => $value ?? 0)->values();
    $yearStudentValues = $yearComparison->pluck('student_count')->values();
    $studentTrendValues = collect($studentReport['score_trend'] ?? [])->values();
    $studentTrendLabels = $studentTrendValues->keys()->map(fn ($index) => 'Lần ' . ($index + 1))->values();
    $subjectDistributionLabels = collect($subjectReport['distribution'] ?? [])->keys()->values();
    $subjectDistributionValues = collect($subjectReport['distribution'] ?? [])->values();
    $kpiIcons = [
        'Học sinh' => 'bi-mortarboard',
        'Giáo viên' => 'bi-person-workspace',
        'Lớp học' => 'bi-building',
        'Môn học' => 'bi-journal-bookmark',
        'Điểm trung bình toàn trường' => 'bi-star',
        'Tỷ lệ khá giỏi' => 'bi-graph-up-arrow',
        'Tỷ lệ chuyên cần' => 'bi-calendar-check',
        'Tỷ lệ lên lớp' => 'bi-arrow-up-circle',
        'Tỷ lệ tốt nghiệp' => 'bi-award',
    ];
@endphp

<div class="page-heading">
    <div>
        <h5>Báo cáo - Thống kê</h5>
        <div class="text-muted">Chọn bộ lọc cần xem, hệ thống tự sinh KPI, biểu đồ, bảng thống kê và nhận xét phù hợp.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="{{ route('reports.index', array_merge($query, ['ai' => 1])) }}" class="btn btn-outline-secondary">
            <i class="bi bi-stars"></i>
            Tạo nhận xét
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i>
            In
        </button>
        <a href="{{ $pdfUrl }}" class="btn btn-secondary" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i>
            Xuất PDF
        </a>
        <a href="{{ $excelUrl }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            Xuất Excel
        </a>
    </div>
</div>

<form method="GET" class="management-card mb-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Chế độ báo cáo</label>
            <select name="scope" class="form-select">
                @foreach($scopeLabels as $scopeKey => $scopeLabel)
                    <option value="{{ $scopeKey }}" @selected($filters['scope'] === $scopeKey)>{{ $scopeLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select">
                @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" @selected((string) $filters['school_year_id'] === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-multi-year-field>
            <label class="form-label">Từ năm học</label>
            <select name="from_year_id" class="form-select">
                @foreach($schoolYears->sortBy('start_date') as $year)
                    <option value="{{ $year->id }}" @selected((string) ($filters['from_year_id'] ?? '') === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-multi-year-field>
            <label class="form-label">Đến năm học</label>
            <select name="to_year_id" class="form-select">
                @foreach($schoolYears->sortBy('start_date') as $year)
                    <option value="{{ $year->id }}" @selected((string) (($filters['to_year_id'] ?? '') ?: $filters['school_year_id']) === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Học kỳ</label>
            <select name="semester_id" class="form-select">
                <option value="">Cả năm</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->normalizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select">
                <option value="">Tất cả khối</option>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected((string) $filters['grade_level'] === (string) $grade)>Khối {{ $grade }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Lớp</label>
            <select name="class_id" class="form-select">
                <option value="">Tất cả lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" data-grade="{{ $class->grade_level }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select">
                <option value="">Tất cả giáo viên</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" data-primary-subject="{{ $teacher->primary_subject_id }}" @selected((string) $filters['teacher_id'] === (string) $teacher->id)>{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2" data-report-stat-field>
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select">
                <option value="">Tất cả môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-xl-3" data-report-stat-field>
            <label class="form-label">Học sinh</label>
            <select name="student_id" class="form-select">
                <option value="">Tất cả học sinh</option>
                @foreach($studentsForFilter as $student)
                    <option value="{{ $student->id }}" data-class="{{ $student->class_id }}" data-grade="{{ $student->classRoom?->grade_level }}" @selected((string) $filters['student_id'] === (string) $student->id)>
                        {{ $student->student_code }} - {{ $student->name }} @if($student->classRoom) - {{ $student->classRoom->name }} @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">
                <i class="bi bi-search"></i>
                Xem báo cáo
            </button>
        </div>
    </div>
</form>

<div class="management-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <h5 class="mb-1">{{ $reportTitle }}</h5>
            <div class="text-muted">Dữ liệu được tổng hợp theo bộ lọc đang chọn. Nếu chưa nhập đủ dữ liệu, hệ thống hiển thị trạng thái “Chưa có dữ liệu”.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-warning">{{ $scopeLabels[$filters['scope']] ?? 'Báo cáo thống kê' }}</span>
            <span class="badge text-bg-light">{{ $focusLabels[$reportFocus] ?? 'Toàn trường' }}</span>
        </div>
    </div>

    <div class="row g-3">
        @foreach($summaryCards as $card)
            <div class="col-6 col-xl-3">
                <div class="report-metric-card">
                    <i class="bi {{ $kpiIcons[$card['label']] ?? 'bi-clipboard-data' }} report-metric-icon"></i>
                    <div>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}{{ $card['suffix'] }}</strong>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@if($reportFocus === 'student')
    <div class="management-card mb-3">
        <h6 class="card-accent-title mb-3">Hồ sơ học tập học sinh</h6>
        @if($studentReport)
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="report-detail-tile">
                        <span>Học sinh</span>
                        <strong>{{ $studentReport['student']->student_code }} - {{ $studentReport['student']->name }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="report-detail-tile">
                        <span>Lớp</span>
                        <strong>{{ $studentReport['student']->classRoom?->name ?? 'Chưa có lớp' }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="report-detail-tile">
                        <span>Điểm trung bình</span>
                        <strong>{{ $studentReport['average'] ?? 'Chưa có dữ liệu' }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="report-detail-tile">
                        <span>Chuyên cần</span>
                        <strong>{{ $studentReport['attendance_rate'] === null ? 'Chưa có dữ liệu' : $studentReport['attendance_rate'] . '%' }}</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="report-detail-tile">
                        <span>Hạnh kiểm</span>
                        <strong>{{ $conductLabels[$studentReport['conduct']] ?? 'Chưa có dữ liệu' }}</strong>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h6 class="mb-2">Nhận xét</h6>
                <div class="alert alert-info mb-0">{{ $studentReport['comment'] ?: 'Chưa có nhận xét cho học sinh trong phạm vi báo cáo.' }}</div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <div class="card report-chart-card h-100">
                        <div class="card-body">
                            <h6 class="card-accent-title">Điểm theo học kỳ</h6>
                            @if($studentTrendValues->filter(fn ($value) => $value !== null)->isNotEmpty())
                                <canvas id="studentTrendChart"></canvas>
                            @else
                                <div class="empty-state"><i class="bi bi-graph-up"></i>Chưa có dữ liệu điểm để hiển thị xu hướng.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state"><i class="bi bi-person"></i>Vui lòng chọn học sinh để xem báo cáo cá nhân.</div>
        @endif
    </div>
@endif

@if($aiInsights)
    <div class="management-card mb-3">
        <h6 class="card-accent-title mb-3">Nhận xét hỗ trợ</h6>
        <div class="row g-2">
            @foreach($aiInsights as $insight)
                <div class="col-md-6">
                    <div class="alert alert-success mb-0">{{ $insight }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($filters['scope'] !== 'multi_year' && $reportFocus !== 'student')
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card report-chart-card h-100">
            <div class="card-body">
                <h6 class="card-accent-title">Biểu đồ học lực</h6>
                @if($studyChartValues->sum() > 0)
                    <canvas id="studyChart"></canvas>
                @else
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Chưa có dữ liệu học lực.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card report-chart-card h-100">
            <div class="card-body">
                <h6 class="card-accent-title">Biểu đồ hạnh kiểm</h6>
                @if($conductChartValues->sum() > 0)
                    <canvas id="conductChart"></canvas>
                @else
                    <div class="empty-state"><i class="bi bi-pie-chart"></i>Chưa có dữ liệu hạnh kiểm.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card report-chart-card h-100">
            <div class="card-body">
                <h6 class="card-accent-title">Tỷ lệ chuyên cần</h6>
                @if($attendanceDistribution['total'] > 0)
                    <canvas id="attendanceChart"></canvas>
                @else
                    <div class="empty-state"><i class="bi bi-person-check"></i>Chưa có dữ liệu điểm danh.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if($filters['scope'] === 'multi_year')
    @if($yearComparison->count() >= 2)
        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card report-chart-card h-100">
                    <div class="card-body">
                        <h6 class="card-accent-title">So sánh điểm trung bình</h6>
                        <canvas id="yearChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card report-chart-card h-100">
                    <div class="card-body">
                        <h6 class="card-accent-title">So sánh chuyên cần, hạnh kiểm, lên lớp, tốt nghiệp</h6>
                        <canvas id="yearRateChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card report-chart-card h-100">
                    <div class="card-body">
                        <h6 class="card-accent-title">So sánh số lượng học sinh</h6>
                        <canvas id="yearStudentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="management-card mb-3">
            <div class="empty-state"><i class="bi bi-graph-up"></i>Vui lòng chọn khoảng từ 2 năm học trở lên để so sánh.</div>
        </div>
    @endif

    <div class="management-card mb-3">
        <h6 class="card-accent-title mb-3">Bảng so sánh nhiều năm học</h6>
        <div class="table-responsive">
            <table class="table content-table align-middle">
                <thead>
                    <tr>
                        <th>Năm học</th>
                        <th>Số học sinh</th>
                        <th>Điểm trung bình</th>
                        <th>Chuyên cần</th>
                        <th>Hạnh kiểm tốt/khá</th>
                        <th>Tỷ lệ lên lớp</th>
                        <th>Tỷ lệ tốt nghiệp</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($yearComparison as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['label'] }}</td>
                        <td>{{ $row['student_count'] }}</td>
                        <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                        <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                        <td>{{ $row['conduct_good_rate'] === null ? 'Chưa có dữ liệu' : $row['conduct_good_rate'] . '%' }}</td>
                        <td>{{ $row['promotion_rate'] === null ? 'Chưa có dữ liệu' : $row['promotion_rate'] . '%' }}</td>
                        <td>{{ $row['graduation_rate'] === null ? 'Chưa có dữ liệu' : $row['graduation_rate'] . '%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">Chưa có dữ liệu so sánh.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($reportFocus === 'teacher' && $teacherReport)
    <div class="management-card mb-3">
        <h6 class="card-accent-title mb-3">Báo cáo theo giáo viên</h6>
        <div class="row g-3">
            <div class="col-md-3"><div class="report-detail-tile"><span>Giáo viên</span><strong>{{ $teacherReport['teacher']->name }}</strong></div></div>
            <div class="col-md-3"><div class="report-detail-tile"><span>Môn chính</span><strong>{{ $teacherReport['teacher']->primarySubjectName() }}</strong></div></div>
            <div class="col-md-2"><div class="report-detail-tile"><span>Số học sinh</span><strong>{{ $teacherReport['student_count'] }}</strong></div></div>
            <div class="col-md-2"><div class="report-detail-tile"><span>Tỷ lệ khá giỏi</span><strong>{{ $teacherReport['excellent_good_rate'] === null ? 'Chưa có dữ liệu' : $teacherReport['excellent_good_rate'] . '%' }}</strong></div></div>
            <div class="col-md-2"><div class="report-detail-tile"><span>Tỷ lệ đạt</span><strong>{{ $teacherReport['passed_rate'] === null ? 'Chưa có dữ liệu' : $teacherReport['passed_rate'] . '%' }}</strong></div></div>
        </div>
    </div>
@endif

@if($reportFocus === 'subject' && $subjectReport)
    <div class="management-card mb-3">
        <h6 class="card-accent-title mb-3">Báo cáo theo môn học</h6>
        <div class="row g-3">
            <div class="col-md-4"><div class="report-detail-tile"><span>Môn học</span><strong>{{ $subjectReport['subject']->name }}</strong></div></div>
            <div class="col-md-4"><div class="report-detail-tile"><span>Điểm trung bình môn</span><strong>{{ $subjectReport['average'] ?? 'Chưa có dữ liệu' }}</strong></div></div>
            <div class="col-md-4"><div class="report-detail-tile"><span>Tỷ lệ đạt</span><strong>{{ $subjectReport['passed_rate'] === null ? 'Chưa có dữ liệu' : $subjectReport['passed_rate'] . '%' }}</strong></div></div>
        </div>
        <div class="card report-chart-card mt-3">
            <div class="card-body">
                <h6 class="card-accent-title">Phân bố điểm môn học</h6>
                @if($subjectDistributionValues->sum() > 0)
                    <canvas id="subjectDistributionChart"></canvas>
                @else
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Chưa có dữ liệu điểm của môn học này.</div>
                @endif
            </div>
        </div>
    </div>
@endif

@if($filters['scope'] !== 'multi_year' && $reportFocus !== 'student')
<div class="management-card mb-3">
    <h6 class="card-accent-title mb-3">Tổng kết theo khối</h6>
    <div class="table-responsive">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Khối</th>
                    <th>Sĩ số</th>
                    <th>Điểm trung bình</th>
                    <th>Học sinh giỏi</th>
                    <th>Chuyên cần</th>
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

<div class="management-card mb-3">
    <h6 class="card-accent-title mb-3">Tổng kết theo lớp</h6>
    <div class="table-responsive">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Sĩ số</th>
                    <th>Điểm trung bình</th>
                    <th>Học sinh giỏi</th>
                    <th>Chuyên cần</th>
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

@if($filters['scope'] !== 'multi_year' && $reportFocus !== 'student')
<div class="management-card mb-3">
    <h6 class="card-accent-title mb-3">Tổng kết theo môn học</h6>
    <div class="table-responsive">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Môn học</th>
                    <th>Số học sinh có điểm</th>
                    <th>Điểm trung bình</th>
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

<div class="management-card mb-3">
    <h6 class="card-accent-title mb-3">Tổng kết theo giáo viên</h6>
    <div class="table-responsive">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Giáo viên</th>
                    <th>Lớp phụ trách</th>
                    <th>Môn</th>
                    <th>Tổng định mức tiết/tuần</th>
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

<div class="management-card">
    <h6 class="card-accent-title mb-3">Danh sách học sinh trong phạm vi báo cáo</h6>
    <div class="table-responsive">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Mã học sinh</th>
                    <th>Họ tên</th>
                    <th>Lớp</th>
                    <th>Điểm trung bình</th>
                    <th>Học lực</th>
                    <th>Hạnh kiểm</th>
                    <th>Chuyên cần</th>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form.management-card');
        if (form) {
            const scopeSelect = form.querySelector('[name="scope"]');
            const gradeSelect = form.querySelector('[name="grade_level"]');
            const classSelect = form.querySelector('[name="class_id"]');
            const teacherSelect = form.querySelector('[name="teacher_id"]');
            const subjectSelect = form.querySelector('[name="subject_id"]');
            const studentSelect = form.querySelector('[name="student_id"]');
            const multiYearFields = form.querySelectorAll('[data-report-multi-year-field]');
            const statFields = form.querySelectorAll('[data-report-stat-field]');

            const syncMultiYear = () => {
                const isMultiYear = scopeSelect?.value === 'multi_year';
                multiYearFields.forEach((field) => field.hidden = !isMultiYear);
                statFields.forEach((field) => field.hidden = isMultiYear);
            };

            const syncClassOptions = () => {
                const grade = gradeSelect?.value || '';
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
                const grade = gradeSelect?.value || '';
                const classId = classSelect?.value || '';
                [...studentSelect?.options || []].forEach((option) => {
                    const gradeMatched = !grade || !option.value || option.dataset.grade === grade;
                    const classMatched = !classId || !option.value || option.dataset.class === classId;
                    option.hidden = !(gradeMatched && classMatched);
                });
                if (studentSelect?.selectedOptions[0]?.hidden) {
                    studentSelect.value = '';
                }
            };

            const syncFilters = () => {
                syncMultiYear();
                syncClassOptions();
                syncSubjectOptions();
                syncStudentOptions();
            };

            [scopeSelect, gradeSelect, classSelect, teacherSelect].forEach((select) => {
                select?.addEventListener('change', syncFilters);
            });
            syncFilters();
        }

        if (!window.Chart) {
            return;
        }

        const makeChart = (id, type, labels, values, colors) => {
            const element = document.getElementById(id);
            if (!element) {
                return;
            }

            new Chart(element, {
                type,
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderRadius: type === 'bar' ? 8 : 0,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type !== 'bar', position: 'bottom' }
                    },
                    scales: type === 'bar' ? {
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, .22)' } },
                        x: { grid: { display: false } }
                    } : {}
                }
            });
        };

        const makeDatasetChart = (id, type, labels, datasets) => {
            const element = document.getElementById(id);
            if (!element) {
                return;
            }

            new Chart(element, {
                type,
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true, position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, .22)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        };

        makeChart('studyChart', 'bar', @json($studyChartLabels), @json($studyChartValues), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#94A3B8']);
        makeChart('conductChart', 'doughnut', @json($conductChartLabels), @json($conductChartValues), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#94A3B8']);
        makeChart('attendanceChart', 'doughnut', @json($attendanceChartLabels), @json($attendanceChartValues), ['#22C55E', '#60A5FA', '#FACC15', '#F97316']);
        makeChart('yearChart', 'bar', @json($yearChartLabels), @json($yearAverageValues), ['rgba(217, 111, 22, .78)']);
        makeChart('yearStudentChart', 'bar', @json($yearChartLabels), @json($yearStudentValues), ['rgba(59, 130, 246, .82)']);
        makeDatasetChart('yearRateChart', 'line', @json($yearChartLabels), [
            { label: 'Chuyên cần', data: @json($yearAttendanceValues), borderColor: '#22C55E', backgroundColor: 'rgba(34, 197, 94, .12)', tension: .35 },
            { label: 'Hạnh kiểm tốt/khá', data: @json($yearConductValues), borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, .12)', tension: .35 },
            { label: 'Lên lớp', data: @json($yearPromotionValues), borderColor: '#F59E0B', backgroundColor: 'rgba(245, 158, 11, .12)', tension: .35 },
            { label: 'Tốt nghiệp', data: @json($yearGraduationValues), borderColor: '#8B5CF6', backgroundColor: 'rgba(139, 92, 246, .12)', tension: .35 },
        ]);
        makeChart('studentTrendChart', 'bar', @json($studentTrendLabels), @json($studentTrendValues), ['rgba(59, 130, 246, .82)']);
        makeChart('subjectDistributionChart', 'bar', @json($subjectDistributionLabels), @json($subjectDistributionValues), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316']);
    });
</script>
@endsection
