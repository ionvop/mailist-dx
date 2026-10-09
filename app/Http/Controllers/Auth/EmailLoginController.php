<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendCodeRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Mail\LoginCodeMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailLoginController extends Controller
{
    /**
     * Session key holding the email awaiting code verification.
     */
    public const EMAIL_SESSION_KEY = 'auth.email';

    /**
     * Session key holding an email that has been verified but not yet registered.
     */
    public const VERIFIED_EMAIL_SESSION_KEY = 'auth.verified_email';

    public function __construct(protected OtpService $otp) {}

    /**
     * Show the login page.
     */
    public function show(): View
    {
        return view('auth.login');
    }

    /**
     * Generate a login code and email it to the given address.
     */
    public function send(SendCodeRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        $code = $this->otp->generate($email);

        Mail::to($email)->send(new LoginCodeMail($code));

        $request->session()->put(self::EMAIL_SESSION_KEY, $email);

        return redirect()
            ->route('login.verify')
            ->with('status', 'We sent a login code to your email.');
    }

    /**
     * Show the code verification page.
     */
    public function verify(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_SESSION_KEY);

        if (! $email) {
            return redirect()->route('login');
        }

        return view('auth.verify', ['email' => $email]);
    }

    /**
     * Verify the submitted code and sign the user in.
     */
    public function verifyCode(VerifyCodeRequest $request): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_SESSION_KEY);

        if (! $email) {
            return redirect()->route('login');
        }

        if (! $this->otp->verify($email, $request->validated('code'))) {
            return back()->withErrors(['code' => 'That code is invalid or has expired.']);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $request->session()->put(self::VERIFIED_EMAIL_SESSION_KEY, $email);
            $request->session()->forget(self::EMAIL_SESSION_KEY);

            return redirect()->route('register');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::EMAIL_SESSION_KEY);

        return redirect()->intended(route('home'));
    }
}
