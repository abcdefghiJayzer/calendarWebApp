<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'desription',
        'start_date',
        'end_date',
        'location',
        'user_id',
        'is_all_day',
        'status'
    ];
}
