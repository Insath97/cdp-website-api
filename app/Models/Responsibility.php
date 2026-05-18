<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function careers()
    {
        return $this->belongsToMany(Career::class, 'career_responsibilities');
    }
}
