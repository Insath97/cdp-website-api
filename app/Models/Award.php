<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'award_type_id',
        'title',
        'slug',
        'short_description',
        'description',
        'verified',
        'is_active',
        'thumbnail_image',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function awardType(): BelongsTo
    {
        return $this->belongsTo(AwardType::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(AwardGallery::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
