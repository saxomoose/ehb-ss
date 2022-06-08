<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Event;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{
    use HandlesAuthorization;

    public function before(User $user)
    {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Item $item)
    {
        $event = Event::findOrFail($item->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Category $category)
    {
        $event = Event::findOrFail($category->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Item $item)
    {
        $event = Event::findOrFail($item->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Item $item)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Item $item)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Item $item)
    {
        //
    }
}
