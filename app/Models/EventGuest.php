<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGuest extends Model
{
    use HasFactory;

    protected $fillable = ['email'];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_participants', 'guest_id', 'event_id')
                    ->withTimestamps();
    }
}
