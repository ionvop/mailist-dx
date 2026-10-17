<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every navigation page renders for a guest', function (string $route) {
    $this->get(route($route))->assertOk();
})->with([
    'home',
    'search',
    'feed',
    'list',
    'profile',
    'login',
    'settings',
]);

test('the notifications page requires authentication', function () {
    $this->get(route('notifications'))
        ->assertRedirect(route('login'));
});

test('every navigation page renders for a signed-in user', function (string $route) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))->assertOk();
})->with([
    'home',
    'search',
    'feed',
    'list',
    'profile',
    'settings',
    'notifications',
]);

test('the login page redirects a signed-in user', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('login'))->assertRedirect();
});

test('the guest chrome links to login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('login'), escape: false)
        ->assertDontSee(route('profile'), escape: false);
});

test('the signed-in chrome links to the profile', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('profile'), escape: false);
});
