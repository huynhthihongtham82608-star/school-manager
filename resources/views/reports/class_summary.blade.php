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
        'school_year' => 'Theo năm học',
        'semester' => 'Theo học kỳ',
        'grade' => 'Theo khối',
        'class' => 'Theo lớp',
        'teacher' => 'Theo giáo viên',
        'subject' => 'Theo môn học',
        'student' => 'Theo học sinh',
        'three_years' => 'So sánh 3 năm',
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
@endphp

<div class="page-heading">
    <div>
        <h5>Báo cáo tổng hợp</h5>
        <div class="text-muted">Theo dõi học lực, hạnh kiểm, chuyên cần, tốt nghiệp và so sánh dữ liệu qua các năm.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
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
            <label class="form-label">Loại báo cáo</label>
            <select name="scope" class="form-select">
                @foreach($scopeLabels as $scopeKey => $scopeLabel)
                    <option value="{{ $scopeKey }}" @selected($filters['scope'] === $scopeKey)>{{ $scopeLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select">
                @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" @selected((string) $filters['school_year_id'] === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Học kỳ</label>
            <select name="semester_id" class="form-select">
                <option value="">Cả năm</option>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->normalizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select">
                <option value="">Tất cả khối</option>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected((string) $filters['grade_level'] === (string) $grade)>Khối {{ $grade }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Lớp</label>
            <select name="class_id" class="form-select">
                <option value="">Tất cả lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select">
                <option value="">Tất cả giáo viên</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string) $filters['teacher_id'] === (string) $teacher->id)>{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-xl-2">
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select">
                <option value="">Tất cả môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-xl-3">
            <label class="form-label">Học sinh</label>
            <select name="student_id" class="form-select">
                <option value="">Tất cả học sinh</option>
                @foreach($studentsForFilter as $student)
                    <option value="{{ $student->id }}" @selected((string) $filters['student_id'] === (string) $student->id)>
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
        <span class="badge text-bg-warning">{{ $scopeLabels[$filters['scope']] ?? 'Báo cáo' }}</span>
    </div>

    <div class="row g-3">
        @foreach($summaryCards as $card)
            <div class="col-6 col-xl-3">
                <div class="report-metric-card">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}{{ $card['suffix'] }}</strong>
                </div>
            </div>
        @endforeach
    </div>
</div>

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
    <div class="col-lg-6">
        <div class="card report-chart-card h-100">
            <div class="card-body">
                <h6 class="card-accent-title">So sánh các năm gần nhất</h6>
                @if($yearComparison->isNotEmpty())
                    <canvas id="yearChart"></canvas>
                @else
                    <div class="empty-state"><i class="bi bi-graph-up"></i>Chưa có dữ liệu so sánh.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="management-card h-100">
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
    </div>
    <div class="col-xl-6">
        <div class="management-card h-100">
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
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="management-card h-100">
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
    </div>
    <div class="col-xl-6">
        <div class="management-card h-100">
            <h6 class="card-accent-title mb-3">Tổng kết theo giáo viên</h6>
            <div class="table-responsive">
                <table class="table content-table align-middle">
                    <thead>
                        <tr>
                            <th>Giáo viên</th>
                            <th>Lớp phụ trách</th>
                            <th>Môn</th>
                            <th>Số tiết/tuần</th>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
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

        makeChart('studyChart', 'bar', @json($studyChartLabels), @json($studyChartValues), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#94A3B8']);
        makeChart('conductChart', 'doughnut', @json($conductChartLabels), @json($conductChartValues), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#94A3B8']);
        makeChart('attendanceChart', 'doughnut', @json($attendanceChartLabels), @json($attendanceChartValues), ['#22C55E', '#60A5FA', '#FACC15', '#F97316']);
        makeChart('yearChart', 'bar', @json($yearChartLabels), @json($yearAverageValues), ['rgba(217, 111, 22, .78)']);
    });
</script>
@endsection
