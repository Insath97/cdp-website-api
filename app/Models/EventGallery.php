<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventGallery extends Model
{
      use HasFactory;

      protected $fillable = [
        'event_id',
        'image_path'
      ];

      /**
     * Get the event that owns the gallery.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
