<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
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
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Transaction $transaction)
    {
        $event = Event::findOrFail($transaction->event_id);
        
        if ($user->isSeller($event->id)) {
            return $user->id == $transaction->user_id
            ? Response::allow()
            : Response::deny('The user is only authorised to access his/her own record(s)');
        } else if ($user->isManager($event->id)) {
            $this->allow();
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Event $event)
    {
        if ($user->isSeller($event->id || $user->isManager($event->id))) {
            $this->allow();
        } else {
            $this->deny('The user does not belong to this event.');
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Transaction $transaction)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Transaction $transaction)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Transaction $transaction)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Transaction $transaction)
    {
        //
    }

    public function viewItems(User $user, Transaction $transaction)
    {
        $event = Event::findOrFail($transaction->event_id);
        
        if ($user->isSeller($event->id)) {
            return $user->id == $transaction->user_id
            ? Response::allow()
            : Response::deny('The user is only authorised to access his/her own record(s)');
        } else if ($user->isManager($event->id)) {
            $this->allow();
        }
    }

    public function toggleStatus(User $user, Transaction $transaction)
    {
        $event = Event::findOrFail($transaction->event_id);

        return $user->isManager($event->id)
            ? Response::allow()
            : Response::deny('The user is not the manager of this event.');
    }
}
