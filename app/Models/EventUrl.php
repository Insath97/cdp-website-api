<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventUrl extends Model
{
    protected $fillable = ['event_id', 'url'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
