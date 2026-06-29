<?php

namespace App\Support;

class SimplePdf
{
    protected array $pages = [];
    protected int $lineLimit;
    protected int $lineLength;

    public function __construct(
        protected string $title = 'Laporan',
        protected string $orientation = 'portrait'
    ) {
        $this->lineLimit = $orientation === 'landscape' ? 42 : 58;
        $this->lineLength = $orientation === 'landscape' ? 145 : 95;
    }

    public function addLine(string $text = ''): void
    {
        $lines = $this->wrapText($text);

        foreach ($lines as $line) {
            if (empty($this->pages) || count($this->pages[array_key_last($this->pages)]) >= $this->lineLimit) {
                $this->pages[] = [];
            }

            $this->pages[array_key_last($this->pages)][] = $line;
        }
    }

    public function addBlank(): void
    {
        $this->addLine('');
    }

    public function addSection(string $title): void
    {
        $this->addBlank();
        $this->addLine(strtoupper($title));
        $this->addLine(str_repeat('-', min(strlen($title) + 4, 80)));
    }

    public function output(): string
    {
        if (empty($this->pages)) {
            $this->addLine($this->title);
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageKids = [];
        $pageObjectNumbers = [];

        $pageCount = count($this->pages);
        $nextObject = 3;
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectNumbers[$i] = $nextObject;
            $pageKids[] = $nextObject . ' 0 R';
            $nextObject += 2;
        }

        $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $pageKids) . '] /Count ' . $pageCount . ' >>';

        foreach ($this->pages as $index => $pageLines) {
            $pageNo = $pageObjectNumbers[$index];
            $contentNo = $pageNo + 1;
            $mediaBox = $this->orientation === 'landscape' ? '[0 0 842 595]' : '[0 0 595 842]';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox ' . $mediaBox . ' /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> /F3 << /Type /Font /Subtype /Type1 /BaseFont /Courier >> >> >> /Contents ' . $contentNo . ' 0 R >>';
            $stream = $this->pageStream($pageLines, $index + 1, $pageCount);
            $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $i => $object) {
            $offsets[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    protected function pageStream(array $lines, int $page, int $pageCount): string
    {
        $x = 42;
        $y = $this->orientation === 'landscape' ? 548 : 795;
        $lineHeight = 12;
        $content = "BT\n";
        $content .= "/F2 12 Tf\n";
        $content .= $x . ' ' . $y . ' Td (' . $this->escape($this->sanitize($this->title)) . ") Tj\n";
        $content .= "/F1 9 Tf\n";
        $content .= "0 -18 Td\n";
        $content .= '(' . $this->escape('Dicetak: ' . now()->format('d-m-Y H:i')) . ") Tj\n";
        $content .= "0 -20 Td\n";
        $content .= "/F3 8.6 Tf\n";

        foreach ($lines as $line) {
            $content .= '(' . $this->escape($this->sanitize($line)) . ") Tj\n";
            $content .= '0 -' . $lineHeight . " Td\n";
        }

        $content .= "/F1 8 Tf\n";
        $content .= $x . ' ' . ($this->orientation === 'landscape' ? -500 : -730) . " Td\n";
        $content .= '(' . $this->escape('Halaman ' . $page . ' dari ' . $pageCount) . ") Tj\n";
        $content .= "ET";

        return $content;
    }

    protected function wrapText(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $this->sanitize($text)) ?? '');

        if ($text === '') {
            return [''];
        }

        return explode("\n", wordwrap($text, $this->lineLength, "\n", true));
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
}
