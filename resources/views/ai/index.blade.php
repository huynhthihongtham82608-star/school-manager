@extends('layouts.app')
@section('title', 'AI Phân tích học tập')

@section('content')
@php
    $user = auth()->user();
    $usesAdminNavigation = $user->isAdmin() || $user->isStaff();
    $canRunAnalysis = $user->isAdmin() || $user->isStaff() || $user->isHomeroom();
    $tabs = [
        'analysis' => ['label' => 'Phân tích', 'icon' => 'bi-bar-chart-line', 'url' => route('ai.run.form')],
        'alerts' => ['label' => 'Cảnh báo', 'icon' => 'bi-exclamation-triangle', 'url' => route('ai.alerts')],
        'reports' => ['label' => 'Báo cáo AI', 'icon' => 'bi-pencil-square', 'url' => route('ai.reports')],
    ];
    $scopeOptions = [
        'student' => 'Học sinh',
        'class' => 'Lớp học',
        'school' => 'Toàn trường',
    ];
    $sectionCards = [
        'strengths' => ['title' => 'Điểm mạnh', 'icon' => '🌟', 'class' => 'ai-card-green'],
        'improvements' => ['title' => 'Điểm cần cải thiện', 'icon' => '📘', 'class' => 'ai-card-blue'],
        'recommendations' => ['title' => 'Khuyến nghị', 'icon' => '💡', 'class' => 'ai-card-yellow'],
    ];
    $attentionLabels = [
        'high' => ['label' => 'Cần hỗ trợ thêm', 'class' => 'bg-warning text-dark'],
        'medium' => ['label' => 'Nên theo dõi', 'class' => 'bg-info text-dark'],
        'low' => ['label' => 'Ổn định', 'class' => 'bg-success'],
    ];
    $cleanAiText = function (?string $text, string $fallback) {
        $text = trim((string) $text);

        if ($text === '') {
            return $fallback;
        }

        if (preg_match('/\[AI\]|TB=|N\/A|\b(stable|medium|high|low|true|false|null|undefined|risk|score|trend)\b/i', $text)) {
            return $fallback;
        }

        return $text;
    };
    $extractReportSections = function (?string $summary) {
        $summary = (string) $summary;
        $sections = [
            'strengths' => [],
            'improvements' => [],
            'recommendations' => [],
        ];
        $current = null;

        foreach (preg_split('/\R/u', $summary) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/\[AI\]|TB=|N\/A|\b(stable|medium|high|low|true|false|null|undefined|risk|score|trend)\b/i', $line)) {
                continue;
            }

            if (str_contains($line, 'Điểm mạnh')) {
                $current = 'strengths';
                continue;
            }
            if (str_contains($line, 'Điểm cần cải thiện')) {
                $current = 'improvements';
                continue;
            }
            if (str_contains($line, 'Khuyến nghị')) {
                $current = 'recommendations';
                continue;
            }
            if (str_starts_with($line, '❤️')) {
                $current = 'ignored';
                continue;
            }

            $line = ltrim($line, "- \t");
            if ($current === 'ignored') {
                continue;
            }

            if ($current) {
                $sections[$current][] = $line;
            } else {
                $sections['recommendations'][] = $line;
            }
        }

        return $sections;
    };
@endphp

@unless($usesAdminNavigation)
    <div class="ai-tabs">
        @foreach($tabs as $key => $tab)
            @if($key !== 'analysis' || $canRunAnalysis)
                <form method="GET" action="{{ $tab['url'] }}">
                    <button type="submit" class="ai-tab-button {{ $activeTab === $key ? 'active' : '' }}">
                        <i class="bi {{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                    </button>
                </form>
            @endif
        @endforeach
    </div>
@endunless

@if($activeTab === 'analysis')
    @if($canRunAnalysis)
        <div class="card ai-panel mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('ai.run.form') }}" class="row g-3 align-items-end mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Đối tượng phân tích</label>
                        <select class="form-select" name="scope" data-ai-scope-select>
                            @foreach($scopeOptions as $scopeKey => $scopeLabel)
                                <option value="{{ $scopeKey }}" @selected($analysisScope === $scopeKey)>{{ $scopeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYearId }}">
                    <div class="col-md-3">
                        <label class="form-label">Học kỳ</label>
                        <select class="form-select" name="semester_id" required>
                            <option value="">Chọn học kỳ</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected((request('semester_id') ?: $selectedSemesterId) === $semester->id)>
                                    {{ $semester->name }} @if($semester->schoolYear) - {{ $semester->schoolYear->name }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($analysisScope === 'student')
                        <div class="col-md-3">
                            <label class="form-label">Học sinh</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Chọn học sinh</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" @selected(request('student_id') === $student->id)>
                                        {{ $student->student_code }} - {{ $student->name }} @if($student->classRoom) - {{ $student->classRoom->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($analysisScope === 'class')
                        <div class="col-md-3">
                            <label class="form-label">Lớp học</label>
                            <select class="form-select" name="class_id" required>
                                <option value="">Chọn lớp</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected(request('class_id') === $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="student_id" value="">
                        <input type="hidden" name="class_id" value="">
                    @endif
                    <div class="col-md-12 d-flex justify-content-end">
                        <button class="btn btn-primary">
                            <i class="bi bi-stars me-1"></i>Phân tích
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($analysisScope === 'class' && request()->filled(['class_id', 'semester_id']))
            <form method="POST" action="{{ route('ai.run') }}" class="mb-3 d-flex justify-content-end">
                @csrf
                <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                <button class="btn btn-outline-primary">
                    <i class="bi bi-save2 me-1"></i>Lưu nhận xét & cảnh báo AI
                </button>
            </form>
        @endif

        @if($analysisResult)
            @php
                $query = array_merge(request()->query(), ['scope' => $analysisScope]);
                $subjectChartRows = collect($analysisResult['subject_averages'] ?? $analysisResult['subject_rows'] ?? [])->filter(fn ($row) => ($row['average'] ?? null) !== null)->values();
                $gradeChartRows = collect($analysisResult['grade_rows'] ?? [])->filter(fn ($row) => ($row['average'] ?? null) !== null)->values();
                $subjectLabels = $subjectChartRows->pluck('subject')->values();
                $subjectValues = $subjectChartRows->pluck('average')->values();
                $gradeLabels = $gradeChartRows->pluck('grade')->values();
                $gradeValues = $gradeChartRows->pluck('average')->values();
                $formatNumber = fn ($value) => $value === null ? 'Chưa có dữ liệu' : number_format((float) $value, 2, ',', '.');
                $formatPercent = fn ($value) => $value === null ? 'Chưa có dữ liệu' : number_format((float) $value, 1, ',', '.') . '%';
            @endphp

            <div class="ai-summary-toolbar mb-3">
                <div>
                    <span class="badge bg-success">Trợ lý giáo dục</span>
                    <span class="badge bg-info text-dark">{{ $analysisResult['attention_label'] ?? 'Phân tích tham khảo' }}</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('ai.export', array_merge($query, ['format' => 'excel'])) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất Excel
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('ai.export', array_merge($query, ['format' => 'pdf'])) }}" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Xuất PDF
                    </a>
                </div>
            </div>

            <div class="ai-metric-grid mb-3">
                @if($analysisResult['type'] === 'student')
                    <div class="ai-metric-card"><span>Điểm trung bình</span><strong>{{ $formatNumber($analysisResult['avg_now']) }}</strong></div>
                    <div class="ai-metric-card"><span>Xu hướng</span><strong>{{ $analysisResult['trend_label'] }}</strong></div>
                    <div class="ai-metric-card"><span>Chuyên cần</span><strong>{{ $formatPercent($analysisResult['attendance']['rate']) }}</strong></div>
                    <div class="ai-metric-card"><span>Hạnh kiểm</span><strong>{{ $analysisResult['conduct_label'] ?? 'Chưa có dữ liệu' }}</strong></div>
                @elseif($analysisResult['type'] === 'class')
                    <div class="ai-metric-card"><span>Sĩ số</span><strong>{{ $analysisResult['student_count'] }}</strong></div>
                    <div class="ai-metric-card"><span>Điểm trung bình lớp</span><strong>{{ $formatNumber($analysisResult['class_average']) }}</strong></div>
                    <div class="ai-metric-card"><span>Chuyên cần</span><strong>{{ $formatPercent($analysisResult['attendance_rate']) }}</strong></div>
                    <div class="ai-metric-card"><span>Học sinh tiến bộ</span><strong>{{ $analysisResult['improved_count'] }}</strong></div>
                @else
                    <div class="ai-metric-card"><span>Số lớp</span><strong>{{ collect($analysisResult['class_rows'])->count() }}</strong></div>
                    <div class="ai-metric-card"><span>Theo khối</span><strong>{{ collect($analysisResult['grade_rows'])->count() }}</strong></div>
                    <div class="ai-metric-card"><span>Theo môn</span><strong>{{ collect($analysisResult['subject_rows'])->count() }}</strong></div>
                    <div class="ai-metric-card"><span>Học kỳ</span><strong>{{ $analysisResult['semester']->name ?? 'Tất cả' }}</strong></div>
                @endif
            </div>

            @if(empty($analysisResult['has_data']) && !empty($analysisResult['no_data_message']))
                <div class="alert alert-warning mb-3">
                    {{ $analysisResult['no_data_message'] }}
                </div>
            @endif

            @if($analysisResult['type'] !== 'student')
                <div class="row g-3 mb-3">
                    @if($subjectLabels->isNotEmpty())
                        <div class="col-lg-6">
                            <div class="card ai-chart-card h-100">
                                <div class="card-body">
                                    <h6 class="ai-card-title">Thống kê theo môn</h6>
                                    <canvas id="aiSubjectChart"></canvas>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($gradeLabels->isNotEmpty())
                        <div class="col-lg-6">
                            <div class="card ai-chart-card h-100">
                                <div class="card-body">
                                    <h6 class="ai-card-title">Thống kê theo khối</h6>
                                    <canvas id="aiGradeChart"></canvas>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="ai-result-grid mb-3">
                <article class="ai-result-card ai-section-card ai-card-blue">
                    <h6><span>📊</span>Tổng quan</h6>
                    <ul>
                        @foreach($analysisResult['overview'] ?? ['Hiện chưa có đủ dữ liệu để AI phân tích.'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
                @foreach($sectionCards as $key => $section)
                    <article class="ai-result-card ai-section-card {{ $section['class'] }}">
                        <h6><span>{{ $section['icon'] }}</span>{{ $section['title'] }}</h6>
                        <ul>
                            @foreach($analysisResult[$key] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="alert alert-info ai-note mb-0">
                {{ $analysisResult['note'] }}
            </div>
        @else
            <div class="card ai-guide-card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-robot"></i>
                        <strong>AI Phân tích học tập</strong>
                        <span>Chọn đối tượng cần phân tích, chọn dữ liệu phù hợp và nhấn “Phân tích” để xem kết quả.</span>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="card">
            <div class="empty-state"><i class="bi bi-lock"></i>Bạn không có quyền chạy phân tích AI.</div>
        </div>
    @endif
@elseif($activeTab === 'alerts')
    <div class="ai-result-grid">
        @forelse($alerts as $alert)
            @php($attention = $attentionLabels[$alert->risk_level] ?? $attentionLabels['low'])
            @php($alertMessage = $cleanAiText($alert->message, 'Dựa trên dữ liệu hiện có, học sinh nên được giáo viên theo dõi và hỗ trợ thêm trong thời gian tới.'))
            <article class="ai-result-card ai-section-card ai-card-yellow">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="badge {{ $attention['class'] }}">{{ $attention['label'] }}</span>
                    <span class="text-muted small">{{ optional($alert->created_at)->format('d/m/Y H:i') }}</span>
                </div>
                <h6><span>💡</span>{{ $alert->student?->name ?? 'Học sinh' }}</h6>
                <p>{{ $alertMessage }}</p>
                <div class="ai-result-meta">
                    <span>{{ $alert->classRoom?->name ?? 'Chưa có lớp' }}</span>
                    <span>{{ $alert->semester?->name ?? 'Chưa có học kỳ' }}</span>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có cảnh báo cần theo dõi.</div>
            </div>
        @endforelse
    </div>
@else
    <div class="ai-result-grid">
        @forelse($reports as $report)
            @php($parsed = $extractReportSections($report->summary))
            <article class="ai-result-card ai-report-card">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    @php($trendText = ['up' => 'Có tiến bộ', 'down' => 'Có xu hướng giảm', 'stable' => 'Ổn định'][$report->trend] ?? 'Nhận xét AI')
                    <span class="badge bg-info text-dark">{{ $trendText }}</span>
                    <span class="text-muted small">{{ optional($report->created_at)->format('d/m/Y H:i') }}</span>
                </div>
                <h6>{{ $report->student?->name ?? 'Học sinh' }}</h6>
                <div class="ai-mini-sections">
                    @foreach($sectionCards as $key => $section)
                        <div class="ai-mini-section">
                            <strong>{{ $section['icon'] }} {{ $section['title'] }}</strong>
                            <ul>
                                @foreach($parsed[$key] ?: ['Chưa có dữ liệu chi tiết.'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <div class="ai-result-meta">
                    <span>{{ $report->semester?->name ?? 'Chưa có học kỳ' }}</span>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-file-earmark-text"></i>Chưa có nhận xét AI.</div>
            </div>
        @endforelse
    </div>
@endif

@if(filled($inspirationMessage ?? null))
    <section class="card ai-inspiration-card mt-3">
        <div class="card-body">
            <div class="ai-inspiration-title">
                <span>🌱</span>
                <strong>Góc truyền cảm hứng</strong>
            </div>
            <p>{{ $inspirationMessage }}</p>
        </div>
    </section>
@endif

@if($activeTab === 'analysis' && !empty($analysisResult) && $analysisResult['type'] !== 'student')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) {
                return;
            }

            const makeBarChart = (id, labels, values, color) => {
                const element = document.getElementById(id);
                if (!element || !labels.length) {
                    return;
                }

                new Chart(element, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: color,
                            borderRadius: 8,
                            maxBarThickness: 44
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, suggestedMax: 10, grid: { color: 'rgba(148, 163, 184, .22)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            };

            makeBarChart('aiSubjectChart', @json($subjectLabels), @json($subjectValues), 'rgba(59, 130, 246, .72)');
            makeBarChart('aiGradeChart', @json($gradeLabels), @json($gradeValues), 'rgba(34, 197, 94, .72)');
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-ai-scope-select]').forEach((select) => {
            select.addEventListener('change', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('scope', select.value);
                url.searchParams.delete('student_id');
                url.searchParams.delete('class_id');
                window.location.href = url.toString();
            });
        });
    });
</script>
@endsection
