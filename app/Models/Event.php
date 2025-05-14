<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'user_id',
        'is_all_day',
        'color',
        'status',
        'visibility',
        'private',
        'google_event_id'
    ];

    // In App\Models\Event.php
    public function participants()
    {
        return $this->belongsToMany(EventGuest::class, 'event_participants', 'event_id', 'guest_id')
            ->withTimestamps();
    }

    /**
     * Get the organizational units associated with this event.
     */
    public function organizationalUnits()
    {
        return $this->belongsToMany(OrganizationalUnit::class, 'event_organizational_unit');
    }
}
