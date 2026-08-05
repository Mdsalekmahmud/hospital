<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DrugClass extends Model
{
    use HasFactory;

    protected $primaryKey = 'drug_class_id';
    protected $table = 'drug_classes';
    
    protected $fillable = [
        'drug_class_name',
        'slug',
        'description',
        'generic_count',
        'is_active'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->slug = Str::slug($model->drug_class_name);
        });
    }

    // Relationships
    public function generics()
    {
        return $this->hasMany(Generic::class, 'drug_class_id', 'drug_class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('drug_class_name', 'LIKE', "%{$search}%");
    }
}
