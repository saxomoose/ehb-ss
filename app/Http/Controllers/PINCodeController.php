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

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        }

        $validatedAttributes = $validator->validated();
        
        if (!isset($user->pin_code)) {
            $message = ['message' => 'The user is not yet registered.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        }
        
        if ($user->status == -1) {
            $message = ['message' => 'The account is deactivated.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        } else if ($user->status == 1) {
            $message = ['message' => 'The account is already active.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        } else if ($user->status == 0) {
            $diff = $user->pin_code_timestamp->diff(Carbon::now());
            if ($diff->i > 5 && $diff->s > 0) {
                $message = ['message' => 'The pin code has expired.'];

                return redirect()->action([PINCodeController::class, 'confirm'], $message);
            } else if ($user->pin_code != $validatedAttributes['pin_code']) {
                $message = ['message' => 'The provided pin code is incorrect'];

                return redirect()->action([PINCodeController::class, 'confirm'], $message);
            } else {
                $user->status = 1;
                $user->saveQuietly();
                $message = ['message' => 'Your account is now active. You can now log in from the app.'];
                
                return redirect()->action([PINCodeController::class, 'confirm'], $message);
            }
        }
    }

    public function confirm(Request $request)
    {
        $message = $request->query();
        return view('pincode.confirm-activation', $message);
    }
    
    public function reset(User $user)
    {
        if (!isset($user->pin_code)) {
            $message = ['message' => 'The user is not yet registered.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        }

        $diff = $user->pin_code_timestamp->diff(Carbon::now());
        if ($diff->i > 5 && $diff->s > 0) {
            $user->pin_code = random_int(10 ** (6 - 1), (10 ** 6) - 1);
            $user->pin_code_timestamp = Carbon::now();
            $user->saveQuietly();
            $user->notify(new PINCodeNotification($user->pin_code, $user->id));
            $message = ['message' => 'The activation process has been reset. Check your mailbox for a new email.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        } else {
            $message = ['message' => 'The pin code has not expired.'];

            return redirect()->action([PINCodeController::class, 'confirm'], $message);
        }
    }
}
