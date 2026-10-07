<?php

use App\Support\DevAuth;

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
    'notifications',
]);

test('every navigation page renders for a signed-in user', function (string $route) {
    DevAuth::toggle();

    $this->get(route($route))->assertOk();
})->with([
    'home',
    'search',
    'feed',
    'list',
    'profile',
    'login',
    'settings',
    'notifications',
]);

test('the guest chrome links to login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('login'), escape: false)
        ->assertDontSee(route('profile'), escape: false);
});

test('the signed-in chrome links to the profile', function () {
    DevAuth::toggle();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('profile'), escape: false);
});

test('the dev auth toggle flips the auth state', function () {
    expect(DevAuth::check())->toBeFalse();

    $this->post(route('dev.auth.toggle'))->assertRedirect();

    expect(DevAuth::check())->toBeTrue();

    $this->post(route('dev.auth.toggle'))->assertRedirect();

    expect(DevAuth::check())->toBeFalse();
});
