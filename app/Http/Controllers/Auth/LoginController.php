<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ClinicAccess;
use App\Support\PortalGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function redirectToMicrosoft()
    {
        if (! config('services.microsoft.client_id') || ! config('services.microsoft.client_secret')) {
            return redirect()->route('login')->with('error', 'Microsoft sign-in is not configured yet. Use your email and password, or Google.');
        }

        return Socialite::driver('microsoft')->redirect();
    }

    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Microsoft authentication failed. Please try again.');
        }

        return $this->loginFromIdentity($microsoftUser->getEmail(), $microsoftUser->getName());
    }

    public function redirectToGoogle()
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->with('error', 'Google sign-in is not configured yet. Use your email and password, or Microsoft.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with([
                'prompt' => 'select_account consent',
                'access_type' => 'online',
            ])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Google authentication failed. Please try again.');
        }

        return $this->loginFromIdentity($googleUser->getEmail(), $googleUser->getName());
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = PortalGrant::normalize($credentials['email']);

        if (! PortalGrant::allowsEmail($email)) {
            return back()->withErrors([
                'email' => 'Use an APC email or a Gmail address.',
            ])->onlyInput('email');
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $credentials['password']])) {
            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->startClinicSession($user->email, $user->name);

        return redirect()->intended('/dashboard');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $email = PortalGrant::normalize($validated['email']);

        if (! PortalGrant::allowsEmail($email)) {
            return back()->withErrors([
                'email' => 'Create an account with an APC email or a Gmail address.',
            ])->onlyInput('name', 'email');
        }

        if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
            return back()->withErrors([
                'email' => 'An account with that email already exists. Log in instead.',
            ])->onlyInput('name', 'email');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->startClinicSession($email, $validated['name']);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function switchView(Request $request)
    {
        $choice = $request->validate([
            'view' => 'required|in:student,staff',
        ])['view'];

        abort_unless(PortalGrant::rememberView($request->user()?->email, $choice), 403);

        return back(fallback: route('dashboard'));
    }

    private function loginFromIdentity(?string $email, ?string $name)
    {
        $email = PortalGrant::normalize($email);

        if ($email === '' || ! PortalGrant::allowsEmail($email)) {
            return redirect()->route('login')->with('error', 'Use an APC email or a Gmail address to sign in.');
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            $user = User::create([
                'name' => $name ?: 'APC User',
                'email' => $email,
                'password' => Str::password(32),
            ]);
        } elseif ($name) {
            $user->forceFill(['name' => $name])->save();
        }

        Auth::login($user);
        request()->session()->regenerate();
        $this->startClinicSession($email, $name ?: $user->name);

        return redirect()->intended('/dashboard');
    }

    private function startClinicSession(string $email, ?string $name): void
    {
        $account = null;

        try {
            $account = PortalGrant::provision($email, $name);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($account) {
            app(ClinicAccess::class)->remember($account);
        }

        if (PortalGrant::isStaffOnly($email)) {
            session(['clinic.portal_view' => 'staff']);
        } elseif (PortalGrant::isDualView($email)) {
            session(['clinic.portal_view' => 'student']);
        }
    }
}
