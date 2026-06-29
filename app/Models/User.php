<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'login_id',
        'email',
        'phone',
        'role',
        'password',
        'password_text',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'password_text',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getPasswordPreviewAttribute(): ?string
    {
        if (!$this->password_text) {
            return null;
        }

        try {
            return Crypt::decryptString($this->password_text);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function isAdminSystem(): bool
    {
        return $this->role === 'admin_sistem';
    }

    public function teamAssignments()
    {
        return $this->hasMany(PublicationTeamAssignment::class);
    }

    public function createdPublicationTeams()
    {
        return $this->hasMany(PublicationTeam::class, 'created_by');
    }
}