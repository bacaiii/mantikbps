<?php

namespace App\Support;

class RevisionInspectionPdf
{
    protected const PAGE_WIDTH = 595.0;
    protected const PAGE_HEIGHT = 842.0;
    protected const MARGIN_X = 42.0;
    protected const TOP_Y = 774.0;
    protected const BOTTOM_Y = 62.0;
    protected const CONTENT_WIDTH = 511.0;

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
        $this->drawIdentity($report);
        $this->drawSummary($report);
        $this->drawFinalNote($report);
        $this->drawRevisionDetails($report);
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
        $this->content .= $this->rect(0, self::PAGE_HEIGHT - 8, 184, 8, false, true, 14, 165, 233);

        if ($firstPage) {
            $this->content .= $this->text('DETAIL REVISI PEMERIKSAAN', self::MARGIN_X, 802, 15.5, true, 15, 23, 42);
            $this->content .= $this->text('Manajemen Publikasi Statistik', self::MARGIN_X, 784, 8.7, false, 100, 116, 139);
            $this->content .= $this->badge('DOKUMEN PDF', 454, 790, 92, 22, 37, 99, 235, 255, 255, 255);
            $this->content .= $this->line(self::MARGIN_X, 770, 553, 770, 219, 234, 254, 1.0);
            $this->y = 748;
        } else {
            $this->content .= $this->text('Detail Revisi Pemeriksaan', self::MARGIN_X, 805, 11, true, 30, 41, 59);
            $this->content .= $this->text('Lanjutan rincian revisi', self::MARGIN_X, 790, 8, false, 100, 116, 139);
            $this->content .= $this->line(self::MARGIN_X, 778, 553, 778, 219, 234, 254, 1.0);
            $this->y = 754;
        }
    }

    protected function finishPage(): void
    {
        $this->pages[] = $this->content;
        $this->content = '';
    }

    protected function drawIdentity(array $report): void
    {
        $publication = $report['publication'] ?? [];
        $review = $report['review'] ?? [];

        $this->sectionTitle('Identitas Pemeriksaan');

        $titleHeight = max(52, $this->textBlockHeight($publication['title'] ?? '-', 360, 9.3, 11.2) + 25);
        $this->ensureSpace($titleHeight + 8);
        $x = self::MARGIN_X;
        $w = self::CONTENT_WIDTH;
        $top = $this->y;
        $this->content .= $this->rect($x, $top - $titleHeight, $w, $titleHeight, true, true, 255, 255, 255, 219, 234, 254);
        $this->content .= $this->rect($x, $top - $titleHeight, 5, $titleHeight, false, true, 37, 99, 235);
        $this->content .= $this->text('Nama Publikasi', $x + 16, $top - 18, 7.6, false, 100, 116, 139);
        $this->drawTextBlock($publication['title'] ?? '-', $x + 16, $top - 34, $w - 32, 9.3, true, 15, 23, 42, 11.2);
        $this->y -= $titleHeight + 10;

        $items = [
            ['Tim Kerja', $publication['team'] ?? '-'],
            ['Wilayah', $publication['region'] ?? '-'],
            ['Jenis Pemeriksaan', $review['type_label'] ?? '-'],
            ['Versi Draft', $review['draft_label'] ?? '-'],
            ['Pemeriksa', $review['reviewer'] ?? '-'],
            ['Tanggal Pemeriksaan', $review['reviewed_at'] ?? '-'],
        ];

        $colGap = 10;
        $colW = (self::CONTENT_WIDTH - $colGap) / 2;
        $cellH = 42;

        foreach (array_chunk($items, 2) as $pair) {
            $this->ensureSpace($cellH + 9);
            foreach ($pair as $index => $item) {
                $cellX = self::MARGIN_X + ($index * ($colW + $colGap));
                $this->drawInfoCell($cellX, $this->y, $colW, $cellH, $item[0], $item[1]);
            }
            $this->y -= $cellH + 9;
        }
    }

    protected function drawSummary(array $report): void
    {
        $review = $report['review'] ?? [];
        $summary = $report['summary'] ?? [];
        $this->sectionTitle('Ringkasan Revisi');

        $cards = [
            ['Total Slide', (string) ($summary['slide_count'] ?? '0'), 'slide'],
            ['Rincian Revisi', (string) ($summary['failed_count'] ?? '0'), 'item'],
            ['Status', $review['result_label'] ?? 'Revisi', 'hasil'],
            ['Peran Pemeriksa', $review['reviewer_role'] ?? 'Pemeriksa', 'peran'],
        ];

        $gap = 9;
        $w = (self::CONTENT_WIDTH - ($gap * 3)) / 4;
        $h = 58;
        $this->ensureSpace($h + 10);

        foreach ($cards as $index => $card) {
            $x = self::MARGIN_X + ($index * ($w + $gap));
            $this->content .= $this->rect($x, $this->y - $h, $w, $h, true, true, 255, 255, 255, 219, 234, 254);
            $this->content .= $this->rect($x, $this->y - $h, $w, 4, false, true, $index === 2 ? 239 : 37, $index === 2 ? 68 : 99, $index === 2 ? 68 : 235);
            $this->content .= $this->text($card[0], $x + 10, $this->y - 18, 7.5, false, 100, 116, 139);
            $this->drawTextBlock($card[1], $x + 10, $this->y - 37, $w - 20, 11, true, 15, 23, 42, 12.5, 2);
        }

        $this->y -= $h + 11;
    }

    protected function drawFinalNote(array $report): void
    {
        $review = $report['review'] ?? [];
        $note = $review['final_notes'] ?? '-';
        $height = max(54, $this->textBlockHeight($note, self::CONTENT_WIDTH - 32, 8.5, 10.5) + 32);

        $this->ensureSpace($height + 12);
        $x = self::MARGIN_X;
        $top = $this->y;
        $this->content .= $this->rect($x, $top - $height, self::CONTENT_WIDTH, $height, true, true, 239, 246, 255, 191, 219, 254);
        $this->content .= $this->text('Catatan Keputusan Akhir', $x + 16, $top - 17, 8.4, true, 30, 64, 175);
        $this->drawTextBlock($note ?: '-', $x + 16, $top - 34, self::CONTENT_WIDTH - 32, 8.5, false, 51, 65, 85, 10.5);
        $this->y -= $height + 16;
    }

    protected function drawRevisionDetails(array $report): void
    {
        $slides = $report['slides'] ?? [];
        $this->sectionTitle('Daftar Rincian Revisi');

        if (empty($slides)) {
            $this->ensureSpace(54);
            $this->content .= $this->rect(self::MARGIN_X, $this->y - 44, self::CONTENT_WIDTH, 44, true, true, 255, 255, 255, 219, 234, 254);
            $this->content .= $this->text('Tidak ada rincian revisi yang tercatat.', self::MARGIN_X + 16, $this->y - 26, 9, false, 100, 116, 139);
            $this->y -= 54;
            return;
        }

        foreach ($slides as $slideIndex => $slide) {
            $this->drawSlideHeader($slideIndex + 1, $slide);

            $failedItems = $slide['failed_items'] ?? [];
            if (empty($failedItems)) {
                $this->drawRevisionItem($slideIndex + 1, 1, 'Tidak ada rincian revisi yang tercatat.');
                continue;
            }

            foreach ($failedItems as $itemIndex => $failedItem) {
                $detail = is_array($failedItem)
                    ? (string) ($failedItem['requirement_detail'] ?? '-')
                    : (string) $failedItem;
                $this->drawRevisionItem($slideIndex + 1, $itemIndex + 1, $detail);
            }
        }
    }

    protected function drawSlideHeader(int $number, array $slide): void
    {
        $note = $slide['notes'] ?? 'Tidak ada catatan tambahan.';
        $noteHeight = $this->textBlockHeight($note ?: 'Tidak ada catatan tambahan.', self::CONTENT_WIDTH - 32, 8, 10, 3);
        $h = max(76, 58 + $noteHeight);

        $this->ensureSpace($h + 12);
        $x = self::MARGIN_X;
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $h, self::CONTENT_WIDTH, $h, true, true, 255, 255, 255, 219, 234, 254);
        $this->content .= $this->rect($x, $top - $h, 6, $h, false, true, 14, 165, 233);
        $this->content .= $this->badge('SLIDE ' . $number, $x + 16, $top - 24, 62, 18, 14, 165, 233, 255, 255, 255);
        $this->content .= $this->text($slide['anatomy_section'] ?? '-', $x + 92, $top - 19, 10, true, 15, 23, 42);
        $this->content .= $this->text('Sub-anatomi: ' . ($slide['sub_anatomy'] ?? '-'), $x + 92, $top - 34, 8, false, 100, 116, 139);
        $this->content .= $this->text(($slide['reviewer_role'] ?? 'Pemeriksa') . ': ' . ($slide['reviewer_name'] ?? '-'), $x + 92, $top - 48, 8, false, 71, 85, 105);
        $this->content .= $this->line($x + 16, $top - 58, $x + self::CONTENT_WIDTH - 16, $top - 58, 226, 232, 240, 0.7);
        $this->content .= $this->text('Catatan slide', $x + 16, $top - 72, 7.5, true, 100, 116, 139);
        $this->drawTextBlock($note ?: 'Tidak ada catatan tambahan.', $x + 92, $top - 72, self::CONTENT_WIDTH - 108, 8, false, 51, 65, 85, 10, 3);

        $this->y -= $h + 8;
    }

    protected function drawRevisionItem(int $slideNumber, int $itemNumber, string $detail): void
    {
        $detail = $detail ?: '-';
        $linesHeight = $this->textBlockHeight($detail, self::CONTENT_WIDTH - 58, 8.4, 10.4);
        $h = max(56, $linesHeight + 32);

        if ($h > 610) {
            $h = 610;
        }

        $this->ensureSpace($h + 10);
        $x = self::MARGIN_X + 14;
        $w = self::CONTENT_WIDTH - 14;
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $h, $w, $h, true, true, 255, 255, 255, 226, 232, 240);
        $this->content .= $this->circle($x + 18, $top - 22, 7, 37, 99, 235);
        $this->content .= $this->text((string) $itemNumber, $x + 15.4, $top - 25, 7, true, 255, 255, 255);
        $this->content .= $this->text('Rincian revisi ' . $slideNumber . '.' . $itemNumber, $x + 34, $top - 21, 8.6, true, 30, 41, 59);
        $this->drawTextBlock($detail, $x + 34, $top - 38, $w - 48, 8.4, false, 51, 65, 85, 10.4);

        $this->y -= $h + 8;
    }

    protected function drawInfoCell(float $x, float $top, float $w, float $h, string $label, string $value): void
    {
        $this->content .= $this->rect($x, $top - $h, $w, $h, true, true, 255, 255, 255, 219, 234, 254);
        $this->content .= $this->text($label, $x + 10, $top - 15, 7.2, false, 100, 116, 139);
        $this->drawTextBlock($value ?: '-', $x + 10, $top - 31, $w - 20, 8.8, true, 15, 23, 42, 10.8, 2);
    }

    protected function sectionTitle(string $title): void
    {
        $this->ensureSpace(34);
        $this->content .= $this->text($title, self::MARGIN_X, $this->y - 4, 10.8, true, 15, 23, 42);
        $this->content .= $this->line(self::MARGIN_X, $this->y - 13, 553, $this->y - 13, 219, 234, 254, 0.9);
        $this->y -= 28;
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
            $offsetX = str_starts_with(ltrim($line), '-') ? 7 : 0;
            $this->content .= $this->text($line, $x + $offsetX, $top - ($index * $lineHeight), $size, $bold, $r, $g, $b);
        }

        return count($lines) * $lineHeight;
    }

    protected function textBlockHeight(string $text, float $width, float $size = 9, float $lineHeight = 11, ?int $maxLines = null): float
    {
        return count($this->wrap($text, $width, $size, $maxLines)) * $lineHeight;
    }

    protected function wrap(string $text, float $width, float $size = 9, ?int $maxLines = null): array
    {
        $text = trim($this->sanitize($text));
        if ($text === '') {
            return ['-'];
        }

        $limit = max(12, (int) floor($width / max(4.2, $size * 0.53)));
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
        $pageObjects = [];
        $contentObjects = [];
        $pageKids = [];
        $objects = [];

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = null;

        $pageCount = count($this->pages);
        $nextObject = 3;

        foreach ($this->pages as $index => $stream) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageObjects[] = $pageObject;
            $contentObjects[] = $contentObject;
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
        $content .= $this->line(self::MARGIN_X, 43, 553, 43, 226, 232, 240, 0.7);
        $content .= $this->text('Dokumen ini dihasilkan otomatis dari Manajemen Publikasi Statistik.', self::MARGIN_X, 28, 7.2, false, 100, 116, 139);
        $content .= $this->text('Halaman ' . $page . ' dari ' . $pageCount, 500, 28, 7.2, false, 100, 116, 139);
        $content .= $this->text('Dicetak: ' . now()->format('d-m-Y H:i'), self::MARGIN_X, 17, 7.2, false, 148, 163, 184);
        return $content;
    }

    protected function badge(string $text, float $x, float $top, float $w, float $h, int $br, int $bg, int $bb, int $tr, int $tg, int $tb): string
    {
        $content = $this->rect($x, $top - $h, $w, $h, false, true, $br, $bg, $bb);
        $textWidth = strlen($this->sanitize($text)) * 4.2;
        $content .= $this->text($text, $x + (($w - $textWidth) / 2), $top - 14, 7.5, true, $tr, $tg, $tb);
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

    protected function circle(float $x, float $y, float $radius, int $r, int $g, int $b): string
    {
        $c = 0.5522847498 * $radius;
        $content = "q\n" . $this->fillColor($r, $g, $b);
        $content .= $this->num($x + $radius) . ' ' . $this->num($y) . " m\n";
        $content .= $this->num($x + $radius) . ' ' . $this->num($y + $c) . ' ' . $this->num($x + $c) . ' ' . $this->num($y + $radius) . ' ' . $this->num($x) . ' ' . $this->num($y + $radius) . " c\n";
        $content .= $this->num($x - $c) . ' ' . $this->num($y + $radius) . ' ' . $this->num($x - $radius) . ' ' . $this->num($y + $c) . ' ' . $this->num($x - $radius) . ' ' . $this->num($y) . " c\n";
        $content .= $this->num($x - $radius) . ' ' . $this->num($y - $c) . ' ' . $this->num($x - $c) . ' ' . $this->num($y - $radius) . ' ' . $this->num($x) . ' ' . $this->num($y - $radius) . " c\n";
        $content .= $this->num($x + $c) . ' ' . $this->num($y - $radius) . ' ' . $this->num($x + $radius) . ' ' . $this->num($y - $c) . ' ' . $this->num($x + $radius) . ' ' . $this->num($y) . " c\nf\nQ\n";
        return $content;
    }

    protected function text(string $text, float $x, float $y, float $size = 9, bool $bold = false, int $r = 15, int $g = 23, int $b = 42): string
    {
        $font = $bold ? 'F2' : 'F1';
        return "BT\n" . $this->fillColor($r, $g, $b) . '/' . $font . ' ' . $this->num($size) . " Tf\n" . $this->num($x) . ' ' . $this->num($y) . ' Td (' . $this->escape($this->sanitize($text)) . ") Tj\nET\n";
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
