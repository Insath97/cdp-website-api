<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
     use HasFactory;

     protected $fillable = [
        'image',
        'maintitle',
        'subtitle',
        'short_description',
        'is_active'
     ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search plans.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('maintitle', 'LIKE', "%{$search}%")
              ->orWhere('subtitle', 'LIKE', "%{$search}%")
              ->orWhere('short_description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get the features for the plan.
     */
    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }
}
