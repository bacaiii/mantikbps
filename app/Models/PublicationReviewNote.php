<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationReviewNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'publication_team_id',
        'publication_document_id',
        'reviewer_id',
        'page_number',
        'section_name',
        'note_type',
        'note',
        'status',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function publicationTeam()
    {
        return $this->belongsTo(PublicationTeam::class);
    }

    public function document()
    {
        return $this->belongsTo(PublicationDocument::class, 'publication_document_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'belum_diperbaiki' => 'Belum Diperbaiki',
            'sudah_diperbaiki' => 'Sudah Diperbaiki',
            'diverifikasi' => 'Diverifikasi',
            default => '-',
        };
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            'belum_diperbaiki' => 'danger',
            'sudah_diperbaiki' => 'warning',
            'diverifikasi' => 'success',
            default => 'secondary',
        };
    }

    public function getNoteTypeLabelAttribute(): string
    {
        return match ($this->note_type) {
            'revisi' => 'Revisi',
            'saran' => 'Saran',
            'catatan' => 'Catatan',
            default => '-',
        };
    }

    public function getNoteTypeIconAttribute(): string
    {
        return match ($this->note_type) {
            'revisi' => 'bi-exclamation-triangle-fill',
            'saran' => 'bi-lightbulb-fill',
            'catatan' => 'bi-chat-left-text-fill',
            default => 'bi-sticky-fill',
        };
    }
}
