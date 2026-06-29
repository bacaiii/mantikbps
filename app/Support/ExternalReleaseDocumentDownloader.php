<?php

namespace App\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\File;
use Throwable;

class ExternalReleaseDocumentDownloader
{
    protected const MAX_BYTES = 120 * 1024 * 1024;

    /**
     * Mengambil dokumen asli dari link eksternal untuk dimasukkan ke paket rilis.
     * Jika link tidak public atau hanya membuka halaman preview/login, method ini
     * akan mengembalikan status gagal agar controller membuat file TXT fallback.
     */
    public function download(string $url, string $temporaryDirectory, string $fallbackName, ?string $documentType = null): array
    {
        $url = trim($url);

        if (!$this->isValidDownloadableUrl($url)) {
            return $this->failed('Link tidak valid. Hanya link http dan https yang dapat diambil otomatis.');
        }

        File::ensureDirectoryExists($temporaryDirectory);
        $downloadUrl = $this->normalizeDownloadUrl($url, $documentType);
        $temporaryPath = tempnam($temporaryDirectory, 'external_release_');

        if ($temporaryPath === false) {
            return $this->failed('Folder sementara tidak dapat digunakan untuk mengambil dokumen eksternal.');
        }

        try {
            $cookieJar = new CookieJar();
            $client = new Client([
                'timeout' => 55,
                'connect_timeout' => 12,
                'allow_redirects' => [
                    'max' => 6,
                    'strict' => true,
                    'referer' => true,
                    'track_redirects' => true,
                ],
                'cookies' => $cookieJar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Manajemen Publikasi Statistik)',
                    'Accept' => 'application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream,image/*,*/*;q=0.8',
                ],
                'http_errors' => false,
            ]);

            $response = $client->request('GET', $downloadUrl, [
                'sink' => $temporaryPath,
                'on_headers' => function ($response) {
                    $contentLength = (int) ($response->getHeaderLine('Content-Length') ?: 0);
                    if ($contentLength > self::MAX_BYTES) {
                        throw new \RuntimeException('Ukuran file dari link melebihi batas 120 MB.');
                    }
                },
            ]);

            if ($this->needsGoogleDriveConfirmation($downloadUrl, $response->getHeaderLine('Content-Type'), $temporaryPath)) {
                $confirmUrl = $this->extractGoogleDriveConfirmUrl((string) file_get_contents($temporaryPath), $downloadUrl);

                if ($confirmUrl) {
                    @unlink($temporaryPath);
                    $temporaryPath = tempnam($temporaryDirectory, 'external_release_');

                    if ($temporaryPath === false) {
                        return $this->failed('Folder sementara tidak dapat digunakan untuk mengambil dokumen eksternal.');
                    }

                    $response = $client->request('GET', $confirmUrl, [
                        'sink' => $temporaryPath,
                        'on_headers' => function ($response) {
                            $contentLength = (int) ($response->getHeaderLine('Content-Length') ?: 0);
                            if ($contentLength > self::MAX_BYTES) {
                                throw new \RuntimeException('Ukuran file dari link melebihi batas 120 MB.');
                            }
                        },
                    ]);
                }
            }

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                @unlink($temporaryPath);
                return $this->failed('Link mengembalikan status HTTP ' . $statusCode . '.');
            }

            if (!is_file($temporaryPath) || filesize($temporaryPath) === 0) {
                @unlink($temporaryPath);
                return $this->failed('File dari link kosong atau tidak berhasil diunduh.');
            }

            $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0] ?? ''));
            if ($this->looksLikePreviewOrLoginPage($contentType, $temporaryPath)) {
                @unlink($temporaryPath);
                return $this->failed('Link masih mengarah ke halaman preview/login, bukan file unduhan langsung. Pastikan akses link dibuat public.');
            }

            $fileName = $this->detectFileName($response->getHeaderLine('Content-Disposition'), $downloadUrl, $fallbackName, $contentType, $documentType);

            return [
                'success' => true,
                'path' => $temporaryPath,
                'file_name' => $fileName,
                'content_type' => $contentType ?: 'application/octet-stream',
                'message' => 'Dokumen eksternal berhasil diunduh.',
            ];
        } catch (Throwable $exception) {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }

            return $this->failed($exception->getMessage() ?: 'Dokumen eksternal gagal diunduh.');
        }
    }

    protected function isValidDownloadableUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    protected function normalizeDownloadUrl(string $url, ?string $documentType = null): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $query = [];
        parse_str($parts['query'] ?? '', $query);

        if (str_contains($host, 'docs.google.com')) {
            if (preg_match('#/document/d/([^/]+)#', $path, $matches)) {
                $format = $documentType === 'naskah_pdf' ? 'pdf' : 'docx';
                return 'https://docs.google.com/document/d/' . $matches[1] . '/export?format=' . $format;
            }

            if (preg_match('#/spreadsheets/d/([^/]+)#', $path, $matches)) {
                return 'https://docs.google.com/spreadsheets/d/' . $matches[1] . '/export?format=xlsx';
            }

            if (preg_match('#/presentation/d/([^/]+)#', $path, $matches)) {
                return 'https://docs.google.com/presentation/d/' . $matches[1] . '/export/pptx';
            }
        }

        if (str_contains($host, 'drive.google.com')) {
            $fileId = null;

            if (preg_match('#/file/d/([^/]+)#', $path, $matches)) {
                $fileId = $matches[1];
            } elseif (!empty($query['id'])) {
                $fileId = $query['id'];
            }

            if ($fileId) {
                return 'https://drive.google.com/uc?export=download&id=' . rawurlencode($fileId);
            }
        }

        if (str_contains($host, 'dropbox.com')) {
            $query['dl'] = '1';
            $scheme = $parts['scheme'] ?? 'https';
            return $scheme . '://' . $host . $path . '?' . http_build_query($query);
        }

        return $url;
    }

    protected function needsGoogleDriveConfirmation(string $url, string $contentType, string $temporaryPath): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        $contentType = strtolower($contentType);

        if (!str_contains($host, 'drive.google.com')) {
            return false;
        }

        if (!str_contains($contentType, 'text/html')) {
            return false;
        }

        $html = is_file($temporaryPath) ? (string) file_get_contents($temporaryPath, false, null, 0, 1024 * 1024) : '';

        return str_contains($html, 'confirm=') || str_contains($html, 'download_warning') || str_contains($html, 'uc?export=download');
    }

    protected function extractGoogleDriveConfirmUrl(string $html, string $currentUrl): ?string
    {
        if (preg_match('/href="([^"]*uc\?export=download[^"]+)"/i', $html, $matches)) {
            $href = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (str_starts_with($href, '/')) {
                return 'https://drive.google.com' . $href;
            }
            if (str_starts_with($href, 'http')) {
                return $href;
            }
        }

        if (preg_match('/confirm=([0-9A-Za-z_\-]+)&amp;id=([0-9A-Za-z_\-]+)/', $html, $matches)) {
            return 'https://drive.google.com/uc?export=download&confirm=' . $matches[1] . '&id=' . $matches[2];
        }

        $id = null;
        parse_str(parse_url($currentUrl, PHP_URL_QUERY) ?: '', $query);
        if (!empty($query['id'])) {
            $id = $query['id'];
        }

        if ($id && preg_match('/confirm=([0-9A-Za-z_\-]+)/', $html, $matches)) {
            return 'https://drive.google.com/uc?export=download&confirm=' . $matches[1] . '&id=' . rawurlencode($id);
        }

        return null;
    }

    protected function looksLikePreviewOrLoginPage(string $contentType, string $temporaryPath): bool
    {
        if (!str_contains($contentType, 'text/html')) {
            return false;
        }

        $sample = strtolower((string) file_get_contents($temporaryPath, false, null, 0, 4096));

        return str_contains($sample, '<html')
            || str_contains($sample, '<!doctype html')
            || str_contains($sample, 'sign in')
            || str_contains($sample, 'login');
    }

    protected function detectFileName(string $contentDisposition, string $url, string $fallbackName, string $contentType, ?string $documentType = null): string
    {
        $fileName = $this->fileNameFromContentDisposition($contentDisposition)
            ?: $this->fileNameFromUrl($url)
            ?: $fallbackName
            ?: 'dokumen-eksternal';

        $fileName = trim(str_replace(['\\', '/'], '-', $fileName));
        $fileName = preg_replace('/[\x00-\x1F\x7F]+/', '', $fileName) ?: 'dokumen-eksternal';

        if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
            $extension = $this->extensionFromContentType($contentType) ?: $this->extensionFromDocumentType($documentType);
            if ($extension) {
                $fileName .= '.' . $extension;
            }
        }

        return $fileName;
    }

    protected function fileNameFromContentDisposition(string $contentDisposition): ?string
    {
        if (!$contentDisposition) {
            return null;
        }

        if (preg_match('/filename\*=UTF-8\'\'([^;]+)/i', $contentDisposition, $matches)) {
            return rawurldecode(trim($matches[1], '" '));
        }

        if (preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function fileNameFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $basename = basename($path);

        if ($basename && $basename !== '/' && !in_array($basename, ['view', 'edit', 'export', 'download', 'uc'], true)) {
            return rawurldecode($basename);
        }

        return null;
    }

    protected function extensionFromContentType(string $contentType): ?string
    {
        return match ($contentType) {
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
    }

    protected function extensionFromDocumentType(?string $documentType): ?string
    {
        return match ($documentType) {
            'naskah_pdf' => 'pdf',
            'naskah_zip' => 'zip',
            'infografis' => 'jpg',
            'daftar_tabel_gambar' => 'xlsx',
            'surat_persetujuan_rilis', 'surat_pernyataan_rilis' => 'pdf',
            default => null,
        };
    }

    protected function failed(string $message): array
    {
        return [
            'success' => false,
            'path' => null,
            'file_name' => null,
            'content_type' => null,
            'message' => $message,
        ];
    }
}
