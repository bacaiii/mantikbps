<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Publication extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'nomor',
        'nama_publikasi',
        'estimasi_nomor_publikasi',
        'pembuat_publikasi',
        'kategori',
        'jadwal_rilis',
        'jadwal_upload',
        'jadwal_mulai_pemeriksaan',
        'jadwal_mulai_penyusunan',
        'akurasi_publikasi',
        'tahun',
        'periode',
        'wilayah',
        'jenis_publikasi',
        'deskripsi_singkat',
        'status',
        'revision_return_stage',
        'tanggal_rilis_aktual',
        'draft_submitted_at',
        'content_review_started_at',
        'content_review_finished_at',
        'layout_review_started_at',
        'layout_review_finished_at',
        'infographic_review_started_at',
        'infographic_review_finished_at',
        'leadership_approved_at',
        'website_packaged_at',
        'ready_for_release_at',
        'catatan',
    ];

    protected $casts = [
        'jadwal_rilis' => 'date',
        'jadwal_upload' => 'date',
        'jadwal_mulai_pemeriksaan' => 'date',
        'jadwal_mulai_penyusunan' => 'date',
        'tanggal_rilis_aktual' => 'date',
        'draft_submitted_at' => 'datetime',
        'content_review_started_at' => 'datetime',
        'content_review_finished_at' => 'datetime',
        'layout_review_started_at' => 'datetime',
        'layout_review_finished_at' => 'datetime',
        'infographic_review_started_at' => 'datetime',
        'infographic_review_finished_at' => 'datetime',
        'leadership_approved_at' => 'datetime',
        'website_packaged_at' => 'datetime',
        'ready_for_release_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->hasOne(PublicationTeam::class);
    }

    public function teamAssignments()
    {
        return $this->hasMany(PublicationTeamAssignment::class);
    }

    public function teamAssignmentHistories()
    {
        return $this->hasMany(PublicationTeamAssignmentHistory::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'penyusunan' => 'Penyusunan',
            'pemeriksaan_konten' => 'Pemeriksaan Konten',
            'pemeriksaan_layout' => 'Pemeriksaan Layout',
            'pemeriksaan_infografis' => 'Pemeriksaan Infografis',
            'persetujuan_pimpinan' => 'Persetujuan Pimpinan',
            'operator_website' => 'Finalisasi Rilis',
            'siap_rilis' => 'Siap Rilis',
            'pengajuan_rilis' => 'Pengajuan Rilis',
            'rilis_selesai' => 'Rilis',
            default => '-',
        };
    }


    public function getStatusCssClassAttribute(): string
    {
        return match ($this->status) {
            'penyusunan' => 'status-penyusunan',
            'pemeriksaan_konten' => 'status-pemeriksaan-konten',
            'pemeriksaan_layout' => 'status-pemeriksaan-layout',
            'pemeriksaan_infografis' => 'status-pemeriksaan-infografis',
            'persetujuan_pimpinan' => 'status-persetujuan-pimpinan',
            'operator_website' => 'status-finalisasi-rilis',
            'siap_rilis' => 'status-siap-rilis',
            'pengajuan_rilis' => 'status-pengajuan-rilis',
            'rilis_selesai' => 'status-siap-rilis',
            default => 'secondary',
        };
    }

    public function getKetepatanArcAttribute(): string
    {
        if ($this->kategori !== 'ARC') {
            return '-';
        }

        if (!$this->jadwal_rilis) {
            return 'Belum Ada Jadwal';
        }

        if (in_array($this->status, ['siap_rilis', 'rilis_selesai'], true)) {
            $finishDate = $this->tanggal_rilis_aktual
                ?: ($this->ready_for_release_at ? $this->ready_for_release_at->copy()->startOfDay() : null);

            if (!$finishDate) {
                return 'Siap Rilis';
            }

            return $finishDate->lte($this->jadwal_rilis)
                ? 'Tepat Waktu'
                : 'Terlambat';
        }

        return Carbon::today()->gt($this->jadwal_rilis)
            ? 'Terlambat'
            : 'Dalam Proses';
    }

    public function hasCompleteTeam(): bool
    {
        if ($this->relationLoaded('team') && $this->team) {
            return $this->team->hasCompleteAssignments();
        }

        if ($this->team) {
            return $this->team->hasCompleteAssignments();
        }

        return false;
    }

    public function getTeamStatusLabelAttribute(): string
    {
        return $this->hasCompleteTeam() ? 'Lengkap' : 'Belum Lengkap';
    }

    public function drafts()
    {
        return $this->hasMany(PublicationDraft::class);
    }

    public function reviews()
    {
        return $this->hasMany(PublicationReview::class);
    }

    public function reviewSlides()
    {
        return $this->hasMany(PublicationReviewSlide::class);
    }

    public function documents()
    {
        return $this->hasMany(PublicationDocument::class);
    }

    public function sprp()
    {
        return $this->hasOne(PublicationSprp::class);
    }

    public function reviewNotes()
    {
        return $this->hasMany(PublicationReviewNote::class);
    }
}
