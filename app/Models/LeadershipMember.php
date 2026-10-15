<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadershipMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'name',
        'designation',
        'group_label',
        'summary',
        'executive_statement',
        'message',
        'focus_area',
        'linkedin',
        'photo',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function qualifications(): HasMany
    {
        return $this->hasMany(LeadershipQualification::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSection(Builder $query, ?string $section): Builder
    {
        if (empty($section)) {
            return $query;
        }

        return $query->where('section', $section);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('designation', 'like', "%{$search}%")
                ->orWhere('group_label', 'like', "%{$search}%")
                ->orWhere('focus_area', 'like', "%{$search}%")
                ->orWhere('executive_statement', 'like', "%{$search}%");
        });
    }
}
