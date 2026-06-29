<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTemplateMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_template_id',
        'user_id',
        'assignment_role',
        'sort_order',
    ];

    public function template()
    {
        return $this->belongsTo(TeamTemplate::class, 'team_template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
