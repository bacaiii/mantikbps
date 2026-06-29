<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationReviewSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_draft_id',
        'publication_team_id',
        'publication_id',
        'reviewer_id',
        'review_type',
        'anatomy_section',
        'sort_order',
        'answers',
        'notes',
        'saved_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'saved_at' => 'datetime',
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

    public function getReviewTypeLabelAttribute(): string
    {
        return $this->review_type === 'konten' ? 'Pemeriksaan Konten' : 'Pemeriksaan Layout';
    }

    public function getFailedItemsAttribute(): array
    {
        return collect($this->answers ?? [])
            ->filter(fn ($item) => ($item['answer'] ?? null) === 'tidak')
            ->values()
            ->all();
    }

    public function getHasRevisionAttribute(): bool
    {
        return count($this->failed_items) > 0;
    }
}
