<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
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
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
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
        h1 { margin: 0 0 6px; font-size: 24px; }
        h2 { margin: 24px 0 10px; font-size: 17px; }
        .muted { color: #6b7280; font-size: 13px; }
        .report-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 18px;
            margin-top: 10px;
            font-size: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0;
        }
        .metric {
            min-height: 74px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff7ed;
        }
        .metric span {
            display: block;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }
        .metric strong {
            display: block;
            margin-top: 7px;
            font-size: 18px;
            line-height: 1.25;
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
            vertical-align: top;
        }
        th { background: #ffedd5; }
        .insight {
            margin: 8px 0;
            padding: 10px 12px;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: #f0fdf4;
        }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .page { margin: 0; border: 0; border-radius: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    @php
        $cards = collect($reportDashboard['cards'] ?? []);
        $profile = collect($reportDashboard['profile'] ?? []);
        $insights = collect($reportDashboard['insights'] ?? []);
        $table = $reportDashboard['table'] ?? ['title' => 'Bảng thống kê', 'headers' => [], 'rows' => []];
        $tableHeaders = collect($table['headers'] ?? []);
        $tableRows = collect($table['rows'] ?? []);
    @endphp
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

        @if($cards->isNotEmpty())
            <h2>Tổng quan</h2>
            <div class="grid">
                @foreach($cards as $card)
                    <div class="metric">
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        @endif

        @if($profile->isNotEmpty())
            <h2>Thông tin chính</h2>
            <table>
                <tbody>
                    @foreach($profile as $item)
                        <tr>
                            <th>{{ $item['label'] }}</th>
                            <td>{{ $item['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($insights->isNotEmpty())
            <h2>Nhận xét thống kê</h2>
            @foreach($insights as $insight)
                <div class="insight">{{ $insight }}</div>
            @endforeach
        @endif

        <h2>{{ $table['title'] ?? 'Bảng thống kê' }}</h2>
        <table>
            <thead>
                <tr>
                    @forelse($tableHeaders as $header)
                        <th>{{ $header }}</th>
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
                        <td colspan="{{ max(1, $tableHeaders->count()) }}">Chưa có dữ liệu phù hợp với bộ lọc.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Báo cáo được tạo tự động bởi Hệ thống Quản lý Trường THPT.
        </div>
    </div>
</body>
</html>
