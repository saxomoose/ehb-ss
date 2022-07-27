<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pin_code_timestamp' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'status',
        'ability',
        'pin_code',
        'pin_code_timestamp'
    ];

    // The events managed by the user.
    public function managedEvents()
    {
        return $this->hasMany(Event::class);
    }
    
    // The events that belong to the user.
    public function events()
    {
        return $this->belongsToMany(Event::class)
            ->using(EventUser::class);
    }

    /**
     * This method returns a collection of pivot model instances.
     * @return mixed
     */
    public function roles()
    {
        return $this->hasMany(EventUser::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Determines whether user is a seller at the event.
    public function isSeller($eventId)
    {
        $eventIds = $this->roles->pluck('event_id');
        if (isset($eventIds)) {
            return $eventIds->contains($eventId);
        } else {
            return false;
        }
    }
    
    public function isManager($eventId)
    {
        $eventIds = $this->managedEvents()->pluck('id');
        if (isset($eventIds)) {
            return $eventIds->contains($eventId);
        } else {
            return false;
        }
    }

    public function managesAny()
    {
        $userIds = $this->events->pluck('user_id');
        if (isset($userIds)) {
            return $userIds->contains($this->id);
        } else {
            return false;
        }
    }
}
