<?php

namespace App\Support;

use RuntimeException;

class PortableZipWriter
{
    protected $handle = null;
    protected array $centralDirectory = [];
    protected bool $closed = false;

    public function __construct(protected string $zipPath)
    {
        $directory = dirname($zipPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder sementara paket rilis tidak dapat dibuat.');
        }

        $this->handle = fopen($zipPath, 'wb');
        if (!$this->handle) {
            throw new RuntimeException('File ZIP sementara tidak dapat ditulis.');
        }
    }

    public function addFromString(string $name, string $contents): void
    {
        $this->assertOpen();

        $name = $this->normalizeName($name);
        $size = strlen($contents);
        $crc = (int) hexdec(hash('crc32b', $contents));
        [$dosTime, $dosDate] = $this->dosTimeDate(time());
        $offset = ftell($this->handle);

        $this->writeLocalHeader($name, $crc, $size, $dosTime, $dosDate);
        $this->write($contents);
        $this->rememberCentralDirectory($name, $crc, $size, $dosTime, $dosDate, $offset);
    }

    public function addFile(string $realPath, string $name): void
    {
        $this->assertOpen();

        if (!is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException('File paket rilis tidak dapat dibaca: ' . basename($realPath));
        }

        $name = $this->normalizeName($name);
        $size = filesize($realPath);
        if ($size === false) {
            throw new RuntimeException('Ukuran file paket rilis tidak dapat dibaca: ' . basename($realPath));
        }

        if ($size > 0xFFFFFFFF) {
            throw new RuntimeException('File terlalu besar untuk paket ZIP standar: ' . basename($realPath));
        }

        $crc = (int) hexdec(hash_file('crc32b', $realPath));
        [$dosTime, $dosDate] = $this->dosTimeDate(filemtime($realPath) ?: time());
        $offset = ftell($this->handle);

        $this->writeLocalHeader($name, $crc, (int) $size, $dosTime, $dosDate);

        $source = fopen($realPath, 'rb');
        if (!$source) {
            throw new RuntimeException('File paket rilis tidak dapat dibuka: ' . basename($realPath));
        }

        while (!feof($source)) {
            $chunk = fread($source, 1024 * 1024);
            if ($chunk === false) {
                fclose($source);
                throw new RuntimeException('File paket rilis gagal dibaca: ' . basename($realPath));
            }
            if ($chunk !== '') {
                $this->write($chunk);
            }
        }

        fclose($source);
        $this->rememberCentralDirectory($name, $crc, (int) $size, $dosTime, $dosDate, $offset);
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->assertOpen();

        $centralOffset = ftell($this->handle);
        foreach ($this->centralDirectory as $entry) {
            $this->writeCentralHeader($entry);
        }
        $centralSize = ftell($this->handle) - $centralOffset;
        $entryCount = count($this->centralDirectory);

        $this->write(pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            $centralSize,
            $centralOffset,
            0
        ));

        fclose($this->handle);
        $this->handle = null;
        $this->closed = true;
    }

    public function __destruct()
    {
        if (!$this->closed && is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    protected function writeLocalHeader(string $name, int $crc, int $size, int $dosTime, int $dosDate): void
    {
        $nameLength = strlen($name);
        $this->write(pack(
            'VvvvvvVVVvv',
            0x04034b50,
            10,
            0x0800,
            0,
            $dosTime,
            $dosDate,
            $crc,
            $size,
            $size,
            $nameLength,
            0
        ));
        $this->write($name);
    }

    protected function writeCentralHeader(array $entry): void
    {
        $name = $entry['name'];
        $nameLength = strlen($name);

        $this->write(pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            10,
            0x0800,
            0,
            $entry['time'],
            $entry['date'],
            $entry['crc'],
            $entry['size'],
            $entry['size'],
            $nameLength,
            0,
            0,
            0,
            0,
            0,
            $entry['offset']
        ));
        $this->write($name);
    }

    protected function rememberCentralDirectory(string $name, int $crc, int $size, int $dosTime, int $dosDate, int $offset): void
    {
        $this->centralDirectory[] = [
            'name' => $name,
            'crc' => $crc,
            'size' => $size,
            'time' => $dosTime,
            'date' => $dosDate,
            'offset' => $offset,
        ];
    }

    protected function dosTimeDate(int $timestamp): array
    {
        $date = getdate($timestamp);
        $year = max(1980, min(2107, (int) $date['year']));

        $dosTime = ((int) $date['hours'] << 11)
            | ((int) $date['minutes'] << 5)
            | intdiv((int) $date['seconds'], 2);

        $dosDate = (($year - 1980) << 9)
            | ((int) $date['mon'] << 5)
            | (int) $date['mday'];

        return [$dosTime, $dosDate];
    }

    protected function normalizeName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = preg_replace('#/+#', '/', $name) ?? $name;
        $name = ltrim($name, '/');

        if ($name === '' || str_contains($name, '..')) {
            throw new RuntimeException('Nama file paket rilis tidak valid.');
        }

        return $name;
    }

    protected function assertOpen(): void
    {
        if ($this->closed || !is_resource($this->handle)) {
            throw new RuntimeException('Writer ZIP sudah ditutup.');
        }
    }

    protected function write(string $data): void
    {
        $length = strlen($data);
        $written = 0;

        while ($written < $length) {
            $result = fwrite($this->handle, substr($data, $written));
            if ($result === false || $result === 0) {
                throw new RuntimeException('Paket ZIP gagal ditulis.');
            }
            $written += $result;
        }
    }
}
