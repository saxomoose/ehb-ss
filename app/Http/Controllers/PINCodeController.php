<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PINCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class PINCodeController extends Controller
{
    public function activate(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            //'data' => 'required|array:pin_code',
            'pin_code' => 'required|integer|digits:6'
        ]);

        if ($validator->fails()) {

            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedAttributes = $validator->validated();
        
        if (!isset($user->pin_code)) {

            return response()->json(['error' => 'The user is not yet registered.'], Response::HTTP_FORBIDDEN);
        }
        
        if ($user->status == -1) {

            return response()->json(['error' => 'The account is deactivated.'], Response::HTTP_FORBIDDEN);
        } else if ($user->status == 1) {

            return response()->json(['error' => 'The account is already active.'], Response::HTTP_FORBIDDEN);
        } else if ($user->status == 0) {
            $diff = $user->pin_code_timestamp->diff(Carbon::now());
            if ($diff->i > 5 && $diff->s > 0) {

                return response()->json(['error' => 'The pin code has expired.'], Response::HTTP_FORBIDDEN);
            } else if ($user->pin_code != $validatedAttributes['pin_code']) {

                return response()->json(['error' => 'The provided pin code is incorrect'], Response::HTTP_UNAUTHORIZED);
            } else {
                $user->status = 1;
                $user->saveQuietly();
                
                return redirect()->route('pin.confirm');
            }
        }
    }

    public function confirm()
    {
        return view('pincode.confirm-activation');
    }
    
    public function reset(User $user)
    {
        if (!isset($user->pin_code)) {
            return response()->json(['error' => 'The user is not yet registered.'], Response::HTTP_FORBIDDEN);
        }

        $diff = $user->pin_code_timestamp->diff(Carbon::now());
        if ($diff->i > 5 && $diff->s > 0) {
            $user->pin_code = random_int(10 ** (6 - 1), (10 ** 6) - 1);
            $user->saveQuietly();
            $user->notify(new PINCodeNotification($user->pin_code, $user->id));

            return response()->noContent();
        } else {
            return response()->json(['error' => 'The pin code has not expired.'], Response::HTTP_FORBIDDEN);
        }
    }
}
