<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\ItemResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $guard = Auth::getDefaultDriver();
        // $tokenGuard = Auth::guard('sanctum');

        return EventResource::collection(Event::all());
    }

    /**
     * Event can have 1 manager.
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Event names should be unique. Validation is case insensitive because MySQL is case insensitive.
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:name,date,user_id,bank_account_id',
            'data.name' => ['required',  Rule::unique('events', 'name'), 'max:30'],
            'data.date' => 'required|date',
            'data.user_id' => ['required', Rule::exists('users', 'id')],
            'data.bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')]
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $user = User::findOrFail($validatedAttributes['user_id']);
        if ($user->ability != 'manager') {
            
            return response()->json(['data' => "Only managers can manage events."], Response::HTTP_FORBIDDEN);
        }

        $event = Event::create([
            'name' => $validatedAttributes['name'],
            'date' => $validatedAttributes['date'],
            'user_id' => $validatedAttributes['user_id'],
            'bank_account_id' => $validatedAttributes['bank_account_id']
        ]);

        // Given that the relationship is loaded, the bank account will be returned here with the created event.
        return (new EventResource($event))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event)
    {
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:name,date,user_id,bank_account_id',
            'data.name' => ['required',  Rule::unique('events', 'name')->ignore($event->id), 'max:30'],
            'data.date' => 'required|date',
            'data.user_id' => ['required', Rule::exists('users', 'id')],
            'data.bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')]
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $user = User::findOrFail($validatedAttributes['user_id']);
        if ($user->ability != 'manager') {
            
            return response()->json(['data' => "Only managers can manage events."], Response::HTTP_FORBIDDEN);
        }

        $originalAttributes = collect($event->getAttributes())->only(array_keys($validatedAttributes));
        $changedAttributes = collect($validatedAttributes);
        $diff = $changedAttributes->diff($originalAttributes);

        $event->fill($diff->toArray());
        $event->save();

        return (new EventResource($event))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->noContent();
    }

    public function users(Event $event)
    {
        return UserResource::collection($event->users);
    }

    public function categories(Event $event)
    {
        return CategoryResource::collection($event->categories);
    }

    public function items(Event $event)
    {
        return ItemResource::collection($event->items);
    }

    public function transactions(Event $event)
    {
        return TransactionResource::collection($event->transactions);
    }




}
