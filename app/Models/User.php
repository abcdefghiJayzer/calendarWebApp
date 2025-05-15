<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'division',
        'is_division_head',
        'organizational_unit_id',
        'google_calendar_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'selected_calendar_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_division_head' => 'boolean', // Add this line
    ];

    /**
     * Check if user can create events in the specified division/calendar type
     *
     * @param string $calendarType
     * @return bool
     */
    public function canCreateEventsIn($calendarType)
    {
        // Institute users can create events anywhere
        if ($this->division === 'institute') {
            return true;
        }

        // Division heads can create events in their sector and division
        if ($this->is_division_head) {
            // Extract sector from division (e.g., 'sector1_div1' -> 'sector1')
            $userSector = explode('_', $this->division)[0];

            // Check if calendar type matches the user's division or their sector
            return $this->division === $calendarType || $userSector === $calendarType;
        }

        // Regular users can only create events in their own division
        return $this->division === $calendarType;
    }

    /**
     * Get the organizational unit that the user belongs to.
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    // Role-related methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isSectorHead()
    {
        return $this->role === 'sector_head';
    }

    public function isDivisionHead()
    {
        return $this->role === 'division_head';
    }

    public function isEmployee()
    {
        return $this->role === 'employee';
    }
}
