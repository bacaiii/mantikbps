<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'name',
        'created_by',
        'notes',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function assignments()
    {
        return $this->hasMany(PublicationTeamAssignment::class);
    }

    public function histories()
    {
        return $this->hasMany(PublicationTeamAssignmentHistory::class);
    }

    public function drafts()
    {
        return $this->hasMany(PublicationDraft::class);
    }

    public function latestDraft()
    {
        return $this->hasOne(PublicationDraft::class)->latestOfMany();
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

    public function hasCompleteAssignments(): bool
    {
        $this->loadMissing('publication.tenant');

        $assignments = $this->relationLoaded('assignments')
            ? $this->assignments
            : $this->assignments()->get();

        $roleCounts = $assignments->pluck('assignment_role')->countBy();

        $penyusunNaskah = $roleCounts['penyusun_naskah'] ?? 0;
        $ketuaPemeriksaKonten = $roleCounts['ketua_pemeriksa_konten'] ?? 0;
        $ketuaPemeriksaLayout = $roleCounts['ketua_pemeriksa_layout'] ?? 0;
        $operatorInfografis = $roleCounts['operator_infografis'] ?? 0;
        $operatorWebsite = $roleCounts['operator_website'] ?? 0;
        $requiresInfografis = optional(optional($this->publication)->tenant)->type === 'provinsi';

        return $penyusunNaskah >= 1
            && $ketuaPemeriksaKonten === 1
            && $ketuaPemeriksaLayout === 1
            && (!$requiresInfografis || $operatorInfografis === 1)
            && $operatorWebsite === 1;
    }

    public function isComplete(): bool
    {
        return $this->hasCompleteAssignments();
    }

    public function getTeamStatusLabelAttribute(): string
    {
        return $this->hasCompleteAssignments() ? 'Lengkap' : 'Belum Lengkap';
    }
}
