<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

test('a guest cannot update a profile', function () {
    $this->patch(route('settings.profile.update'), ['bio' => 'Hello'])
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Bio
|--------------------------------------------------------------------------
*/

test('the settings page shows the profile form to a signed-in user', function () {
    $user = User::factory()->create(['username' => 'sauce_hunter']);

    $this->actingAs($user)
        ->get(route('settings'))
        ->assertOk()
        ->assertSee('sauce_hunter')
        ->assertSee('Save changes');
});

test('the settings page shows a placeholder to a guest', function () {
    $this->get(route('settings'))
        ->assertOk()
        ->assertSee('Sign in to edit your avatar, username, and bio.');
});

test('a user can update their bio', function () {
    $user = User::factory()->create(['bio' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['bio' => 'I make maimai charts.'])
        ->assertRedirect(route('settings'))
        ->assertSessionHas('status');

    expect($user->refresh()->bio)->toBe('I make maimai charts.');
});

test('the bio may not exceed the maximum length', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['bio' => str_repeat('a', 5001)])
        ->assertSessionHasErrors('bio');
});

/*
|--------------------------------------------------------------------------
| Avatar
|--------------------------------------------------------------------------
*/

test('a user can upload an avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar_path' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(route('settings'));

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('uploading a new avatar replaces the previous one', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar_path' => null]);

    $this->actingAs($user)->patch(route('settings.profile.update'), [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ]);

    $firstPath = $user->refresh()->avatar_path;

    $this->actingAs($user)->patch(route('settings.profile.update'), [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ]);

    $secondPath = $user->refresh()->avatar_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('an invalid avatar file is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Username cooldown
|--------------------------------------------------------------------------
*/

test('a user can change their username and the change time is recorded', function () {
    $user = User::factory()->create(['username' => 'old_name', 'username_changed_at' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'new_name'])
        ->assertRedirect(route('settings'));

    $user->refresh();

    expect($user->username)->toBe('new_name')
        ->and($user->username_changed_at)->not->toBeNull();
});

test('a username change within the cooldown is silently ignored', function () {
    $user = User::factory()->create([
        'username' => 'old_name',
        'username_changed_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'new_name'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->username)->toBe('old_name');
});

test('a username change is allowed after the cooldown elapses', function () {
    $user = User::factory()->create([
        'username' => 'old_name',
        'username_changed_at' => now()->subMinutes(User::USERNAME_CHANGE_COOLDOWN_MINUTES + 1),
    ]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'new_name'])
        ->assertRedirect(route('settings'));

    expect($user->refresh()->username)->toBe('new_name');
});

test('a username that is already taken is rejected', function () {
    User::factory()->create(['username' => 'taken_name']);
    $user = User::factory()->create(['username' => 'my_name', 'username_changed_at' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'taken_name'])
        ->assertSessionHasErrors('username');

    expect($user->refresh()->username)->toBe('my_name');
});

test('a username with invalid characters is rejected', function () {
    $user = User::factory()->create(['username' => 'my_name', 'username_changed_at' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'not valid!'])
        ->assertSessionHasErrors('username');
});

test('keeping the same username does not start a cooldown', function () {
    $user = User::factory()->create(['username' => 'my_name', 'username_changed_at' => null]);

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['username' => 'my_name'])
        ->assertRedirect(route('settings'));

    expect($user->refresh()->username_changed_at)->toBeNull();
});
