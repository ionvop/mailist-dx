<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Mail\LoginCodeMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Email login
|--------------------------------------------------------------------------
*/

test('the login page renders for a guest', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Send me a login code');
});

test('sending a code emails it and redirects to verification', function () {
    Mail::fake();

    $this->mock(OtpService::class, function ($mock) {
        $mock->shouldReceive('generate')->once()->with('user@example.com')->andReturn('123456');
    });

    $this->post(route('login.email'), ['email' => 'user@example.com'])
        ->assertRedirect(route('login.verify'))
        ->assertSessionHas(EmailLoginController::EMAIL_SESSION_KEY, 'user@example.com');

    Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) {
        return $mail->hasTo('user@example.com') && $mail->code === '123456';
    });
});

test('sending a code requires a valid email', function () {
    Mail::fake();

    $this->post(route('login.email'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

test('the verification page requires a pending email', function () {
    $this->get(route('login.verify'))->assertRedirect(route('login'));
});

test('the verification page renders with a pending email', function () {
    $this->withSession([EmailLoginController::EMAIL_SESSION_KEY => 'user@example.com'])
        ->get(route('login.verify'))
        ->assertOk()
        ->assertSee('user@example.com');
});

test('a valid code signs an existing user in', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->mock(OtpService::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->with('user@example.com', '123456')->andReturn(true);
    });

    $this->withSession([EmailLoginController::EMAIL_SESSION_KEY => 'user@example.com'])
        ->post(route('login.verify.submit'), ['code' => '123456'])
        ->assertRedirect(route('home'));

    expect(Auth::id())->toBe($user->id);
});

test('a valid code for a new email redirects to registration', function () {
    $this->mock(OtpService::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });

    $this->withSession([EmailLoginController::EMAIL_SESSION_KEY => 'new@example.com'])
        ->post(route('login.verify.submit'), ['code' => '123456'])
        ->assertRedirect(route('register'))
        ->assertSessionHas(EmailLoginController::VERIFIED_EMAIL_SESSION_KEY, 'new@example.com');

    expect(Auth::check())->toBeFalse();
});

test('an invalid code returns an error', function () {
    $this->mock(OtpService::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(false);
    });

    $this->withSession([EmailLoginController::EMAIL_SESSION_KEY => 'user@example.com'])
        ->post(route('login.verify.submit'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(Auth::check())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

test('the registration page requires a verified email', function () {
    $this->get(route('register'))->assertRedirect(route('login'));
});

test('a verified email can create an account', function () {
    $this->withSession([EmailLoginController::VERIFIED_EMAIL_SESSION_KEY => 'new@example.com'])
        ->post(route('register.store'), ['username' => 'new_user'])
        ->assertRedirect(route('home'));

    $this->assertDatabaseHas('users', [
        'username' => 'new_user',
        'email' => 'new@example.com',
    ]);

    expect(Auth::check())->toBeTrue();
});

test('registration rejects a duplicate username', function () {
    User::factory()->create(['username' => 'taken']);

    $this->withSession([EmailLoginController::VERIFIED_EMAIL_SESSION_KEY => 'new@example.com'])
        ->post(route('register.store'), ['username' => 'taken'])
        ->assertSessionHasErrors('username');
});

test('registration rejects an invalid username', function () {
    $this->withSession([EmailLoginController::VERIFIED_EMAIL_SESSION_KEY => 'new@example.com'])
        ->post(route('register.store'), ['username' => 'no spaces!'])
        ->assertSessionHasErrors('username');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

test('a signed-in user can log out', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('logout'))->assertRedirect(route('home'));

    expect(Auth::check())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Google
|--------------------------------------------------------------------------
*/

test('google redirect sends the user to google', function () {
    Socialite::shouldReceive('driver->redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    $this->get(route('auth.google.redirect'))
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('a google callback signs an existing user in', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->email = 'user@example.com';

    Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    expect(Auth::id())->toBe($user->id);
});

test('a google callback for a new email redirects to registration', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->email = 'new@example.com';

    Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('register'))
        ->assertSessionHas(EmailLoginController::VERIFIED_EMAIL_SESSION_KEY, 'new@example.com');
});

test('a failed google callback redirects back to login', function () {
    Socialite::shouldReceive('driver->user')->once()->andThrow(new RuntimeException('nope'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});
