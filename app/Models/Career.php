<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Career extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'poster_image',
        'department',
        'location',
        'job_type',
        'due_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'due_date' => 'date',
    ];

    /**
     * Scope to search careers.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('slug', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('department', 'LIKE', "%{$search}%")
              ->orWhere('location', 'LIKE', "%{$search}%")
              ->orWhere('job_type', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get the responsibilities for this career.
     */
    public function responsibilities()
    {
        return $this->belongsToMany(Responsibility::class, 'career_responsibilities');
    }

    /**
     * Get the requirements for this career.
     */
    public function requirements()
    {
        return $this->belongsToMany(Requirement::class, 'career_requirements');
    }

    /**
     * Get the benefits for this career.
     */
    public function benefits()
    {
        return $this->belongsToMany(Benefit::class, 'career_benefits');
    }

    /**
     * Get the applications for this career.
     */
    public function applications()
    {
        return $this->hasMany(CareerApplication::class);
    }
}
