<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indication extends Model
{
    use HasFactory;

    protected $primaryKey = 'indication_id';
    protected $table = 'indications';
    
    protected $fillable = [
        'generic_id',
        'indication_name',
        'indication_code',
        'description',
        'severity',
        'is_active'
    ];

    // Relationships
    public function generic()
    {
        return $this->belongsTo(Generic::class, 'generic_id', 'generic_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('indication_name', 'LIKE', "%{$search}%")
                     ->orWhere('indication_code', 'LIKE', "%{$search}%");
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }
}
