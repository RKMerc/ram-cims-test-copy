<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmEmailMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ClinicAccess;
use App\Support\MicrosoftSignIn;
use App\Support\PortalGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
            return redirect()->route('login')->with('error', 'Microsoft sign-in is not configured yet. Use your email and password.');
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

        return app(MicrosoftSignIn::class)->fromCallback($microsoftUser);
    }

    public function showLink()
    {
        abort_unless(session()->has('microsoft.pending_link'), 404);

        return view('auth.link-microsoft');
    }

    public function confirmLink(Request $request, MicrosoftSignIn $signIn)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        return $signIn->confirmPending($validated['password']);
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::query()->findOrFail($id);
        abort_unless(hash_equals($hash, sha1($user->email)), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
            AuditLog::record('email.verified', $user, [], $user->id);
        }

        return redirect()->route('login')->with('status', 'Email confirmed. You can sign in.');
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'This email is already confirmed.');
        }

        $this->sendVerificationNotice($user);

        return back()->with('success', 'A confirmation link was sent to your email.');
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
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
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
        $this->sendVerificationNotice($user);

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

    private function sendVerificationNotice(User $user): void
    {
        try {
            $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]);

            Mail::to($user->email)->send(new ConfirmEmailMail($url));
        } catch (\Throwable $exception) {
            report($exception);
        }
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
