<?php

namespace App\Support;

class AdminPublicationReportPdf
{
    protected const PAGE_WIDTH = 842.0;
    protected const PAGE_HEIGHT = 595.0;
    protected const MARGIN_X = 32.0;
    protected const TOP_Y = 552.0;
    protected const BOTTOM_Y = 32.0;
    protected const CONTENT_WIDTH = 778.0;

    protected array $pages = [];
    protected string $content = '';
    protected float $y = self::TOP_Y;
    protected int $pageNumber = 0;

    public static function make(array $report): string
    {
        return (new self())->output($report);
    }

    public function output(array $report): string
    {
        $this->addPage(true);
        $this->drawDocumentHeader($report);
        $this->drawIdentityTable($report);
        $this->drawProgressTable($report);
        $this->drawDecisionNotesTable($report);
        $this->finishPage();

        return $this->buildPdf();
    }

    protected function addPage(bool $firstPage = false): void
    {
        if ($this->content !== '') {
            $this->finishPage();
        }

        $this->pageNumber++;
        $this->content = '';
        $this->y = self::TOP_Y;

        $this->content .= $this->rect(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, false, true, 248, 251, 255);
        $this->content .= $this->rect(0, self::PAGE_HEIGHT - 8, self::PAGE_WIDTH, 8, false, true, 37, 99, 235);
        $this->content .= $this->rect(0, self::PAGE_HEIGHT - 8, 256, 8, false, true, 14, 165, 233);

        if (!$firstPage) {
            $this->content .= $this->text('Kartu Kendali Penyusunan Publikasi', self::MARGIN_X, 568, 10.5, true, 15, 23, 42);
            $this->content .= $this->text('Lanjutan progres kerja penyusunan publikasi', self::MARGIN_X, 553, 7.8, false, 100, 116, 139);
            $this->content .= $this->line(self::MARGIN_X, 540, self::PAGE_WIDTH - self::MARGIN_X, 540, 219, 234, 254, 0.8);
            $this->y = 520;
        }
    }

    protected function finishPage(): void
    {
        if ($this->content !== '') {
            $this->pages[] = $this->content;
            $this->content = '';
        }
    }

    protected function drawDocumentHeader(array $report): void
    {
        $publication = $report['publication'] ?? [];
        $region = (string) ($publication['region'] ?? '-');

        $x = self::MARGIN_X;
        $w = self::CONTENT_WIDTH;
        $top = $this->y;
        $h = 52;

        $this->content .= $this->rect($x, $top - $h, $w, $h, true, true, 255, 255, 255, 219, 234, 254);
        $this->content .= $this->rect($x, $top - $h, $w, 5, false, true, 37, 99, 235);
        $this->content .= $this->textCentered('KARTU KENDALI PENYUSUNAN PUBLIKASI', $x, $top - 20, $w, 13.0, true, 15, 23, 42);
        $this->content .= $this->textCentered('SISTEM MANAJEMEN PUBLIKASI STATISTIK', $x, $top - 35, $w, 8.2, false, 100, 116, 139);
        $this->content .= $this->text('Wilayah: ' . $region, $x + 16, $top - 45, 7.2, false, 100, 116, 139);
        $this->content .= $this->text('Dicetak: ' . now()->format('d-m-Y H:i'), $x + $w - 130, $top - 45, 7.2, false, 100, 116, 139);

        $this->y -= $h + 10;
    }

    protected function drawIdentityTable(array $report): void
    {
        $publication = $report['publication'] ?? [];
        $x = self::MARGIN_X;
        $w = self::CONTENT_WIDTH;
        $labelW = 140;
        $rowH = 21;
        $titleH = 22;
        $rows = [
            ['JUDUL PUBLIKASI', (string) ($publication['title'] ?? '-')],
            ['TIM KERJA', (string) ($publication['team'] ?? '-')],
            ['TANGGAL RILIS', (string) ($publication['release_date'] ?? '-')],
        ];

        $height = $titleH + ($rowH * count($rows));
        $this->ensureSpace($height + 16);
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $height, $w, $height, true, true, 255, 255, 255, 203, 213, 225);
        $this->content .= $this->rect($x, $top - $titleH, $w, $titleH, true, true, 226, 239, 255, 148, 163, 184);
        $this->content .= $this->textCentered('Identitas Publikasi', $x, $top - 14.5, $w, 9.8, true, 15, 23, 42);

        $currentTop = $top - $titleH;
        foreach ($rows as $row) {
            $this->content .= $this->rect($x, $currentTop - $rowH, $labelW, $rowH, true, true, 239, 246, 255, 203, 213, 225);
            $this->content .= $this->rect($x + $labelW, $currentTop - $rowH, $w - $labelW, $rowH, true, false, 255, 255, 255, 203, 213, 225);
            $this->content .= $this->text($row[0], $x + 10, $currentTop - 13.8, 8.0, true, 15, 23, 42);
            $this->drawTextBlock($row[1], $x + $labelW + 10, $currentTop - 13.8, $w - $labelW - 20, 8.2, false, 15, 23, 42, 9.8, 1);
            $currentTop -= $rowH;
        }

        $this->y -= $height + 12;
    }

    protected function drawProgressTable(array $report): void
    {
        $rows = $report['rows'] ?? [];
        $x = self::MARGIN_X;
        $widths = $this->tableWidths();

        if (empty($rows)) {
            $this->ensureSpace(70);
            $this->content .= $this->rect($x, $this->y - 54, self::CONTENT_WIDTH, 54, true, true, 255, 255, 255, 219, 234, 254);
            $this->content .= $this->text('Belum ada progres kerja yang tercatat pada publikasi ini.', $x + 18, $this->y - 30, 9.2, false, 100, 116, 139);
            $this->y -= 70;
            return;
        }

        $this->drawTableHeader();

        foreach ($rows as $index => $row) {
            $height = $this->progressRowHeight($row);
            if (($this->y - $height) < self::BOTTOM_Y) {
                $this->addPage(false);
                $this->drawTableHeader();
            }
            $this->drawProgressRow($index + 1, $row, $height, $index % 2 === 0);
        }
    }

    protected function drawTableHeader(): void
    {
        $x = self::MARGIN_X;
        $w = self::CONTENT_WIDTH;
        $widths = $this->tableWidths();
        $titleH = 24;
        $headerH = 36;

        $this->ensureSpace($titleH + $headerH + 30);
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $titleH, $w, $titleH, true, true, 226, 239, 255, 148, 163, 184);
        $this->content .= $this->textCentered('Progres Kerja Penyusunan Publikasi', $x, $top - 16, $w, 10.2, true, 15, 23, 42);
        $this->y -= $titleH;

        $top = $this->y;
        $cursor = $x;
        $dateX = $x + $widths['no'] + $widths['task'] + $widths['name'];
        $dateW = $widths['start'] + $widths['finish'];

        $this->drawHeaderCell($cursor, $top, $widths['no'], $headerH, 'No');
        $cursor += $widths['no'];
        $this->drawHeaderCell($cursor, $top, $widths['task'], $headerH, 'Uraian');
        $cursor += $widths['task'];
        $this->drawHeaderCell($cursor, $top, $widths['name'], $headerH, 'Nama');
        $cursor += $widths['name'];

        $this->content .= $this->rect($dateX, $top - 18, $dateW, 18, true, true, 219, 234, 254, 96, 165, 250);
        $this->content .= $this->textCentered('Tanggal/Bulan/Tahun', $dateX, $top - 12, $dateW, 7.5, true, 15, 23, 42);
        $this->drawHeaderCell($cursor, $top - 18, $widths['start'], 18, 'Mulai');
        $cursor += $widths['start'];
        $this->drawHeaderCell($cursor, $top - 18, $widths['finish'], 18, 'Selesai');
        $cursor += $widths['finish'];

        $this->drawHeaderCell($cursor, $top, $widths['result'], $headerH, 'Hasil');

        $this->y -= $headerH;
    }

    protected function drawHeaderCell(float $x, float $top, float $w, float $h, string $label): void
    {
        $this->content .= $this->rect($x, $top - $h, $w, $h, true, true, 219, 234, 254, 96, 165, 250);
        $lines = $this->wrap($label, $w - 10, 7.4, 2);
        $blockH = count($lines) * 8;
        $startY = $top - (($h - $blockH) / 2) - 6;
        foreach ($lines as $i => $line) {
            $this->content .= $this->textCentered($line, $x, $startY - ($i * 8), $w, 7.4, true, 15, 23, 42);
        }
    }

    protected function drawProgressRow(int $number, array $row, float $height, bool $alternate): void
    {
        $widths = $this->tableWidths();
        $x = self::MARGIN_X;
        $top = $this->y;
        $fill = $alternate ? [255, 255, 255] : [248, 251, 255];
        $cursor = $x;

        $cells = [
            ['key' => 'no', 'value' => (string) $number, 'align' => 'center', 'bold' => true, 'max' => 1],
            ['key' => 'task', 'value' => (string) ($row['task'] ?? '-'), 'align' => 'left', 'bold' => true, 'max' => 4],
            ['key' => 'name', 'value' => (string) ($row['names'] ?? '-'), 'align' => 'left', 'bold' => false, 'max' => 5],
            ['key' => 'start', 'value' => (string) ($row['start'] ?? '-'), 'align' => 'center', 'bold' => false, 'max' => 2],
            ['key' => 'finish', 'value' => (string) ($row['finish'] ?? '-'), 'align' => 'center', 'bold' => false, 'max' => 2],
            ['key' => 'result', 'value' => $this->normalizeResultLabel((string) ($row['result'] ?? '-')), 'align' => 'center', 'bold' => true, 'max' => 2],
        ];

        foreach ($cells as $cell) {
            $w = $widths[$cell['key']];
            $this->content .= $this->rect($cursor, $top - $height, $w, $height, true, true, $fill[0], $fill[1], $fill[2], 203, 213, 225);

            if ($cell['key'] === 'result') {
                $this->drawResultBadge($cell['value'], $cursor, $top, $w, $height);
            } else {
                $this->drawCellText($cell['value'], $cursor, $top, $w, $height, $cell['align'], $cell['bold'], (int) $cell['max']);
            }

            $cursor += $w;
        }

        $this->y -= $height;
    }

    protected function drawCellText(string $text, float $x, float $top, float $w, float $h, string $align = 'left', bool $bold = false, int $maxLines = 4): void
    {
        $size = $w < 46 ? 7.0 : 7.3;
        $lineHeight = 8.2;
        $lines = $this->wrap($text, $w - 12, $size, $maxLines);
        $blockHeight = count($lines) * $lineHeight;
        $startY = $top - max(10, (($h - $blockHeight) / 2) + 5.5);

        foreach ($lines as $i => $line) {
            $lineY = $startY - ($i * $lineHeight);
            if ($align === 'center') {
                $this->content .= $this->textCentered($line, $x + 4, $lineY, $w - 8, $size, $bold, 15, 23, 42);
            } else {
                $this->content .= $this->text($line, $x + 8, $lineY, $size, $bold, 15, 23, 42);
            }
        }
    }

    protected function drawResultBadge(string $result, float $x, float $top, float $w, float $h): void
    {
        $color = $this->resultColor($result);
        $badgeW = min($w - 14, 64);
        $badgeH = 16;
        $badgeX = $x + (($w - $badgeW) / 2);
        $badgeTop = $top - (($h - $badgeH) / 2);
        $this->content .= $this->rect($badgeX, $badgeTop - $badgeH, $badgeW, $badgeH, false, true, $color[0], $color[1], $color[2]);
        $this->content .= $this->textCentered($result, $badgeX, $badgeTop - 11.0, $badgeW, 7.0, true, 255, 255, 255);
    }

    protected function progressRowHeight(array $row): float
    {
        $widths = $this->tableWidths();
        $items = [
            ['text' => (string) ($row['task'] ?? '-'), 'width' => $widths['task'], 'size' => 7.3, 'line' => 8.2, 'max' => 4],
            ['text' => (string) ($row['names'] ?? '-'), 'width' => $widths['name'], 'size' => 7.3, 'line' => 8.2, 'max' => 5],
            ['text' => (string) ($row['start'] ?? '-'), 'width' => $widths['start'], 'size' => 7.0, 'line' => 8.2, 'max' => 2],
            ['text' => (string) ($row['finish'] ?? '-'), 'width' => $widths['finish'], 'size' => 7.0, 'line' => 8.2, 'max' => 2],
            ['text' => (string) ($row['result'] ?? '-'), 'width' => $widths['result'], 'size' => 7.0, 'line' => 8.2, 'max' => 2],
        ];

        $max = 0;
        foreach ($items as $item) {
            $max = max($max, count($this->wrap($item['text'], $item['width'] - 12, $item['size'], $item['max'])) * $item['line']);
        }

        return max(36, $max + 13);
    }

    protected function normalizeResultLabel(string $result): string
    {
        $clean = strtolower($this->sanitize($result));
        if ($clean === '-' || $clean === '') {
            return '-';
        }
        if (str_contains($clean, 'selesai')) {
            return 'Selesai';
        }
        if (str_contains($clean, 'revisi')) {
            return 'Revisi';
        }
        return 'Disetujui';
    }

    protected function resultColor(string $result): array
    {
        $result = strtolower($this->sanitize($result));
        if (str_contains($result, 'revisi')) {
            return [245, 158, 11];
        }
        if (str_contains($result, 'selesai')) {
            return [37, 99, 235];
        }
        if ($result === '-') {
            return [100, 116, 139];
        }
        return [16, 185, 129];
    }

    protected function tableWidths(): array
    {
        return [
            'no' => 34.0,
            'task' => 186.0,
            'name' => 186.0,
            'start' => 98.0,
            'finish' => 98.0,
            'result' => 176.0,
        ];
    }

    protected function decisionNoteWidths(): array
    {
        return [
            'reviewer' => 170.0,
            'note' => 450.0,
            'result' => 158.0,
        ];
    }

    protected function drawDecisionNotesTable(array $report): void
    {
        $rows = $report['decision_notes'] ?? [];
        if (empty($rows)) {
            $rows = [[
                'reviewer' => 'Semua Pemeriksa',
                'note' => 'Tidak terdapat catatan revisi akhir pada publikasi ini.',
                'result' => 'Disetujui',
            ]];
        }

        $this->drawDecisionNoteHeader();

        foreach ($rows as $index => $row) {
            $height = $this->decisionRowHeight($row);
            if (($this->y - $height) < self::BOTTOM_Y) {
                $this->addPage(false);
                $this->drawDecisionNoteHeader(true);
            }
            $this->drawDecisionNoteRow($row, $height, $index % 2 === 0);
        }
    }

    protected function drawDecisionNoteHeader(bool $continued = false): void
    {
        $x = self::MARGIN_X;
        $w = self::CONTENT_WIDTH;
        $widths = $this->decisionNoteWidths();
        $titleH = 24;
        $headerH = 32;

        $this->ensureSpace($titleH + $headerH + 36);
        $top = $this->y - 12;

        $this->content .= $this->rect($x, $top - $titleH, $w, $titleH, true, true, 226, 239, 255, 148, 163, 184);
        $title = $continued ? 'Catatan Hasil Pemeriksaan (Lanjutan)' : 'Catatan Hasil Pemeriksaan';
        $this->content .= $this->textCentered($title, $x, $top - 16, $w, 10.2, true, 15, 23, 42);
        $this->y = $top - $titleH;

        $top = $this->y;
        $cursor = $x;
        $this->drawHeaderCell($cursor, $top, $widths['reviewer'], $headerH, 'Pemeriksa');
        $cursor += $widths['reviewer'];
        $this->drawHeaderCell($cursor, $top, $widths['note'], $headerH, 'Catatan Keputusan');
        $cursor += $widths['note'];
        $this->drawHeaderCell($cursor, $top, $widths['result'], $headerH, 'Review Hasil Revisi');
        $this->y -= $headerH;
    }

    protected function drawDecisionNoteRow(array $row, float $height, bool $alternate): void
    {
        $widths = $this->decisionNoteWidths();
        $x = self::MARGIN_X;
        $top = $this->y;
        $fill = $alternate ? [255, 255, 255] : [248, 251, 255];
        $cursor = $x;

        $cells = [
            ['key' => 'reviewer', 'value' => (string) ($row['reviewer'] ?? '-'), 'align' => 'left', 'bold' => true, 'max' => 4],
            ['key' => 'note', 'value' => (string) ($row['note'] ?? '-'), 'align' => 'left', 'bold' => false, 'max' => 8],
            ['key' => 'result', 'value' => (string) ($row['result'] ?? 'Disetujui'), 'align' => 'center', 'bold' => true, 'max' => 3],
        ];

        foreach ($cells as $cell) {
            $w = $widths[$cell['key']];
            $this->content .= $this->rect($cursor, $top - $height, $w, $height, true, true, $fill[0], $fill[1], $fill[2], 203, 213, 225);
            $this->drawCellText($cell['value'], $cursor, $top, $w, $height, $cell['align'], $cell['bold'], (int) $cell['max']);
            $cursor += $w;
        }

        $this->y -= $height;
    }

    protected function decisionRowHeight(array $row): float
    {
        $widths = $this->decisionNoteWidths();
        $items = [
            ['text' => (string) ($row['reviewer'] ?? '-'), 'width' => $widths['reviewer'], 'size' => 7.3, 'line' => 8.2, 'max' => 4],
            ['text' => (string) ($row['note'] ?? '-'), 'width' => $widths['note'], 'size' => 7.3, 'line' => 8.2, 'max' => 8],
            ['text' => (string) ($row['result'] ?? '-'), 'width' => $widths['result'], 'size' => 7.3, 'line' => 8.2, 'max' => 3],
        ];

        $max = 0;
        foreach ($items as $item) {
            $max = max($max, count($this->wrap($item['text'], $item['width'] - 12, $item['size'], $item['max'])) * $item['line']);
        }

        return max(48, $max + 18);
    }

    protected function ensureSpace(float $height): void
    {
        if (($this->y - $height) < self::BOTTOM_Y) {
            $this->addPage(false);
        }
    }

    protected function drawTextBlock(
        string $text,
        float $x,
        float $top,
        float $width,
        float $size = 9,
        bool $bold = false,
        int $r = 15,
        int $g = 23,
        int $b = 42,
        float $lineHeight = 11,
        ?int $maxLines = null
    ): float {
        $lines = $this->wrap($text, $width, $size, $maxLines);
        foreach ($lines as $index => $line) {
            $this->content .= $this->text($line, $x, $top - ($index * $lineHeight), $size, $bold, $r, $g, $b);
        }

        return count($lines) * $lineHeight;
    }

    protected function wrap(string $text, float $width, float $size = 9, ?int $maxLines = null): array
    {
        $text = trim($this->sanitize($text));
        if ($text === '') {
            return ['-'];
        }

        $limit = max(8, (int) floor($width / max(3.8, $size * 0.52)));
        $paragraphs = preg_split('/\r\n|\r|\n/', $text) ?: [$text];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph) ?? '');
            if ($paragraph === '') {
                continue;
            }

            foreach (explode("\n", wordwrap($paragraph, $limit, "\n", true)) as $line) {
                $lines[] = $line;
                if ($maxLines !== null && count($lines) >= $maxLines) {
                    break 2;
                }
            }
        }

        if (empty($lines)) {
            $lines = ['-'];
        }

        if ($maxLines !== null && count($lines) >= $maxLines) {
            $originalLineCount = 0;
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph) ?? '');
                if ($paragraph !== '') {
                    $originalLineCount += count(explode("\n", wordwrap($paragraph, $limit, "\n", true)));
                }
            }

            if ($originalLineCount > $maxLines) {
                $last = $lines[$maxLines - 1] ?? '';
                $lines[$maxLines - 1] = rtrim(substr($last, 0, max(0, $limit - 3))) . '...';
            }
        }

        return $lines;
    }

    protected function buildPdf(): string
    {
        $objects = [];
        $pageKids = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = null;

        $pageCount = count($this->pages);
        $nextObject = 3;

        foreach ($this->pages as $index => $stream) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageKids[] = $pageObject . ' 0 R';
            $streamWithFooter = $stream . $this->footerStream($index + 1, $pageCount);

            $objects[$pageObject - 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> /Contents ' . $contentObject . ' 0 R >>';
            $objects[$contentObject - 1] = '<< /Length ' . strlen($streamWithFooter) . " >>\nstream\n" . $streamWithFooter . "\nendstream";
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageKids) . '] /Count ' . $pageCount . ' >>';
        ksort($objects);
        $objects = array_values($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    protected function footerStream(int $page, int $pageCount): string
    {
        $content = '';
        $content .= $this->line(self::MARGIN_X, 30, self::PAGE_WIDTH - self::MARGIN_X, 30, 226, 232, 240, 0.7);
        $content .= $this->text('Dokumen ini dihasilkan otomatis dari Manajemen Publikasi Statistik.', self::MARGIN_X, 17, 7.0, false, 100, 116, 139);
        $content .= $this->text('Halaman ' . $page . ' dari ' . $pageCount, self::PAGE_WIDTH - 100, 17, 7.0, false, 100, 116, 139);
        return $content;
    }

    protected function rect(float $x, float $y, float $w, float $h, bool $stroke = true, bool $fill = false, int $fr = 255, int $fg = 255, int $fb = 255, int $sr = 203, int $sg = 213, int $sb = 225): string
    {
        $content = "q\n";
        if ($fill) {
            $content .= $this->fillColor($fr, $fg, $fb);
        }
        if ($stroke) {
            $content .= $this->strokeColor($sr, $sg, $sb);
            $content .= "0.8 w\n";
        }
        $content .= $this->num($x) . ' ' . $this->num($y) . ' ' . $this->num($w) . ' ' . $this->num($h) . " re\n";
        $content .= $fill && $stroke ? "B\n" : ($fill ? "f\n" : "S\n");
        $content .= "Q\n";

        return $content;
    }

    protected function line(float $x1, float $y1, float $x2, float $y2, int $r, int $g, int $b, float $width = 0.8): string
    {
        return "q\n" . $this->strokeColor($r, $g, $b) . $this->num($width) . " w\n" . $this->num($x1) . ' ' . $this->num($y1) . ' m ' . $this->num($x2) . ' ' . $this->num($y2) . " l S\nQ\n";
    }

    protected function text(string $text, float $x, float $y, float $size = 9, bool $bold = false, int $r = 15, int $g = 23, int $b = 42): string
    {
        $font = $bold ? 'F2' : 'F1';
        return "BT\n" . $this->fillColor($r, $g, $b) . '/' . $font . ' ' . $this->num($size) . " Tf\n" . $this->num($x) . ' ' . $this->num($y) . ' Td (' . $this->escape($this->sanitize($text)) . ") Tj\nET\n";
    }

    protected function textCentered(string $text, float $x, float $y, float $width, float $size = 9, bool $bold = false, int $r = 15, int $g = 23, int $b = 42): string
    {
        $textWidth = strlen($this->sanitize($text)) * $size * 0.52;
        $textX = $x + (($width - $textWidth) / 2);
        return $this->text($text, $textX, $y, $size, $bold, $r, $g, $b);
    }

    protected function fillColor(int $r, int $g, int $b): string
    {
        return $this->num($r / 255) . ' ' . $this->num($g / 255) . ' ' . $this->num($b / 255) . " rg\n";
    }

    protected function strokeColor(int $r, int $g, int $b): string
    {
        return $this->num($r / 255) . ' ' . $this->num($g / 255) . ' ' . $this->num($b / 255) . " RG\n";
    }

    protected function sanitize(string $text): string
    {
        $replace = [
            '–' => '-', '—' => '-', '“' => '"', '”' => '"', '‘' => "'", '’' => "'",
            '•' => '-', '…' => '...', '≥' => '>=', '≤' => '<=', '×' => 'x',
        ];

        $text = strtr($text, $replace);
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
    }

    protected function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    protected function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
