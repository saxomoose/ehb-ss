<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    // The manager of the event.
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // The bank account that belongs to the event.
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    // The sellers that belong to the event.
    public function users()
    {
        return $this->belongsToMany(User::class)
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

    // The categories that belong to the event.
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function items()
    {
        return $this->hasManyThrough(Item::class, Category::class);
    }

    // The transactions that belong to the event.
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
