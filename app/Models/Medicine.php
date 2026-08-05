<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Medicine extends Model
{
    use HasFactory;

    protected $table = 'medicines';
    
    protected $fillable = [
        'id',
        'brand_id',
        'brand_name',
        'type',
        'slug',
        'dosage_form',
        'generic',
        'strength',
        'manufacturer',
        'package_container',
        'package_size',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->brand_name . '-' . uniqid());
        });
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where('brand_name', 'LIKE', "%{$search}%")
                     ->orWhere('generic', 'LIKE', "%{$search}%")
                     ->orWhere('manufacturer', 'LIKE', "%{$search}%");
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByManufacturer($query, $manufacturer)
    {
        return $query->where('manufacturer', 'LIKE', "%{$manufacturer}%");
    }
}

