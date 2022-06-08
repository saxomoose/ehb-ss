<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\PINCodeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * This class was created with php artisan make:controller UserController --model User --api
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return UserResource::collection(User::all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Only possible to update name and email.
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:name,email',
            'data.name' => 'required|max:255',
            'data.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id), 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedAttributes = $validator->validated();

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $originalAttributes = collect($user->getAttributes())->only(array_keys($validatedAttributes));
        $changedAttributes = collect($validatedAttributes);
        $diff = $changedAttributes->diff($originalAttributes);

        $user->fill($diff->toArray());
        $user->save();

        // If user email is updated, new register process is initiated.
        if ($diff->has('email')) {
            $user->pin_code = random_int(10 ** (6 - 1), (10 ** 6) - 1);
            $user->pin_code_timestamp = Carbon::now();
            $user->saveQuietly();
            $user->notify(new PINCodeNotification($user->pin_code, $user->id));
        }

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }

    public function seedManager(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:email',
            'data.email' => ['required', 'email', Rule::unique('users', 'email'), 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedAttributes = $validator->validated();

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $user = User::create([
            'id' => (string) Str::uuid(),
            'email' => $validatedAttributes['email'],
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function toggleIsActive(User $user)
    {
        if ($user->status == 1) {
            $user->status = -1;
            $user->pin_code = null;
            $user->pin_code_timestamp = null;
            $user->save();
            $user->tokens()->delete();

            return response()->noContent();
        } else {
            $user->status = 1;
            $user->save();

            return response()->noContent();
        }
    }

    // Since Eloquent provides "dynamic relationship properties", relationship methods are accessed as if they were defined as properties on the model.
    public function events(User $user)
    {
        return EventResource::collection($user->events);
    }

    // TODO.
    /* public function transactions(User $user)
    {
        return TransactionResource::collection($user->transactions);
    } */
}
