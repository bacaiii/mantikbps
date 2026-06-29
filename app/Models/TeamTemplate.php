<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(TeamTemplateMember::class)->orderBy('sort_order')->orderBy('id');
    }

    public function hasCompleteAssignments(): bool
    {
        $this->loadMissing('tenant');

        $members = $this->relationLoaded('members')
            ? $this->members
            : $this->members()->get();

        $roleCounts = $members->pluck('assignment_role')->countBy();
        $requiresInfografis = optional($this->tenant)->type === 'provinsi';

        return ($roleCounts['penyusun_naskah'] ?? 0) >= 1
            && ($roleCounts['ketua_pemeriksa_konten'] ?? 0) === 1
            && ($roleCounts['ketua_pemeriksa_layout'] ?? 0) === 1
            && (!$requiresInfografis || ($roleCounts['operator_infografis'] ?? 0) === 1)
            && ($roleCounts['operator_website'] ?? 0) === 1;
    }

    public function getTemplateStatusLabelAttribute(): string
    {
        return $this->hasCompleteAssignments() ? 'Lengkap' : 'Belum Lengkap';
    }
}
