<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Models\Event;
use App\Models\EventUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventUserController extends Controller
{
    // Seeds the email of a user in the db. The email existence is tested during registration. Admin is able to seed manager, ''. Manager is only able to seed ''.
    public function seed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:email,ability',
            'data.email' => ['required', 'email', Rule::unique('users', 'email'), 'max:255'],
            'data.ability' => ['required', Rule::in(['manager', ''])]
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
            'ability' => $validatedAttributes['ability']
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
    
    /**
     * Events can have 1 manager.
     * Sets the roles in the pivot table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upsert(Request $request, Event $event, User $user)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:ability',
            'data.ability' => ['required', 'string', Rule::in(['manager', 'seller'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $manager = $event->getManager();
        if (isset($manager) && $validatedAttributes['ability'] == 'manager') {
            return response()->json(['error' => 'The event manager is already set'], Response::HTTP_FORBIDDEN);
        }

        EventUser::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['ability' => $validatedAttributes['ability']]
        );

        return response()->json(['data' => "User {$user->name}'s role on event {$event->name} set to {$validatedAttributes['ability']}"], Response::HTTP_CREATED);
    }

    /**
     * Removes the roles from the pivot table.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Event $event, User $user)
    {
        $event->users()->detach($user->id);

        return response()->json(['data' => "{$user->name} removed from event {$event->name}"], Response::HTTP_OK);
    }

    public function transactions(Event $event, User $user)
    {
        $transactions = Transaction::where('user_id', '=', $user->id)
            ->where('event_id', '=', $event->id)
            ->get();

        return TransactionResource::collection($transactions);
    }
}
