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

        <h1>{{ $reportTitle }}</h1>
        <div class="muted">Thời điểm xuất báo cáo: {{ now()->format('d/m/Y H:i') }}</div>

        <div class="grid">
            @foreach($summaryCards as $card)
                <div class="metric">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}{{ $card['suffix'] }}</strong>
                </div>
            @endforeach
        </div>

        <h2>Phân bố học lực</h2>
        <table>
            <thead><tr><th>Mức học lực</th><th>Số lượng</th></tr></thead>
            <tbody>
            @foreach($studyDistribution as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
            @endforeach
            </tbody>
        </table>

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
    </div>
</body>
</html>
