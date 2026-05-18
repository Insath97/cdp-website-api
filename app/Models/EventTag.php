<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventTag extends Pivot
{
    protected $fillable = [
        'event_id',
        'tag_id',
    ];

}
