<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_draft_id',
        'publication_team_id',
        'publication_id',
        'reviewer_id',
        'review_type',
        'result',
        'checklist',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'checklist' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function draft()
    {
        return $this->belongsTo(PublicationDraft::class, 'publication_draft_id');
    }

    public function publicationTeam()
    {
        return $this->belongsTo(PublicationTeam::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function getResultLabelAttribute(): string
    {
        return $this->result === 'disetujui' ? 'Disetujui' : 'Revisi';
    }

    public function getReviewTypeLabelAttribute(): string
    {
        return match ($this->review_type) {
            'konten' => 'Pemeriksaan Konten',
            'layout' => 'Pemeriksaan Layout',
            'infografis' => 'Pemeriksaan Infografis',
            'pimpinan' => 'Persetujuan Pimpinan',
            default => 'Pemeriksaan',
        };
    }
}