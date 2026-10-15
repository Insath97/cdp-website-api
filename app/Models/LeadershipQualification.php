<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadershipQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'leadership_member_id',
        'title',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(LeadershipMember::class, 'leadership_member_id');
    }
}
