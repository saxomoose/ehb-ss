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
            'pin_code' => 'required|integer|digits:6'
        ]);

        if ($validator->fails()) {
            $message = ['message' => 'Validation failed.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedAttributes = $validator->validated();
        
        if (!isset($user->pin_code)) {
            $message = ['message' => 'The user is not yet registered.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
        }
        
        if ($user->status == -1) {
            $message = ['message' => 'The account is deactivated.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
        } else if ($user->status == 1) {
            $message = ['message' => 'The account is already active.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
        } else if ($user->status == 0) {
            $diff = $user->pin_code_timestamp->diff(Carbon::now());
            if ($diff->i > 5 && $diff->s > 0) {
                $message = ['message' => 'The pin code has expired.'];

                return response()
                    ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
            } else if ($user->pin_code != $validatedAttributes['pin_code']) {
                $message = ['message' => 'The provided pin code is incorrect'];

                return response()
                    ->view('pincode.message', $message, Response::HTTP_UNAUTHORIZED);
            } else {
                $user->status = 1;
                $user->saveQuietly();
                $message = ['message' => 'Your account is now active. You can now log in the app.'];
                
                return view('pincode.message', $message);
            }
        }
    }
    
    public function reset(User $user)
    {
        if (!isset($user->pin_code)) {
            $message = ['message' => 'The user is not yet registered.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
        }

        $diff = $user->pin_code_timestamp->diff(Carbon::now());
        if ($diff->i > 5 && $diff->s > 0) {
            $user->pin_code = random_int(10 ** (6 - 1), (10 ** 6) - 1);
            $user->pin_code_timestamp = Carbon::now();
            $user->saveQuietly();
            $user->notify(new PINCodeNotification($user->pin_code, $user->id));
            $message = ['message' => 'The activation process has been reset. Check your mailbox for a new email.'];

            return view('pincode.message', $message);
        } else {
            $message = ['message' => 'The pin code has not expired.'];

            return response()
                ->view('pincode.message', $message, Response::HTTP_FORBIDDEN);
        }
    }
}
