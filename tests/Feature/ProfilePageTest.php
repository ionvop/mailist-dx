<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Bio
|--------------------------------------------------------------------------
*/

test('the profile page renders the bio as markdown', function () {
    $user = User::factory()->create(['bio' => 'I make **maimai** charts.']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('<strong>maimai</strong>', false);
});

test('the profile page shows a placeholder when the bio is empty', function () {
    $user = User::factory()->create(['bio' => null]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Markdown-supported biography.');
});

test('the profile page strips raw html from the bio', function () {
    $user = User::factory()->create(['bio' => 'Hello <script>alert(1)</script> world']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee('<script>', false);
});

test('a guest sees the bio placeholder', function () {
    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('Markdown-supported biography.');
});

/*
|--------------------------------------------------------------------------
| Public profiles
|--------------------------------------------------------------------------
*/

test('a guest can view a user profile by username', function () {
    $user = User::factory()->create(['username' => 'chartmaker', 'bio' => 'Hello there']);

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertSee('chartmaker')
        ->assertSee('Hello there');
});

test('a public profile hides the email of another user', function () {
    $user = User::factory()->create(['username' => 'otherguy', 'email' => 'secret@example.com']);

    $this->actingAs(User::factory()->create())
        ->get(route('users.show', $user))
        ->assertOk()
        ->assertDontSee('secret@example.com');
});

test('an unknown username returns not found', function () {
    $this->get('/users/does-not-exist')->assertNotFound();
});
