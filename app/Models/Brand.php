<?php

namespace App\Models ;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $primaryKey = 'brand_id';
    protected $table = 'brands';
    
    protected $fillable = [
        'brand_name',
        'slug',
        'generic_id',
        'dosage_form_id',
        'manufacturer_id',
        'strength',
        'unit',
        'price',
        'manufacturer_name_backup',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->brand_name);
        });
    }

    // Relationships
    public function generic()
    {
        return $this->belongsTo(Generic::class, 'generic_id', 'generic_id');
    }

    public function dosageForm()
    {
        return $this->belongsTo(DosageForm::class, 'dosage_form_id', 'dosage_form_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return $this->price ? 'BDT ' . number_format($this->price, 2) : 'N/A';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('brand_name', 'LIKE', "%{$search}%")
                     ->orWhere('strength', 'LIKE', "%{$search}%");
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    } 
}
