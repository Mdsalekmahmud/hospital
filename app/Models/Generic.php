<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Generic extends Model
{
    use HasFactory;

    protected $primaryKey = 'generic_id';
    protected $table = 'generics';
    
    protected $fillable = [
        'generic_name',
        'slug',
        'drug_class_id',
        'strength',
        'unit',
        'indication',
        'contraindication',
        'side_effects',
        'pharmacology',
        'dosage',
        'interaction',
        'precautions',
        'pregnancy_lactation',
        'pediatric_usage',
        'overdose_effects',
        'storage_conditions',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->generic_name);
        });
    }

    // Relationships
    public function drugClass()
    {
        return $this->belongsTo(DrugClass::class, 'drug_class_id', 'drug_class_id');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class, 'generic_id', 'generic_id');
    }

    public function indications()
    {
        return $this->hasMany(Indication::class, 'generic_id', 'generic_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('generic_name', 'LIKE', "%{$search}%")
                     ->orWhere('indication', 'LIKE', "%{$search}%");
    }

    public function scopeByDrugClass($query, $drugClassId)
    {
        return $query->where('drug_class_id', $drugClassId);
    }
}
