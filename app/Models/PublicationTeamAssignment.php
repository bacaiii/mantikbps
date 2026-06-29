<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTeamAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'publication_team_id',
        'user_id',
        'assignment_role',
        'assigned_by',
        'notes',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function team()
    {
        return $this->belongsTo(PublicationTeam::class, 'publication_team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getAssignmentRoleLabelAttribute(): string
    {
        return match ($this->assignment_role) {
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_website' => 'Operator Website',
            'operator_infografis' => 'Operator Infografis',
            default => '-',
        };
    }
}
