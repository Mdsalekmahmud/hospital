<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    
    protected $fillable = [
        'id',
        'service_name',
        'price',
        'service_category_id',
        'category_name_backup',
        'slug',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($service) {
            $service->slug = Str::slug($service->service_name . '-' . uniqid());
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return $this->price ? 'BDT ' . number_format($this->price, 2) : 'N/A';
    }
    
    public function getPriceNumericAttribute()
    {
        return (float) $this->price ?? 0;
    }

    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : ($this->category_name_backup ?? 'Uncategorized');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('service_name', 'LIKE', "%{$search}%")
              ->orWhere('category_name_backup', 'LIKE', "%{$search}%")
              ->orWhere('price', 'LIKE', "%{$search}%")
              ->orWhereHas('category', function ($q2) use ($search) {
                  $q2->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('service_category_id', $categoryId);
    }

    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min) {
            $query->whereRaw('CAST(price AS UNSIGNED) >= ?', [$min]);
        }
        if ($max) {
            $query->whereRaw('CAST(price AS UNSIGNED) <= ?', [$max]);
        }
        return $query;
    }
}
