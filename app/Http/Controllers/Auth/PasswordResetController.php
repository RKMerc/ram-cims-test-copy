<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PortalGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = PortalGrant::normalize($validated['email']);

        if (! PortalGrant::allowsEmail($email)) {
            return back()->withErrors([
                'email' => 'Use an APC email or a Gmail address.',
            ])->onlyInput('email');
        }

        Password::sendResetLink(['email' => $email]);

        return back()->with('status', 'If that account exists, a password reset link has been sent.');
    }

    public function edit(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = Password::reset(
            [
                'email' => PortalGrant::normalize($validated['email']),
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function ($user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        return redirect()->route('login')->with('status', 'Your password was reset. Sign in with the new password.');
    }
}
