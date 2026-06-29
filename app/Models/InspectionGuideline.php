<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionGuideline extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'anatomy_section',
        'inspection_item',
        'requirement_detail',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'konten' ? 'Pemeriksaan Konten' : 'Pemeriksaan Layout';
    }
}
