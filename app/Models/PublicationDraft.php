<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PublicationDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_team_id',
        'publication_id',
        'uploaded_by',
        'version',
        'source_type',
        'file_path',
        'external_url',
        'file_original_name',
        'mime_type',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviews()
    {
        return $this->hasMany(PublicationReview::class);
    }

    public function reviewSlides()
    {
        return $this->hasMany(PublicationReviewSlide::class);
    }

    public function getFileUrlAttribute(): string
    {
        if ($this->is_link && $this->external_url) {
            return $this->external_url;
        }

        return Storage::url($this->file_path);
    }

    public function getIsLinkAttribute(): bool
    {
        return $this->source_type === 'link' && !empty($this->external_url);
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_original_name, PATHINFO_EXTENSION));
    }
}