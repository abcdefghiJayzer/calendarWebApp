<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventParticipant extends Pivot
{
    use HasFactory;

    protected $table = 'event_participants';

    protected $fillable = ['event_id', 'guest_id'];
}
