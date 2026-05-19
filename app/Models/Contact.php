<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'contact_type_id',
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
        'reply',
        'reply_message',
        'status',
        'is_replied',
        'replied_by',
        'replied_at',
    ];

    protected $casts = [
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * Accessor for full name.
     */
    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Accessor for reply message.
     */
    public function getReplyMessageAttribute()
    {
        return $this->reply;
    }

    /**
     * Mutator for reply message.
     */
    public function setReplyMessageAttribute($value)
    {
        $this->attributes['reply'] = $value;
    }

    /**
     * Scope to search contact types.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'LIKE', "%{$search}%")
              ->orWhere('last_name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('subject', 'LIKE', "%{$search}%")
              ->orWhere('message', 'LIKE', "%{$search}%")
              ->orWhere('reply', 'LIKE', "%{$search}%");
        }); 
    }
}
