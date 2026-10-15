<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Follow / unfollow
|--------------------------------------------------------------------------
*/

test('a guest cannot follow a user', function () {
    $user = User::factory()->create();

    $this->post(route('users.follow.store', $user))
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('follows', 0);
});

test('a user can follow another user', function () {
    $follower = User::factory()->create();
    $followed = User::factory()->create();

    $this->actingAs($follower)
        ->post(route('users.follow.store', $followed))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('follows', [
        'follower_id' => $follower->id,
        'followed_id' => $followed->id,
    ]);

    expect($followed->refresh()->isFollowedBy($follower))->toBeTrue();
});

test('following a user twice does not create duplicates', function () {
    $follower = User::factory()->create();
    $followed = User::factory()->create();

    $this->actingAs($follower)->post(route('users.follow.store', $followed))->assertRedirect();
    $this->actingAs($follower)->post(route('users.follow.store', $followed))->assertRedirect();

    $this->assertDatabaseCount('follows', 1);
});

test('a user can unfollow another user', function () {
    $follower = User::factory()->create();
    $followed = User::factory()->create();
    $follower->following()->attach($followed->id);

    $this->actingAs($follower)
        ->delete(route('users.follow.destroy', $followed))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseCount('follows', 0);

    expect($followed->refresh()->isFollowedBy($follower))->toBeFalse();
});

test('a user cannot follow themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.follow.store', $user))
        ->assertNotFound();

    $this->assertDatabaseCount('follows', 0);
});

test('a user cannot unfollow themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('users.follow.destroy', $user))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Profile counts
|--------------------------------------------------------------------------
*/

test('the profile page shows the following and follower counts', function () {
    $user = User::factory()->create();
    $user->following()->attach(User::factory()->count(2)->create());
    $user->followers()->attach(User::factory()->count(3)->create());

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('2</span> Following', false)
        ->assertSee('3</span> Followers', false);
});

test('the public profile links to the follower and following pages', function () {
    $user = User::factory()->create();

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertSee(route('users.followers', $user), false)
        ->assertSee(route('users.following', $user), false);
});

/*
|--------------------------------------------------------------------------
| Follower and following pages
|--------------------------------------------------------------------------
*/

test('a guest can view a user followers page', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create(['username' => 'loyal_fan']);
    $user->followers()->attach($follower->id);

    $this->get(route('users.followers', $user))
        ->assertOk()
        ->assertSee('loyal_fan');
});

test('a guest can view a user following page', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create(['username' => 'followed_guy']);
    $user->following()->attach($followed->id);

    $this->get(route('users.following', $user))
        ->assertOk()
        ->assertSee('followed_guy');
});

test('the followers page only lists followers', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create(['username' => 'a_follower']);
    $followed = User::factory()->create(['username' => 'a_followed']);
    $user->followers()->attach($follower->id);
    $user->following()->attach($followed->id);

    $this->get(route('users.followers', $user))
        ->assertOk()
        ->assertSee('a_follower')
        ->assertDontSee('a_followed');
});

test('the following page only lists followed users', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create(['username' => 'b_follower']);
    $followed = User::factory()->create(['username' => 'b_followed']);
    $user->followers()->attach($follower->id);
    $user->following()->attach($followed->id);

    $this->get(route('users.following', $user))
        ->assertOk()
        ->assertSee('b_followed')
        ->assertDontSee('b_follower');
});

test('the followers page shows an empty state', function () {
    $user = User::factory()->create();

    $this->get(route('users.followers', $user))
        ->assertOk()
        ->assertSee('No followers yet');
});

test('the following page shows an empty state', function () {
    $user = User::factory()->create();

    $this->get(route('users.following', $user))
        ->assertOk()
        ->assertSee('Not following anyone yet');
});

test('the followers page shows a follow button for a user not yet followed', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create();
    $user->followers()->attach($follower->id);

    $this->actingAs(User::factory()->create())
        ->get(route('users.followers', $user))
        ->assertOk()
        ->assertSee(route('users.follow.store', $follower), false);
});

test('the followers page shows the following state for a followed user', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create();
    $user->followers()->attach($follower->id);

    $viewer = User::factory()->create();
    $viewer->following()->attach($follower->id);

    $this->actingAs($viewer)
        ->get(route('users.followers', $user))
        ->assertOk()
        ->assertSee(route('users.follow.destroy', $follower), false);
});

test('the followers page hides the follow button on your own row', function () {
    $user = User::factory()->create();
    $user->followers()->attach(User::factory()->create());

    $this->actingAs($user)
        ->get(route('users.followers', $user))
        ->assertOk()
        ->assertDontSee('action="'.route('users.follow.store', $user).'"', false);
});
