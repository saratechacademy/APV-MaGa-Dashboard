<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Minimal .xlsx writer that streams rows to disk: each sheet's XML is written
 * row by row to a temporary file and zipped at the end, so memory stays flat
 * however long the export. Empty cells are left out entirely — a ThingsBoard
 * export row holds one value among dozens of columns, and writing only what is
 * there is what keeps a month-long export to seconds.
 *
 * Supports what the exports need: several named sheets, numbers and text
 * (text always stored as text, never as a formula), and a styled header row.
 */
class XlsxStreamWriter
{
    private array $sheets = [];   // [name, xml temp path]
    private $handle = null;
    private int $row = 0;

    public function addSheet(string $name): void
    {
        $this->closeSheet();

        $path = tempnam(sys_get_temp_dir(), 'apv-sheet-');
        $this->handle = fopen($path, 'w');
        $this->sheets[] = [$name, $path];
        $this->row = 0;

        fwrite($this->handle, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // Keep the header row in view while scrolling.
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetData>');
    }

    public function addRow(array $values, bool $header = false): void
    {
        $n   = ++$this->row;
        $xml = '<row r="' . $n . '">';

        foreach (array_values($values) as $i => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $ref   = self::column($i) . $n;
            $style = $header ? ' s="1"' : '';

            // Numbers can arrive as strings (MySQL, manual entries): store
            // plain decimals as numbers so Excel can compute with them.
            if (is_int($value) || is_float($value) || preg_match('/^-?(0|[1-9]\d*)(\.\d+)?$/', (string) $value)) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $text = htmlspecialchars((string) $value, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
                $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
        }

        fwrite($this->handle, $xml . '</row>');
    }

    /** Zips the workbook into $path. */
    public function save(string $path): void
    {
        $this->closeSheet();
        if (!$this->sheets) {
            $this->addSheet('Sheet');
            $this->closeSheet();
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot write {$path}");
        }

        $sheetTypes = $sheetEntries = $sheetRels = '';
        foreach ($this->sheets as $i => [$name, $xmlPath]) {
            $id = $i + 1;
            $zip->addFile($xmlPath, "xl/worksheets/sheet{$id}.xml");
            $sheetTypes   .= '<Override PartName="/xl/worksheets/sheet' . $id . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetEntries .= '<sheet name="' . htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $sheetRels    .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        }
        $stylesId = count($this->sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $sheetTypes . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetEntries . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $sheetRels
            . '<Relationship Id="rId' . $stylesId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Style 0: default. Style 1: header — bold white text on blue.
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1D6ED8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>');

        $zip->close();

        foreach ($this->sheets as [, $xmlPath]) {
            @unlink($xmlPath);
        }
        $this->sheets = [];
    }

    private function closeSheet(): void
    {
        if ($this->handle) {
            fwrite($this->handle, '</sheetData></worksheet>');
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private static function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26) . $name;
        }

        return $name;
    }
}
