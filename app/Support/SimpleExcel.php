<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use ZipArchive;

class SimpleExcel
{
    public static function readRows(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        return $extension === 'xlsx'
            ? self::readXlsxRows($file->getRealPath())
            : self::readCsvRows($file->getRealPath());
    }

    public static function downloadXlsx(string $filename, array $headers, array $rows)
    {
        $path = tempnam(sys_get_temp_dir(), 'emis_xlsx_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::relsXml());
        $zip->addFromString('docProps/app.xml', self::appXml());
        $zip->addFromString('docProps/core.xml', self::coreXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml(array_merge([$headers], $rows)));
        $zip->close();

        return response()->download($path, Str::finish($filename, '.xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public static function downloadPdf(string $filename, array $headers, array $rows)
    {
        $html = self::buildPdfHtml($filename, $headers, $rows);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public static function normalizeHeader(string $header): string
    {
        $header = trim(Str::of($header)->replace("\xEF\xBB\xBF", '')->toString());
        $header = Str::ascii($header);
        $header = Str::lower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim((string) $header, '_');
    }

    private static function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        $headers = [];
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === []) {
                $headers = array_map(fn ($value) => self::normalizeHeader((string) $value), $data);
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $data[$index] ?? '';
                }
            }

            if (self::rowHasValue($row)) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private static function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            foreach ($shared->si ?? [] as $item) {
                $parts = [];
                if (isset($item->t)) {
                    $parts[] = (string) $item->t;
                }
                foreach ($item->r ?? [] as $run) {
                    $parts[] = (string) $run->t;
                }
                $sharedStrings[] = implode('', $parts);
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rawRows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/([A-Z]+)/', $reference, $matches);
                $index = self::excelColumnIndex($matches[1] ?? 'A');
                $type = (string) $cell['t'];

                if ($type === 's') {
                    $values[$index] = $sharedStrings[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $values[$index] = (string) ($cell->is->t ?? '');
                } else {
                    $values[$index] = (string) ($cell->v ?? '');
                }
            }

            if ($values !== []) {
                ksort($values);
                $rawRows[] = $values;
            }
        }

        if ($rawRows === []) {
            return [];
        }

        $headers = array_map(fn ($value) => self::normalizeHeader((string) $value), array_values($rawRows[0]));
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $normalizedValues = [];
            $max = max(array_keys($rawRow));
            for ($i = 0; $i <= $max; $i++) {
                $normalizedValues[$i] = $rawRow[$i] ?? '';
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $normalizedValues[$index] ?? '';
                }
            }

            if (self::rowHasValue($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function rowHasValue(array $row): bool
    {
        return array_filter($row, fn ($value) => trim((string) $value) !== '') !== [];
    }

    private static function buildPdfHtml(string $filename, array $headers, array $rows): string
    {
        $cleanHeaders = array_map(fn ($header) => self::cleanHeaderLabel((string) $header), $headers);
        $titleName = Str::headline(preg_replace('/\.[A-Za-z0-9]+$/', '', $filename));

        $headerColsHtml = implode('', array_map(fn ($h) => '<th style="padding: 10px 14px; font-weight: 600; color: #111827; background-color: #fff7ed; border-bottom: 2px solid #fed7aa; text-align: left; font-size: 13px;">' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>', $cleanHeaders));

        $rowsHtml = '';
        foreach ($rows as $rowIndex => $row) {
            $bgColor = $rowIndex % 2 === 0 ? '#ffffff' : '#fafafa';
            $rowsHtml .= '<tr style="background-color: ' . $bgColor . ';">';
            $rowValues = is_array($row) ? array_values($row) : (array) $row;
            foreach ($rowValues as $cell) {
                $val = trim((string) ($cell === '' || $cell === null ? '—' : $cell));
                $rowsHtml .= '<td style="padding: 9px 14px; border-bottom: 1px solid #e5e7eb; color: #1f2937; text-align: left; font-size: 13px;">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $rowsHtml .= '</tr>';
        }

        $nowStr = now()->format('d/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleName}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            color: #1f2937;
            background: #ffffff;
            margin: 0;
            padding: 24px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #ea580c;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px 0;
        }
        .meta {
            font-size: 13px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">📋 BÁO CÁO: {$titleName}</h1>
        <div class="meta">Trường THPT • Thời gian kết xuất: {$nowStr}</div>
    </div>
    <table>
        <thead>
            <tr>{$headerColsHtml}</tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>
    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 300);
        };
    </script>
</body>
</html>
HTML;
    }

    private static function cleanHeaderLabel(string $header): string
    {
        $normalized = Str::lower(trim($header));

        return match ($normalized) {
            'ma_hs', 'student_code', 'ma_hoc_sinh' => 'Mã HS',
            'ho_ten', 'name', 'full_name', 'ho_va_ten' => 'Họ và tên',
            'ngay_sinh', 'dob', 'birth_date' => 'Ngày sinh',
            'gioi_tinh', 'gender', 'sex' => 'Giới tính',
            'lop', 'class_name', 'class' => 'Lớp học',
            'que_quan', 'hometown' => 'Quê quán',
            'dan_toc', 'ethnicity' => 'Dân tộc',
            'trang_thai', 'status' => 'Trạng thái',
            'diem_so', 'score', 'average', 'gpa' => 'Điểm số',
            'hanh_kiem', 'conduct' => 'Hạnh kiểm',
            'mon_hoc', 'subject' => 'Môn học',
            'giao_vien', 'teacher' => 'Giáo viên',
            'created_at', 'ngay_tao' => 'Ngày tạo',
            'updated_at', 'ngay_cap_nhat' => 'Ngày cập nhật',
            'note', 'ghi_chu' => 'Ghi chú',
            default => function_exists('mb_convert_case') ? mb_convert_case(str_replace(['_', '-'], ' ', $header), MB_CASE_TITLE, 'UTF-8') : title_case(str_replace(['_', '-'], ' ', $header)),
        };
    }

    private static function excelColumnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private static function excelColumnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = intdiv($index - $mod, 26);
        }

        return $name;
    }

    private static function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="' . $excelRow . '">';
            foreach (array_values($row) as $columnIndex => $value) {
                $cell = self::excelColumnName($columnIndex) . $excelRow;
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $displayValue = $rowIndex === 0 ? self::uppercase((string) $value) : (string) $value;
                $xml .= '<c r="' . $cell . '"' . $style . ' t="inlineStr"><is><t>'
                    . self::escape($displayValue)
                    . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private static function uppercase(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private static function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Import" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF7ED"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf xfId="0"/><xf xfId="0" fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private static function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>School EMIS</Application></Properties>';
    }

    private static function coreXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>School EMIS</dc:creator></cp:coreProperties>';
    }
}
