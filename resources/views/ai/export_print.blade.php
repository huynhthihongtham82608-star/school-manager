<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo AI Phân tích học tập</title>
    <style>
        body {
            margin: 0;
            padding: 32px;
            color: #1f2937;
            background: #f4f6f9;
            font-family: DejaVu Sans, Arial, sans-serif;
            line-height: 1.55;
        }

        .report {
            max-width: 920px;
            margin: 0 auto;
            padding: 28px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .12);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        h1 {
            margin: 0;
            font-size: 26px;
        }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 12px;
            color: #9a4f07;
            background: #fff1df;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .section {
            margin-top: 18px;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
        }

        .section h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        ul {
            margin: 0;
            padding-left: 20px;
        }

        li + li {
            margin-top: 6px;
        }

        .note {
            margin-top: 18px;
            padding: 14px 16px;
            color: #1d4ed8;
            background: #eff6ff;
            border-radius: 12px;
            font-size: 14px;
        }

        .actions {
            margin: 0 auto 18px;
            max-width: 920px;
            text-align: right;
        }

        .actions button {
            min-height: 38px;
            padding: 8px 16px;
            border: 0;
            border-radius: 10px;
            color: #ffffff;
            background: #e67e22;
            font-weight: 700;
            cursor: pointer;
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            .actions {
                display: none;
            }

            .report {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">In / Lưu PDF</button>
    </div>
    <main class="report">
        <header class="report-header">
            <div>
                <h1>Báo cáo AI Phân tích học tập</h1>
                <span class="badge">Trợ lý giáo dục</span>
            </div>
            <div>
                <strong>Ngày xuất:</strong><br>
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </header>

        @if(empty($analysis['has_data']) && !empty($analysis['no_data_message']))
            <div class="note" style="color:#92400e;background:#fffbeb;">
                {{ $analysis['no_data_message'] }}
            </div>
        @endif

        <section class="section">
            <h2>📊 Tổng quan</h2>
            <ul>
                @foreach($analysis['overview'] ?? ['Hiện chưa có đủ dữ liệu để AI phân tích.'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <section class="section">
            <h2>🌟 Điểm mạnh</h2>
            <ul>
                @foreach($analysis['strengths'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <section class="section">
            <h2>📘 Điểm cần cải thiện</h2>
            <ul>
                @foreach($analysis['improvements'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <section class="section">
            <h2>💡 Khuyến nghị</h2>
            <ul>
                @foreach($analysis['recommendations'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <div class="note">
            {{ $analysis['note'] ?? 'Lưu ý: Các nhận xét và khuyến nghị được tạo dựa trên dữ liệu hiện có trong hệ thống, chỉ mang tính chất tham khảo và hỗ trợ giáo viên trong quá trình theo dõi học sinh.' }}
        </div>
    </main>
</body>
</html>
