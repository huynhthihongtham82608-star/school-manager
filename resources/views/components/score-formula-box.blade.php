@props([
    'scoreSetting',
    'class' => '',
])

<div {{ $attributes->merge(['class' => trim('score-formula-box ' . $class)]) }} data-score-weight-formula>
    <div class="score-formula-equation" aria-label="Công thức tính điểm trung bình môn học kỳ">
        <span class="score-formula-result-name">Điểm trung bình môn học kỳ =</span>
        <div class="score-formula-fraction">
            <div class="score-formula-line score-formula-numerator">
                <span class="score-formula-term">(Tổng điểm Đánh giá thường xuyên x <span class="score-formula-weight-token">W1=<span data-score-weight-label="gdtx">{{ $scoreSetting->weight_gdtx }}</span></span>)</span>
                <span class="score-formula-operator">+</span>
                <span class="score-formula-term">(Điểm Đánh giá giữa kỳ x <span class="score-formula-weight-token">W2=<span data-score-weight-label="dggk">{{ $scoreSetting->weight_dggk }}</span></span>)</span>
                <span class="score-formula-operator">+</span>
                <span class="score-formula-term">(Điểm Đánh giá cuối kỳ x <span class="score-formula-weight-token">W3=<span data-score-weight-label="dgck">{{ $scoreSetting->weight_dgck }}</span></span>)</span>
            </div>
            <div class="score-formula-divider"></div>
            <div class="score-formula-line score-formula-denominator">
                <span class="score-formula-term">(Tổng số cột Đánh giá thường xuyên x <span class="score-formula-weight-token">W1=<span data-score-weight-label="gdtx">{{ $scoreSetting->weight_gdtx }}</span></span>)</span>
                <span class="score-formula-operator">+</span>
                <span class="score-formula-term"><span class="score-formula-weight-token">W2=<span data-score-weight-label="dggk">{{ $scoreSetting->weight_dggk }}</span></span></span>
                <span class="score-formula-operator">+</span>
                <span class="score-formula-term"><span class="score-formula-weight-token">W3=<span data-score-weight-label="dgck">{{ $scoreSetting->weight_dgck }}</span></span></span>
            </div>
        </div>
    </div>
    <p class="score-formula-note">💡 Quy tắc làm tròn: Hệ thống tự động kết xuất dữ liệu và làm tròn kết quả đến đúng 1 chữ số thập phân bằng hàm .toFixed(1) theo quy chế học vụ quốc gia.</p>
</div>

@once
    <style>
        .score-formula-box {
            display: flex;
            width: 100%;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1.25rem;
            border: 1px solid #ffedd5;
            border-radius: 8px;
            background: rgba(255, 247, 237, .4);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .score-formula-equation {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .7rem;
            color: #1f2937;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            text-align: center;
        }

        .score-formula-result-name {
            color: #111827;
            font-size: 1rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .score-formula-fraction {
            display: flex;
            width: 100%;
            max-width: 920px;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
        }

        .score-formula-line {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: .38rem .55rem;
            color: #1f2937;
            font-size: 1rem;
            font-weight: 400;
        }

        .score-formula-term {
            display: inline-flex;
            align-items: baseline;
            gap: .22rem;
            white-space: nowrap;
        }

        .score-formula-operator {
            color: #9a3412;
            font-weight: 500;
            white-space: nowrap;
        }

        .score-formula-divider {
            width: 100%;
            max-width: 42rem;
            height: 1px;
            margin: .12rem 0;
            background: #fed7aa;
        }

        .score-formula-weight-token {
            display: inline-flex;
            align-items: baseline;
            gap: .12rem;
            color: rgba(154, 52, 18, .68);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .78rem;
            font-weight: 400;
            line-height: 1;
            white-space: nowrap;
        }

        .score-formula-note {
            margin: 0;
            color: #6b7280;
            font-size: .86rem;
            font-weight: 400;
            line-height: 1.45;
            text-align: left;
        }

        @media (max-width: 767.98px) {
            .score-formula-equation {
                align-items: flex-start;
                text-align: left;
            }

            .score-formula-fraction,
            .score-formula-line {
                align-items: flex-start;
                justify-content: flex-start;
            }

            .score-formula-term {
                white-space: normal;
            }
        }
    </style>
@endonce
