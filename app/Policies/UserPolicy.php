<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
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
        return $user->ability == 'coordinator';
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
        if ($user->ability == '') {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->ability == 'coordinator';
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
        return $user->ability == 'coordinator';
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

        if ($model->ability == 'admin') {
            $this->deny('The admin user cannot be updated.');
        } else if ($user->ability == '') {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->ability == 'coordinator';
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

    public function seed(User $user)
    {
        if ($user->ability == 'coordinator') {
            return request()->input('data.ability') == ''
                ? Response::allow()
                : Response::deny('The user is only authorized to seed unprivileged users.');
        }
    }

    public function toggleIsActive(User $user, User $model)
    {
        if ($model->ability == 'admin') {
            $this->deny("The admin user cannot be deactivated.");
        } else {
            return $user->ability == 'coordinator';
        }
    }

    public function viewEvents(User $user, User $model)
    {
        if ($user->ability == '') {
            return $user->id == $model->id
                ? Response::allow()
                : Response::deny('The user is only authorised to access his/her own record(s)');
        } else {
            return $user->ability == 'coordinator';
        }
    }
}
