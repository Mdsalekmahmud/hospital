<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Manufacturer extends Model
{
    use HasFactory;

    protected $primaryKey = 'manufacturer_id';
    protected $table = 'manufacturers';
    
    protected $fillable = [
        'manufacturer_name',
        'slug',
        'country',
        'address',
        'phone',
        'email',
        'website',
        'brand_count',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->manufacturer_name);
        });
    }

    // Relationships
    public function brands()
    {
        return $this->hasMany(Brand::class, 'manufacturer_id', 'manufacturer_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('manufacturer_name', 'LIKE', "%{$search}%")
                     ->orWhere('country', 'LIKE', "%{$search}%");
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}
