<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_code',
        'career_id',
        'fullname',
        'email',
        'phone_number',
        'resume_path',
        'cover_letter',
        'status',
    ];

    /**
     * Get the career post this application belongs to.
     */
    public function career()
    {
        return $this->belongsTo(Career::class);
    }
}
