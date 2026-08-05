<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DosageForm extends Model
{
    use HasFactory;

    protected $primaryKey = 'dosage_form_id';
    protected $table = 'dosage_forms';
    
    protected $fillable = [
        'dosage_form_name',
        'slug',
        'brand_count',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->dosage_form_name);
        });
    }

    // Relationships
    public function brands()
    {
        return $this->hasMany(Brand::class, 'dosage_form_id', 'dosage_form_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('dosage_form_name', 'LIKE', "%{$search}%");
    }
}
