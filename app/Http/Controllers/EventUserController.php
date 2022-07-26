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
    public function seedSeller(Request $request)
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
            'email' => $validatedAttributes['email'],
            'ability' => 'seller'
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upsert(Request $request, Event $event, User $user)
    {
        if ($user->ability != 'seller') {
            
            return response()->json(['data' => "Only sellers can be added to events."], Response::HTTP_FORBIDDEN);
        }
        
        EventUser::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id]
        );

        return response()->json(['data' => "User {$user->name}'s added to event {$event->name}"], Response::HTTP_CREATED);
    }

    /**
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
