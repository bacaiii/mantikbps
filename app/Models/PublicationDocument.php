<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PublicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_team_id',
        'publication_id',
        'uploaded_by',
        'document_type',
        'version',
        'source_type',
        'file_path',
        'file_original_name',
        'mime_type',
        'file_size',
        'external_url',
        'notes',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function publicationTeam()
    {
        return $this->belongsTo(PublicationTeam::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return $this->is_link ? (string) $this->external_url : Storage::url($this->file_path);
    }

    public function getIsLinkAttribute(): bool
    {
        return $this->source_type === 'link' && !empty($this->external_url);
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_original_name, PATHINFO_EXTENSION));
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'surat_pernyataan_rilis' => 'Surat Pernyataan Rilis',
            'surat_persetujuan_rilis' => 'Surat Persetujuan Rilis',
            'naskah_pdf' => 'Naskah Publikasi PDF',
            'naskah_zip' => 'Naskah Publikasi RAR/ZIP',
            'infografis' => 'Infografis',
            'daftar_tabel_gambar' => 'Daftar Tabel & Gambar',
            'hasil_pemeriksaan_infografis' => 'Hasil Pemeriksaan Infografis',
            'hasil_pemeriksaan_daftar_tabel_gambar' => 'Hasil Pemeriksaan Daftar Tabel & Gambar',
            default => '-',
        };
    }

    public function getReadableSizeAttribute(): string
    {
        if ($this->is_link) {
            return 'Link';
        }

        if (!$this->file_size) {
            return '-';
        }

        if ($this->file_size >= 1024 * 1024) {
            return number_format($this->file_size / 1024 / 1024, 2) . ' MB';
        }

        return number_format($this->file_size / 1024, 1) . ' KB';
    }

    public function getIsImageAttribute(): bool
    {
        return !$this->is_link && in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
