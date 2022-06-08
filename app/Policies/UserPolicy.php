<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        if (!$user->is_admin) {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->is_admin;
        }
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
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, User $model)
    {
        
        if ($model->is_admin) {
            $this->deny('The admin user cannot be updated.');
        } 
        // A user is only allowed to update his own profile.
        else if (!$user->is_admin) {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->is_admin;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, User $model)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        //
    }

    public function toggleIsActive(User $user, User $model)
    {
        // Only managers and sellers can be deactivated.
        if ($model->is_admin) {
            $this->deny("The admin user cannot be deactivated.");
        } else {
            return $user->is_admin;
        }
    }

    public function viewEvents(User $user, User $model)
    {
        if (!$user->is_admin) {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->is_admin;
        }
    }
}
