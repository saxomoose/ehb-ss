<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function before(User $user)
    {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Reserved to admin and coordinators given that method is not scoped to an event.
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
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Category $category)
    {
        $event = Event::findOrFail($category->event_id);

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
    public function create(User $user, Event $event)
    {
        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Category $category)
    {
        $event = Event::findOrFail($category->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Category $category)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Category $category)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Category $category)
    {
        //
    }

    public function viewItems(User $user, Category $category)
    {
        $event = Event::findOrFail($category->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }
}
