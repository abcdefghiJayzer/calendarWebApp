<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationalUnit extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parent_id'
    ];

    /**
     * Get the parent unit of this organizational unit.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'parent_id');
    }

    /**
     * Get the child units of this organizational unit.
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'parent_id');
    }

    /**
     * Get the users in this organizational unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the events associated with this organizational unit.
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_organizational_unit');
    }
} 