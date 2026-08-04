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
        $pdf = self::buildPdf($headers, $rows);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . addslashes(Str::finish($filename, '.pdf')) . '"',
            'Content-Length' => strlen($pdf),
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

    private static function buildPdf(array $headers, array $rows): string
    {
        $objects = [];
        $pages = [];
        $linesPerPage = 28;
        $chunks = array_chunk($rows, $linesPerPage);
        if ($chunks === []) {
            $chunks = [[]];
        }

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $fontObjectNumber = 3;
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($chunks as $pageIndex => $chunk) {
            $contentObjectNumber = count($objects) + 2;
            $pageObjectNumber = count($objects) + 1;
            $pages[] = $pageObjectNumber . ' 0 R';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 ' . $fontObjectNumber . ' 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
            $objects[] = self::pdfStream(self::pdfPageContent($headers, $chunk, $pageIndex + 1, count($chunks)));
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pages) . '] /Count ' . count($pages) . ' >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        return $pdf
            . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n"
            . "startxref\n" . $xrefOffset . "\n%%EOF";
    }

    private static function pdfPageContent(array $headers, array $rows, int $page, int $totalPages): string
    {
        $content = "BT\n/F1 14 Tf\n50 552 Td\n" . self::pdfText('BÁO CÁO DỮ LIỆU HỌC VỤ') . " Tj\n";
        $content .= "/F1 8 Tf\n0 -18 Td\n" . self::pdfText('Trang ' . $page . '/' . $totalPages . ' - Xuất ngày ' . now()->format('d/m/Y H:i')) . " Tj\n";
        $content .= "/F1 8 Tf\n0 -24 Td\n" . self::pdfText(self::pdfRow($headers, 150)) . " Tj\n";

        foreach ($rows as $row) {
            $content .= "0 -16 Td\n" . self::pdfText(self::pdfRow($row, 150)) . " Tj\n";
        }

        return $content . "ET";
    }

    private static function pdfRow(array $row, int $limit): string
    {
        $text = collect($row)
            ->map(fn ($value) => trim((string) ($value === '' || $value === null ? '—' : $value)))
            ->implode(' | ');

        return Str::limit($text, $limit, '');
    }

    private static function pdfStream(string $content): string
    {
        return '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    }

    private static function pdfText(string $value): string
    {
        $value = str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", "\\(", "\\)", ' ', ' '], $value);

        return '(' . $value . ')';
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
