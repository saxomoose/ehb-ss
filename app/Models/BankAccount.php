<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    // Required because primary key is uuid.
    //public $incrementing = false;

    protected $guarded = [];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function getManagers()
    {
        $managers = collect();
        foreach($this->events as $event) {
            $managers->push($event->getManager());
        }
        return $managers;
    }


    public function isManagedBy($userId) {
        return $this->getManagers()->pluck('id')->contains($userId);
    }
}
