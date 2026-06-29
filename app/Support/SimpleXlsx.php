<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;

class SimpleXlsx
{
    public function __construct(
        protected string $sheetName,
        protected array $headers,
        protected array $rows,
        protected array $columnWidths = []
    ) {}

    public function output(): string
    {
        return $this->buildZip([
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/app.xml' => $this->appXml(),
            'docProps/core.xml' => $this->coreXml(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $this->worksheetXml(),
        ]);
    }

    protected function buildZip(array $files): string
    {
        if ($files === []) {
            throw new RuntimeException('Tidak ada file yang dapat ditulis ke Excel.');
        }

        [$dosTime, $dosDate] = $this->dosDateTime();
        $body = '';
        $centralDirectory = '';
        $offset = 0;

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', $name);
            $size = strlen($contents);
            $crc = (int) sprintf('%u', crc32($contents));
            $nameLength = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0
            );

            $body .= $localHeader . $name . $contents;

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $nameLength + $size;
        }

        $centralDirectoryOffset = strlen($body);
        $centralDirectorySize = strlen($centralDirectory);
        $entryCount = count($files);

        $endRecord = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            $centralDirectorySize,
            $centralDirectoryOffset,
            0
        );

        return $body . $centralDirectory . $endRecord;
    }

    protected function dosDateTime(): array
    {
        $timestamp = time();
        $year = max(1980, (int) date('Y', $timestamp));

        $dosTime = ((int) date('H', $timestamp) << 11)
            | ((int) date('i', $timestamp) << 5)
            | ((int) floor(((int) date('s', $timestamp)) / 2));

        $dosDate = (($year - 1980) << 9)
            | ((int) date('n', $timestamp) << 5)
            | (int) date('j', $timestamp);

        return [$dosTime, $dosDate];
    }

    protected function worksheetXml(): string
    {
        $columnCount = count($this->headers);
        $rowCount = max(1, count($this->rows) + 1);
        $lastColumn = $this->columnName($columnCount);
        $lastCell = $lastColumn . $rowCount;

        $cols = '';
        for ($i = 1; $i <= $columnCount; $i++) {
            $width = $this->columnWidths[$i - 1] ?? 18;
            $cols .= '<col min="' . $i . '" max="' . $i . '" width="' . $width . '" customWidth="1"/>';
        }

        $sheetData = '<row r="1" ht="34" customHeight="1">';
        foreach ($this->headers as $index => $header) {
            $cell = $this->columnName($index + 1) . '1';
            $sheetData .= $this->inlineStringCell($cell, (string) $header, 1);
        }
        $sheetData .= '</row>';

        foreach ($this->rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetData .= '<row r="' . $excelRow . '">';

            foreach ($this->headers as $columnIndex => $_header) {
                $cell = $this->columnName($columnIndex + 1) . $excelRow;
                $value = $row[$columnIndex] ?? null;
                $style = $columnIndex === 1 ? 3 : 4;

                if ($value instanceof DateTimeInterface) {
                    $sheetData .= $this->numberCell($cell, $this->dateToExcelSerial($value), 2);
                } elseif (is_int($value) || is_float($value)) {
                    $sheetData .= $this->numberCell($cell, $value, $style);
                } else {
                    $sheetData .= $this->inlineStringCell($cell, $value === null || $value === '' ? '-' : (string) $value, $style);
                }
            }

            $sheetData .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $lastCell . '"/>'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCell . '"/>'
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    protected function inlineStringCell(string $cell, string $value, int $style = 0): string
    {
        return '<c r="' . $cell . '" t="inlineStr" s="' . $style . '"><is><t>' . $this->escape($value) . '</t></is></c>';
    }

    protected function numberCell(string $cell, int|float $value, int $style = 0): string
    {
        return '<c r="' . $cell . '" s="' . $style . '"><v>' . $value . '</v></c>';
    }

    protected function dateToExcelSerial(DateTimeInterface $date): int
    {
        $epoch = new \DateTimeImmutable('1899-12-30', $date->getTimezone());
        return (int) floor(($date->getTimestamp() - $epoch->getTimestamp()) / 86400);
    }

    protected function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    protected function safeSheetName(): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]\:]/', ' ', $this->sheetName) ?: 'Rekap Publikasi';
        return mb_substr(trim($name), 0, 31) ?: 'Rekap Publikasi';
    }

    protected function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    protected function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($this->safeSheetName()) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    protected function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    protected function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd mmmm yyyy"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E2F3"/></left><right style="thin"><color rgb="FFD9E2F3"/></right><top style="thin"><color rgb="FFD9E2F3"/></top><bottom style="thin"><color rgb="FFD9E2F3"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    protected function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Microsoft Excel</Application>'
            . '</Properties>';
    }

    protected function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>MANTIK</dc:creator><cp:lastModifiedBy>MANTIK</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }
}
