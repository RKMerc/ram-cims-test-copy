<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MicrosoftController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            // Find or create the user based on their email
            $user = User::firstOrCreate(
                ['email' => $microsoftUser->getEmail()],
                [
                    'name' => $microsoftUser->getName(),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            Auth::login($user);

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Microsoft authentication failed. Please try again.');
        }
    }
}