<?php

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\UserFollowed;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

test('a guest cannot update notification preferences', function () {
    $this->patch(route('settings.notifications.update'), ['preferences' => ['user_followed' => false]])
        ->assertRedirect(route('login'));
});

test('the settings page shows the notification preferences to a signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings'))
        ->assertOk()
        ->assertSee('Notifications')
        ->assertSee('Save preferences')
        ->assertSee('Chart comments');
});

test('the settings page shows a notifications placeholder to a guest', function () {
    $this->get(route('settings'))
        ->assertOk()
        ->assertSee('Sign in to choose which activity sends you a notification.');
});

/*
|--------------------------------------------------------------------------
| Saving preferences
|--------------------------------------------------------------------------
*/

test('a user can disable a notification type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.notifications.update'), [
            'preferences' => [
                'user_followed' => false,
                'chart_comment' => true,
            ],
        ])
        ->assertRedirect(route('settings'))
        ->assertSessionHas('status');

    expect($user->refresh()->wantsNotification('user_followed'))->toBeFalse();
    expect($user->wantsNotification('chart_comment'))->toBeTrue();
});

test('a user can re-enable a disabled notification type', function () {
    $user = User::factory()->create([
        'notification_preferences' => ['user_followed' => false],
    ]);

    $this->actingAs($user)
        ->patch(route('settings.notifications.update'), [
            'preferences' => ['user_followed' => true],
        ])
        ->assertRedirect(route('settings'));

    expect($user->refresh()->wantsNotification('user_followed'))->toBeTrue();
});

test('unmentioned notification types are disabled when saving', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.notifications.update'), [
            'preferences' => ['chart_comment' => true],
        ])
        ->assertRedirect(route('settings'));

    $preferences = $user->refresh()->notification_preferences;

    expect($preferences['chart_comment'])->toBeTrue();
    expect($preferences['user_followed'])->toBeFalse();
});

test('unknown preference keys are ignored', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.notifications.update'), [
            'preferences' => ['not_a_type' => true],
        ])
        ->assertRedirect(route('settings'));

    $preferences = $user->refresh()->notification_preferences;

    expect($preferences)->not->toHaveKey('not_a_type');
    expect($preferences)->toHaveCount(count(NotificationType::cases()));
});

test('preferences must be an array', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.notifications.update'), ['preferences' => 'nope'])
        ->assertSessionHasErrors('preferences');
});

/*
|--------------------------------------------------------------------------
| Delivery filtering
|--------------------------------------------------------------------------
*/

test('a disabled notification type is not delivered', function () {
    $recipient = User::factory()->create([
        'notification_preferences' => ['user_followed' => false],
    ]);
    $follower = User::factory()->create();

    $follower->following()->attach($recipient);

    $recipient->notify(new UserFollowed($follower->username));

    expect($recipient->notifications()->count())->toBe(0);
});

test('an enabled notification type is delivered', function () {
    $recipient = User::factory()->create();
    $follower = User::factory()->create();

    $follower->following()->attach($recipient);

    $recipient->notify(new UserFollowed($follower->username));

    expect($recipient->notifications()->count())->toBe(1);
});
