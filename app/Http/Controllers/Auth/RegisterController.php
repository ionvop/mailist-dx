<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * Show the username selection page for a verified email.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(EmailLoginController::VERIFIED_EMAIL_SESSION_KEY);

        if (! $email) {
            return redirect()->route('login');
        }

        return view('auth.register', ['email' => $email]);
    }

    /**
     * Create the account and sign the user in.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $email = $request->session()->get(EmailLoginController::VERIFIED_EMAIL_SESSION_KEY);

        if (! $email) {
            return redirect()->route('login');
        }

        $user = User::create([
            'username' => $request->validated('username'),
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(EmailLoginController::VERIFIED_EMAIL_SESSION_KEY);

        return redirect()->intended(route('home'));
    }
}
