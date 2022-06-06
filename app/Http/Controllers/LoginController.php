<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Stateless authentication based on sanctum tokens.
class LoginController extends Controller
{
    // Should be called on every app start-up.
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:email,password,pin_code,device_name',
            'data.email' => ['required', 'email', Rule::exists('users', 'email'), 'max:255'],
            'data.password' => 'required',
            'data.device_name' => 'required'
        ]);

        if ($validator->fails()) {
            
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $user = User::with(['tokens'])->firstWhere('email', $validatedAttributes['email']);

        // Checking user credentials.
        if (!isset($user)) {
            
            abort(Response::HTTP_NOT_FOUND);
        } else if (!Hash::check($validatedAttributes['password'], $user->password)) {
            
            return response()->json(['error' => 'The provided credentials are incorrect'], Response::HTTP_UNAUTHORIZED);
        }

        // User is active. The user token for the user is created.
        if ($user->status == 1 && $user->tokens->isEmpty()) {
            $token = $user->createToken($validatedAttributes['device_name'], []);

            return response()->json(['data' => $token->plainTextToken], Response::HTTP_OK);
        } else {
            
            return response()->json(['error' => 'The user token is already set.'], Response::HTTP_FORBIDDEN);
        }
    }
}
