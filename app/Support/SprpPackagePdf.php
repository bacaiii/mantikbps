<?php

namespace App\Support;

use App\Models\PublicationSprp;

class SprpPackagePdf
{
    protected const PAGE_WIDTH = 595;
    protected const PAGE_HEIGHT = 842;

    protected array $objects = [];

    public static function make(PublicationSprp $sprp): string
    {
        return (new self())->output($sprp);
    }

    public function output(PublicationSprp $sprp): string
    {
        $stream = $this->pageStream($sprp);

        $this->objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> /Contents 4 0 R >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
        ];

        return $this->buildPdf();
    }

    protected function pageStream(PublicationSprp $sprp): string
    {
        $rows = $this->rows($sprp);
        $content = '';

        $content .= $this->fillColor(248, 251, 255);
        $content .= $this->rect(32, 36, 531, 770, true, true, 220, 230, 245);

        $content .= $this->text('FORM SPRP', 56, 766, 16, true, 15, 43, 102);
        $content .= $this->text('Surat Permintaan/Pengesahan Rancangan Publikasi', 56, 748, 9, false, 100, 116, 139);
        $content .= $this->line(56, 734, 539, 734, 220, 230, 245);

        $content .= $this->text('Identitas Rancangan Publikasi', 56, 711, 11, true, 15, 43, 102);

        $leftX = 56;
        $rightX = 307;
        $topY = 688;
        $cellW = 232;
        $cellH = 54;
        $gapY = 10;

        foreach ($rows as $index => $row) {
            $col = $index % 2;
            $line = intdiv($index, 2);
            $x = $col === 0 ? $leftX : $rightX;
            $y = $topY - ($line * ($cellH + $gapY));
            $content .= $this->drawCell($x, $y, $cellW, $cellH, $row[0], $row[1]);
        }

        $footerY = 92;
        $content .= $this->line(56, $footerY + 22, 539, $footerY + 22, 220, 230, 245);
        $content .= $this->text('Dokumen ini dihasilkan otomatis dari Manajemen Publikasi Statistik.', 56, $footerY, 8, false, 100, 116, 139);
        $content .= $this->text('Waktu cetak: ' . now()->format('d-m-Y H:i'), 56, $footerY - 14, 8, false, 100, 116, 139);

        return $content;
    }

    protected function drawCell(float $x, float $yTop, float $w, float $h, string $label, string $value): string
    {
        $y = $yTop - $h;
        $content = $this->rect($x, $y, $w, $h, true, true, 255, 255, 255, 219, 234, 254);
        $content .= $this->text($label, $x + 12, $yTop - 17, 7.5, false, 100, 116, 139);

        $lines = $this->wrap($value, 38, 3);
        $startY = $yTop - 33;
        foreach ($lines as $i => $line) {
            $content .= $this->text($line, $x + 12, $startY - ($i * 11), 9, true, 15, 23, 42);
        }

        return $content;
    }

    protected function rows(PublicationSprp $sprp): array
    {
        $languages = implode(', ', $sprp->bahasa ?? []);
        $yesNo = fn ($value) => $value === null ? '-' : ($value ? 'Ya' : 'Tidak');
        $date = fn ($value) => $value ? $value->translatedFormat('d F Y') : '-';

        return [
            ['Bidang/Bagian', $sprp->bidang_bagian ?: '-'],
            ['Rancangan Perwajahan', $sprp->rancangan_perwajahan ?: '-'],
            ['Judul Publikasi', $sprp->judul_publikasi ?: '-'],
            ['Publikasi Baru', $yesNo($sprp->publikasi_baru)],
            ['Ukuran', $sprp->ukuran ?: '-'],
            ['Orientasi', $sprp->orientasi ?: '-'],
            ['Frekuensi Terbit', $sprp->frekuensi_terbit ?: '-'],
            ['Terbitan Ke', $sprp->terbitan_ke ?: '-'],
            ['Tahun Pertama Terbit', (string) ($sprp->tahun_pertama_terbit ?: '-')],
            ['Diterbitkan Untuk', $sprp->diterbitkan_untuk ?: '-'],
            ['ARC/Non-ARC', ($sprp->kategori_rilis ?: '-') . ', ' . $date($sprp->tanggal_rilis)],
            ['Jumlah Halaman', 'Romawi: ' . ($sprp->jumlah_halaman_romawi ?: '-') . ' | Arab: ' . ($sprp->jumlah_halaman_arab ?: '-')],
            ['Kerja Sama Luar BPS', $yesNo($sprp->kerja_sama_luar_bps)],
            ['Bahasa', $languages ?: '-'],
            ['Diisi Oleh', optional($sprp->submittedBy)->name ?: '-'],
            ['Waktu Simpan', optional($sprp->submitted_at)->format('d-m-Y H:i') ?: '-'],
        ];
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

    protected function line(float $x1, float $y1, float $x2, float $y2, int $r, int $g, int $b): string
    {
        return "q\n" . $this->strokeColor($r, $g, $b) . "0.8 w\n" . $this->num($x1) . ' ' . $this->num($y1) . ' m ' . $this->num($x2) . ' ' . $this->num($y2) . " l S\nQ\n";
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

    protected function wrap(string $text, int $limit, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $this->sanitize($text)) ?? '');
        if ($text === '') {
            return ['-'];
        }

        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) <= $limit) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;

            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        if (count($lines) === $maxLines && strlen(implode(' ', $words)) > strlen(implode(' ', $lines))) {
            $lines[$maxLines - 1] = rtrim(substr($lines[$maxLines - 1], 0, max(0, $limit - 3))) . '...';
        }

        return $lines ?: ['-'];
    }

    protected function buildPdf(): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($this->objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($this->objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($this->objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($this->objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    protected function sanitize(string $text): string
    {
        $replace = [
            '–' => '-', '—' => '-', '“' => '"', '”' => '"', '‘' => "'", '’' => "'",
            '•' => '-', '…' => '...', '≥' => '>=', '≤' => '<=',
        ];

        $text = strtr($text, $replace);
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
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
