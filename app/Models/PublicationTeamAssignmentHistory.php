<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTeamAssignmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'action',
        'old_value',
        'new_value',
        'notes',
        'changed_by',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}