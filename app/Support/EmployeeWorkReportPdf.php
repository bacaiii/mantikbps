<?php

namespace App\Support;

class EmployeeWorkReportPdf
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
        $this->drawHero($report);
        $this->drawIdentity($report);
        $this->drawSummary($report);
        $this->drawActivities($report);
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
            $this->content .= $this->text('REKAP HASIL KERJA PEGAWAI', self::MARGIN_X, 802, 15.5, true, 15, 23, 42);
            $this->content .= $this->text('Manajemen Publikasi Statistik', self::MARGIN_X, 784, 8.7, false, 100, 116, 139);
            $this->content .= $this->badge('DOKUMEN PDF', 454, 790, 92, 22, 37, 99, 235, 255, 255, 255);
            $this->content .= $this->line(self::MARGIN_X, 770, 553, 770, 219, 234, 254, 1.0);
            $this->y = 748;
            return;
        }

        $this->content .= $this->text('Rekap Hasil Kerja Pegawai', self::MARGIN_X, 805, 11, true, 30, 41, 59);
        $this->content .= $this->text('Lanjutan rincian aktivitas kerja', self::MARGIN_X, 790, 8, false, 100, 116, 139);
        $this->content .= $this->line(self::MARGIN_X, 778, 553, 778, 219, 234, 254, 1.0);
        $this->y = 754;
    }

    protected function finishPage(): void
    {
        if ($this->content !== '') {
            $this->pages[] = $this->content;
            $this->content = '';
        }
    }

    protected function drawHero(array $report): void
    {
        $publication = $report['publication'] ?? [];
        $employee = $report['employee'] ?? [];
        $title = (string) ($publication['title'] ?? '-');
        $height = max(86, $this->textBlockHeight($title, self::CONTENT_WIDTH - 34, 12, 14.2, 3) + 48);

        $this->ensureSpace($height + 14);
        $x = self::MARGIN_X;
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $height, self::CONTENT_WIDTH, $height, true, true, 255, 255, 255, 219, 234, 254);
        $this->content .= $this->rect($x, $top - $height, 6, $height, false, true, 37, 99, 235);
        $this->content .= $this->text('Publikasi Siap Rilis', $x + 18, $top - 18, 8, false, 100, 116, 139);
        $this->drawTextBlock($title, $x + 18, $top - 39, self::CONTENT_WIDTH - 34, 12, true, 15, 23, 42, 14.2, 3);
        $this->content .= $this->line($x + 18, $top - $height + 27, $x + self::CONTENT_WIDTH - 18, $top - $height + 27, 226, 232, 240, 0.7);
        $this->content .= $this->text('Pegawai: ' . ($employee['name'] ?? '-'), $x + 18, $top - $height + 12, 8, false, 71, 85, 105);
        $this->content .= $this->text('Dicetak: ' . now()->format('d-m-Y H:i'), 438, $top - $height + 12, 8, false, 100, 116, 139);

        $this->y -= $height + 15;
    }

    protected function drawIdentity(array $report): void
    {
        $employee = $report['employee'] ?? [];
        $publication = $report['publication'] ?? [];

        $this->sectionTitle('Identitas dan Informasi Publikasi');

        $items = [
            ['Nama Pegawai', $employee['name'] ?? '-'],
            ['Email', $employee['email'] ?? '-'],
            ['Wilayah Pegawai', $employee['region'] ?? '-'],
            ['Peran Pegawai', $employee['roles'] ?? '-'],
            ['Tim Kerja', $publication['team'] ?? '-'],
            ['Wilayah Publikasi', $publication['region'] ?? '-'],
            ['Kategori / Periode', ($publication['category'] ?? '-') . ' / ' . ($publication['period'] ?? '-')],
            ['Akurasi / Status', ($publication['accuracy'] ?? '-') . ' / ' . ($publication['status'] ?? '-')],
            ['Jadwal Rilis', $publication['release_date'] ?? '-'],
            ['Tahun Publikasi', $publication['year'] ?? '-'],
        ];

        $colGap = 10;
        $colW = (self::CONTENT_WIDTH - $colGap) / 2;
        $cellH = 42;

        foreach (array_chunk($items, 2) as $pair) {
            $this->ensureSpace($cellH + 9);
            foreach ($pair as $index => $item) {
                $cellX = self::MARGIN_X + ($index * ($colW + $colGap));
                $this->drawInfoCell($cellX, $this->y, $colW, $cellH, $item[0], (string) $item[1]);
            }
            $this->y -= $cellH + 9;
        }
    }

    protected function drawSummary(array $report): void
    {
        $summary = $report['summary'] ?? [];
        $this->sectionTitle('Ringkasan Aktivitas');

        $cards = [
            ['Total Aktivitas', (string) ($summary['total_activities'] ?? '0'), 'catatan'],
            ['Dokumen Diunggah', (string) ($summary['uploaded_documents'] ?? '0'), 'dokumen'],
            ['Review Dilakukan', (string) ($summary['review_count'] ?? '0'), 'review'],
            ['Penugasan', (string) ($summary['assignment_count'] ?? '0'), 'peran'],
        ];

        $gap = 9;
        $w = (self::CONTENT_WIDTH - ($gap * 3)) / 4;
        $h = 58;
        $this->ensureSpace($h + 10);

        foreach ($cards as $index => $card) {
            $x = self::MARGIN_X + ($index * ($w + $gap));
            $this->content .= $this->rect($x, $this->y - $h, $w, $h, true, true, 255, 255, 255, 219, 234, 254);
            $this->content .= $this->rect($x, $this->y - $h, $w, 4, false, true, $index === 0 ? 37 : 14, $index === 0 ? 99 : 165, $index === 0 ? 235 : 233);
            $this->content .= $this->text($card[0], $x + 10, $this->y - 18, 7.5, false, 100, 116, 139);
            $this->drawTextBlock($card[1], $x + 10, $this->y - 39, $w - 20, 14, true, 15, 23, 42, 14, 1);
        }

        $this->y -= $h + 14;
    }

    protected function drawActivities(array $report): void
    {
        $activities = $report['activities'] ?? [];
        $this->sectionTitle('Rincian Aktivitas Kerja');

        if (empty($activities)) {
            $this->ensureSpace(54);
            $this->content .= $this->rect(self::MARGIN_X, $this->y - 44, self::CONTENT_WIDTH, 44, true, true, 255, 255, 255, 219, 234, 254);
            $this->content .= $this->text('Belum ada aktivitas kerja yang tercatat pada publikasi ini.', self::MARGIN_X + 16, $this->y - 26, 9, false, 100, 116, 139);
            $this->y -= 54;
            return;
        }

        foreach ($activities as $index => $activity) {
            $this->drawActivityCard($index + 1, $activity);
        }
    }

    protected function drawActivityCard(int $number, array $activity): void
    {
        $title = (string) ($activity['aktivitas'] ?? '-');
        $note = (string) ($activity['keterangan'] ?? '-');
        $date = (string) ($activity['tanggal'] ?? '-');

        $titleHeight = $this->textBlockHeight($title, self::CONTENT_WIDTH - 96, 9.2, 11.2, 2);
        $noteHeight = $this->textBlockHeight($note, self::CONTENT_WIDTH - 96, 8.1, 10, 4);
        $h = max(70, 42 + $titleHeight + $noteHeight);

        $this->ensureSpace($h + 10);
        $x = self::MARGIN_X;
        $top = $this->y;

        $this->content .= $this->rect($x, $top - $h, self::CONTENT_WIDTH, $h, true, true, 255, 255, 255, 226, 232, 240);
        $this->content .= $this->circle($x + 22, $top - 24, 9, 37, 99, 235);
        $this->content .= $this->text((string) $number, $x + ($number < 10 ? 19.3 : 16.7), $top - 27.5, 7.5, true, 255, 255, 255);
        $this->content .= $this->badge($date, $x + self::CONTENT_WIDTH - 116, $top - 13, 100, 18, 239, 246, 255, 30, 64, 175);
        $this->drawTextBlock($title, $x + 46, $top - 20, self::CONTENT_WIDTH - 172, 9.2, true, 15, 23, 42, 11.2, 2);
        $this->content .= $this->text('Keterangan', $x + 46, $top - 43 - $titleHeight, 7.3, true, 100, 116, 139);
        $this->drawTextBlock($note ?: '-', $x + 46, $top - 58 - $titleHeight, self::CONTENT_WIDTH - 96, 8.1, false, 51, 65, 85, 10, 4);

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
            $this->content .= $this->text($line, $x, $top - ($index * $lineHeight), $size, $bold, $r, $g, $b);
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
