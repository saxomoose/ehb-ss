<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EventUserPolicy
{
    use HandlesAuthorization;

    public function before(User $user)
    {
        if ($user->ability == 'admin') {
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
     * @param  \App\Models\EventUser  $eventUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, EventUser $eventUser)
    {
        //
    }

    public function upsert(User $user, Event $event)
    {
        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('Only the manager of this event can perform this action.');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EventUser  $eventUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, EventUser $eventUser)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EventUser  $eventUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Event $event)
    {
        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('Only the manager of this event can perform this action.');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EventUser  $eventUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, EventUser $eventUser)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EventUser  $eventUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, EventUser $eventUser)
    {
        //
    }

    public function seedSeller(User $user, Event $event)
    {
        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('Only the manager of this event can perform this action.');
    }

    public function viewTransactions(User $user, Event $event, User $model)
    {        
        if ($user->isSeller($event->id)) {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorized to access his/her own record(s)');
        } else if ($user->isManager($event->id)) {
            $this->allow();
        }
    }
}
