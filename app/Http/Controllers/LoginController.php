<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
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

        if ($user->status == 0 && isset($user->pin_code)) {
            return response()->json(['error' => 'An email with an activation link was sent to your mailbox. Please follow the instructions in the email.'], Response::HTTP_FORBIDDEN);
        } 
        // User is active. The user token for the user is created.
        else if ($user->status == 1 && $user->tokens->isEmpty()) {
            $token = $user->createToken($validatedAttributes['device_name'], []);
            $data = ['user_id' => $user->id, 'token' => $token->plainTextToken];
            
            return response()->json(['data' => $data], Response::HTTP_OK);
        } else {
            return response()->json(['error' => 'The user token is already set.'], Response::HTTP_FORBIDDEN);
        }
    }
}
