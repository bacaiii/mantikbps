<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationSprp extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_team_id',
        'publication_id',
        'submitted_by',
        'bidang_bagian',
        'rancangan_perwajahan',
        'judul_publikasi',
        'publikasi_baru',
        'ukuran',
        'orientasi',
        'frekuensi_terbit',
        'terbitan_ke',
        'tahun_pertama_terbit',
        'diterbitkan_untuk',
        'kategori_rilis',
        'tanggal_rilis',
        'jumlah_halaman_romawi',
        'jumlah_halaman_arab',
        'kerja_sama_luar_bps',
        'bahasa',
        'submitted_at',
    ];

    protected $casts = [
        'publikasi_baru' => 'boolean',
        'kerja_sama_luar_bps' => 'boolean',
        'bahasa' => 'array',
        'tanggal_rilis' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function publicationTeam()
    {
        return $this->belongsTo(PublicationTeam::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
