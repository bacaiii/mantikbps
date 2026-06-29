<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_type',
        'title',
        'file_path',
        'file_original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_original_name, PATHINFO_EXTENSION));
    }

    public function getTemplateTypeLabelAttribute(): string
    {
        return match ($this->template_type) {
            'surat_pernyataan_rilis' => 'Surat Pernyataan Rilis',
            'surat_persetujuan_rilis' => 'Surat Persetujuan Rilis',
            default => '-',
        };
    }
}
