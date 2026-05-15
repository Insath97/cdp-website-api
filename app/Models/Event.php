<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'title',
        'slug',
        'created_date',
        'created_by',
        'thumbnail_image',
        'url',
        'description',
        'is_active',
        'status',
        'decision_by',
        'decision_at',
        'rejected_reason'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'decision_at' => 'datetime',
        'created_date' => 'date',
    ];

     /**
     * Scope to search events.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('slug', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('created_date', 'LIKE', "%{$search}%")
              ->orWhere('status', 'LIKE', "%{$search}%");
        });
    }

     /**
     * Get the galleries for the event.
     */
    public function galleries()
    {
        return $this->hasMany(EventGallery::class);
    }

    /**
     * The user who created the event.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who approved/rejected the event.
     */
    public function decisionBy()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }
}
