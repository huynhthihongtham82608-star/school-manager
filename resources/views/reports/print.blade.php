<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }}</title>
    <style>
        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #f8fafc;
        }
        @page {
            size: A4;
            margin: 14mm;
        }
        .page {
            max-width: 1080px;
            margin: 24px auto;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }
        .report-header {
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 14px;
            align-items: center;
            padding-bottom: 16px;
            margin-bottom: 18px;
            border-bottom: 2px solid #fed7aa;
        }
        .report-logo {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #d96f16;
            border-radius: 16px;
            background: #fff7ed;
            font-weight: 800;
        }
        .report-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .report-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 18px;
            margin-top: 10px;
            font-size: 12px;
        }
        h2 {
            margin: 24px 0 10px;
            font-size: 17px;
        }
        .muted {
            color: #6b7280;
            font-size: 13px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        .metric {
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff7ed;
        }
        .metric span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }
        .metric strong {
            display: block;
            margin-top: 5px;
            font-size: 22px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #ffedd5;
        }
        .print-chart {
            display: grid;
            gap: 8px;
            margin: 10px 0 18px;
        }
        .print-chart-row {
            display: grid;
            grid-template-columns: 160px 1fr 52px;
            gap: 10px;
            align-items: center;
            font-size: 12px;
        }
        .print-chart-track {
            height: 12px;
            overflow: hidden;
            border-radius: 999px;
            background: #f3f4f6;
        }
        .print-chart-fill {
            height: 100%;
            border-radius: inherit;
            background: #d96f16;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 14px;
        }
        button {
            padding: 10px 14px;
            color: #fff;
            border: 0;
            border-radius: 10px;
            background: #d96f16;
            font-weight: 700;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; }
            .page { margin: 0; border: 0; border-radius: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="actions">
            <button type="button" onclick="window.print()">In hoặc lưu PDF</button>
        </div>

        <div class="report-header">
            <div class="report-logo">
                @if($systemSetting->logoUrl())
                    <img src="{{ $systemSetting->logoUrl() }}" alt="Logo">
                @else
                    {{ $systemSetting->short_name ?: 'TH' }}
                @endif
            </div>
            <div>
                <div class="muted">{{ $systemSetting->school_name }}</div>
                <h1>{{ $reportTitle }}</h1>
                <div class="report-meta">
                    <div><strong>Năm học:</strong> {{ $selectedYear?->name ?: 'Chưa chọn' }}</div>
                    <div><strong>Học kỳ:</strong> {{ $selectedSemester?->normalizedName() ?: 'Cả năm' }}</div>
                    <div><strong>Ngày xuất:</strong> {{ now()->format('d/m/Y H:i') }}</div>
                    <div><strong>Người xuất:</strong> {{ $exportedBy }}</div>
                </div>
            </div>
        </div>

        <div class="grid">
            @foreach($summaryCards as $card)
                <div class="metric">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}{{ $card['suffix'] }}</strong>
                </div>
            @endforeach
        </div>

        @if($reportFocus === 'student')
            <h2>Hồ sơ học tập học sinh</h2>
            @if($studentReport)
                <table>
                    <tbody>
                        <tr><th>Học sinh</th><td>{{ $studentReport['student']->student_code }} - {{ $studentReport['student']->name }}</td></tr>
                        <tr><th>Lớp</th><td>{{ $studentReport['student']->classRoom?->name ?? 'Chưa có lớp' }}</td></tr>
                        <tr><th>Điểm trung bình</th><td>{{ $studentReport['average'] ?? 'Chưa có dữ liệu' }}</td></tr>
                        <tr><th>Chuyên cần</th><td>{{ $studentReport['attendance_rate'] === null ? 'Chưa có dữ liệu' : $studentReport['attendance_rate'] . '%' }}</td></tr>
                        <tr><th>Nhận xét</th><td>{{ $studentReport['comment'] ?: 'Chưa có nhận xét cho học sinh trong phạm vi báo cáo.' }}</td></tr>
                    </tbody>
                </table>
            @else
                <p>Vui lòng chọn học sinh để xuất báo cáo cá nhân.</p>
            @endif
        @elseif($filters['scope'] === 'multi_year')
            <h2>So sánh nhiều năm học</h2>
            @if($yearComparison->count() >= 2)
                <table>
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
                    @foreach($yearComparison as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['student_count'] }}</td>
                            <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                            <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                            <td>{{ $row['conduct_good_rate'] === null ? 'Chưa có dữ liệu' : $row['conduct_good_rate'] . '%' }}</td>
                            <td>{{ $row['promotion_rate'] === null ? 'Chưa có dữ liệu' : $row['promotion_rate'] . '%' }}</td>
                            <td>{{ $row['graduation_rate'] === null ? 'Chưa có dữ liệu' : $row['graduation_rate'] . '%' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p>Vui lòng chọn khoảng từ 2 năm học trở lên để so sánh.</p>
            @endif
        @else
            @php($studyTotal = collect($studyDistribution)->sum('value'))
            <h2>Biểu đồ học lực</h2>
            <div class="print-chart">
                @foreach($studyDistribution as $row)
                    <div class="print-chart-row">
                        <span>{{ $row['label'] }}</span>
                        <div class="print-chart-track"><div class="print-chart-fill" style="width: {{ $studyTotal > 0 ? round($row['value'] / $studyTotal * 100, 1) : 0 }}%"></div></div>
                        <strong>{{ $row['value'] }}</strong>
                    </div>
                @endforeach
            </div>

            <h2>Phân bố hạnh kiểm</h2>
            <table>
                <thead><tr><th>Mức hạnh kiểm</th><th>Số lượng</th></tr></thead>
                <tbody>
                @foreach($conductDistribution as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
                @endforeach
                </tbody>
            </table>

            <h2>Tổng kết theo lớp</h2>
            <table>
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
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['student_count'] }}</td>
                        <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                        <td>{{ $row['excellent_count'] }}</td>
                        <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Chưa có dữ liệu.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif

        @if($aiInsights)
            <h2>Nhận xét và gợi ý</h2>
            <table>
                <tbody>
                @foreach($aiInsights as $insight)
                    <tr><td>{{ $insight }}</td></tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if($filters['scope'] !== 'multi_year' && $reportFocus !== 'student')
            <h2>Danh sách học sinh</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã học sinh</th>
                        <th>Họ tên</th>
                        <th>Lớp</th>
                        <th>Điểm trung bình</th>
                        <th>Học lực</th>
                        <th>Chuyên cần</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($studentRows as $row)
                    <tr>
                        <td>{{ $row['student']->student_code }}</td>
                        <td>{{ $row['student']->name }}</td>
                        <td>{{ $row['student']->classRoom?->name ?? 'Chưa có lớp' }}</td>
                        <td>{{ $row['average'] ?? 'Chưa có dữ liệu' }}</td>
                        <td>{{ [
                            'excellent' => 'Giỏi',
                            'good' => 'Khá',
                            'average' => 'Trung bình',
                            'needs_support' => 'Cần hỗ trợ',
                            'no_data' => 'Chưa có dữ liệu',
                        ][$row['study_rank']] ?? 'Chưa có dữ liệu' }}</td>
                        <td>{{ $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Không có dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
