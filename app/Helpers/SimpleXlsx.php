<?php

namespace App\Helpers;

use ZipArchive;

/**
 * Minimal .xlsx reader and writer using only PHP's zip + XML extensions (the shared hosting has no Composer, so no
 * PhpSpreadsheet). Reads cell text from one sheet; writes simple sheets with a bold header row, column widths, a
 * frozen header and optional drop-down lists. Not a general spreadsheet library: no formulas, dates or merged cells.
 */
class SimpleXlsx
{
    /**
     * Rows of one sheet as arrays of strings, keyed by column index (0 = A). $sheet_name picks a sheet by name
     * (case-insensitive); otherwise the first sheet is read. Empty rows are skipped; each row keeps its Excel row
     * number under the key '_row'. Returns null if the file isn't a readable .xlsx.
     */
    public static function read(string $path, ?string $sheet_name = null, int $max_rows = 5000): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $workbook = self::xml($zip->getFromName('xl/workbook.xml'));
        $rels = self::xml($zip->getFromName('xl/_rels/workbook.xml.rels'));
        if (!$workbook || !$rels) {
            $zip->close();
            return null;
        }

        // Sheet name -> relationship id -> file inside the zip
        $targets = [];
        foreach ($rels->Relationship as $rel) {
            $targets[(string) $rel['Id']] = (string) $rel['Target'];
        }
        $sheet_file = null;
        foreach ($workbook->sheets->sheet as $sheet) {
            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $file = $targets[$rid] ?? null;
            if (!$file) {
                continue;
            }
            $file = str_starts_with($file, '/') ? ltrim($file, '/') : 'xl/' . $file;
            if ($sheet_file === null) {
                $sheet_file = $file;
            }
            if ($sheet_name !== null && strcasecmp((string) $sheet['name'], $sheet_name) === 0) {
                $sheet_file = $file;
                break;
            }
        }

        $shared = [];
        if ($strings = self::xml($zip->getFromName('xl/sharedStrings.xml'))) {
            foreach ($strings->si as $si) {
                $shared[] = self::text($si);
            }
        }

        $sheet = $sheet_file ? self::xml($zip->getFromName($sheet_file)) : null;
        $zip->close();
        if (!$sheet) {
            return null;
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            $next = 0;
            foreach ($row->c as $cell) {
                $index = isset($cell['r']) ? self::column_index((string) $cell['r']) : $next;
                $next = $index + 1;
                $type = (string) $cell['t'];
                if ($type === 's') {
                    $value = $shared[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = self::text($cell->is);
                } else {
                    $value = (string) $cell->v;
                    // Whole numbers stored as floats ("15551234567.0") read back as written
                    if ($type === '' && is_numeric($value) && floor((float) $value) == (float) $value && abs((float) $value) < 1e15) {
                        $value = number_format((float) $value, 0, '.', '');
                    }
                }
                $values[$index] = trim(str_replace("\xC2\xA0", ' ', $value));
            }
            if (implode('', $values) === '') {
                continue;
            }
            $values['_row'] = (int) $row['r'] ?: count($rows) + 1;
            $rows[] = $values;
            if (count($rows) > $max_rows) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Writes an .xlsx. $sheets: [name => ['rows' => [[cell, …], …], 'widths' => [chars, …],
     * 'header' => true (bold first row, frozen), 'lists' => [['range' => 'D2:D501', 'source' => "Categories!\$A\$2:\$A\$90"]],
     * 'wrap' => [column indexes to wrap]]].
     */
    public static function write(string $path, array $sheets): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create ' . $path);
        }

        $names = array_keys($sheets);
        $overrides = '';
        $workbook_sheets = '';
        $workbook_rels = '';
        foreach ($names as $i => $name) {
            $n = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbook_sheets .= '<sheet name="' . self::esc($name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $workbook_rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', self::sheet_xml($sheets[$name]));
        }
        $styles_rel = count($names) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbook_sheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $workbook_rels
            . '<Relationship Id="rId' . $styles_rel . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Styles: 0 normal, 1 bold white on WWD red (header), 2 wrapped text, 3 grey italic (notes)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Arial"/></font><font><i/><sz val="10"/><color rgb="FF6B7280"/><name val="Arial"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDD3F3B"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"><alignment vertical="top"/></xf>'
            . '<xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1" applyNumberFormat="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1" applyNumberFormat="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->close();
    }

    private static function sheet_xml(array $sheet): string
    {
        $rows = $sheet['rows'] ?? [];
        $header = $sheet['header'] ?? true;
        $wrap = array_flip($sheet['wrap'] ?? []);
        $notes = array_flip($sheet['note_rows'] ?? []);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        if ($header) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }
        if (!empty($sheet['widths'])) {
            $xml .= '<cols>';
            foreach ($sheet['widths'] as $i => $width) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $width . '" customWidth="1" style="0"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach (array_values($rows) as $r => $cells) {
            $row_number = $r + 1;
            $xml .= '<row r="' . $row_number . '"' . ($header && $r === 0 ? ' ht="30" customHeight="1"' : '') . '>';
            foreach (array_values($cells) as $c => $value) {
                $style = $header && $r === 0 ? 1 : (isset($notes[$r]) ? 3 : (isset($wrap[$c]) ? 2 : 0));
                $ref = self::column_letter($c) . $row_number;
                if ($value === null || $value === '') {
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                    continue;
                }
                $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::esc((string) $value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';
        if (!empty($sheet['lists'])) {
            $xml .= '<dataValidations count="' . count($sheet['lists']) . '">';
            foreach ($sheet['lists'] as $list) {
                $xml .= '<dataValidation type="list" errorStyle="warning" allowBlank="1" showErrorMessage="1"'
                    . ' errorTitle="Not in the list" error="' . self::esc($list['error'] ?? 'Choose a value from the list.') . '"'
                    . ' sqref="' . $list['range'] . '"><formula1>' . self::esc($list['source']) . '</formula1></dataValidation>';
            }
            $xml .= '</dataValidations>';
        }
        return $xml . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
    }

    private static function xml($content): ?\SimpleXMLElement
    {
        if (!is_string($content) || $content === '') {
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previous);
        return $xml ?: null;
    }

    // Text of a shared string / inline string, including rich-text runs
    private static function text(\SimpleXMLElement $node): string
    {
        if (isset($node->t)) {
            return (string) $node->t;
        }
        $text = '';
        foreach ($node->r as $run) {
            $text .= (string) $run->t;
        }
        return $text;
    }

    // "C12" -> 2
    private static function column_index(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }
        return max(0, $index - 1);
    }

    // 2 -> "C"
    public static function column_letter(int $index): string
    {
        $letters = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letters = chr(65 + ($n - 1) % 26) . $letters;
        }
        return $letters;
    }

    private static function esc(string $value): string
    {
        // Control characters other than tab/newline are not allowed in XML
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
