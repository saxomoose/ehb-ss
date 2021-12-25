<?php

namespace App\Http\Controllers;

use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class EventUserTokenController extends Controller
{
    // public function refresh()
    // {
    // }

    // Should be called on every app start-up.
    public function purge(Request $request)
    {
        $currentAccessToken = $request->user()->currentAccessToken();
        if ($currentAccessToken->tokenable_type == 'App\Models\EventUser') {
            $currentAccessToken->delete();

            return response()->noContent();
        } else {
            return response()->json(['error' => 'This type of token cannot be purged'], Response::HTTP_FORBIDDEN);
        }
    }

    // An role (eventuser) token is created per event to which the user belongs. Should be called on every app start-up.
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:device_name',
            'data.device_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $user = $request->user();
        $tokens = [];
        if ($user->roles()->exists()) {
            $user->load('roles'); // Lazy eager loading.
            foreach ($user->roles as $role) {
                $roleToken = $role->createToken($validatedAttributes['device_name'], ["{$role->ability}"]);
                $token = new Token("role_token", $role->event_id, $roleToken->plainTextToken); // Role tokens are identified client-side by event id.
                array_push($tokens, $token);
            }

            return response()->json(['data' => $tokens], Response::HTTP_OK);
        } else {
            return response()->json(['error' => 'The user does not belong to any event.'], Response::HTTP_NOT_FOUND);
        }
    }
}
