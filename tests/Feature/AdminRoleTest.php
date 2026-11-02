<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests must sign in before opening or submitting the admin panel', function () {
    $this->get(route('admin.roles.index'))
        ->assertRedirect(route('login'));

    $this->post(route('admin.roles.store'), [
        'username' => 'target',
        'role' => UserRole::Approver->value,
    ])->assertRedirect(route('login'));
});

test('only the exact configured email can access the admin panel', function () {
    $target = User::factory()->create(['role' => UserRole::Member]);
    $admin = User::factory()->create(['email' => 'Admin@example.com']);

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertNotFound();

    $this->post(route('admin.roles.store'), [
        'username' => $target->username,
        'role' => UserRole::Moderator->value,
    ])->assertNotFound();

    expect($target->fresh()->role)->toBe(UserRole::Member);
});

test('the admin panel is unavailable when no admin email is configured', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);

    config(['admin.email' => null]);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertNotFound();
});

test('the configured admin can open the unlinked role panel', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $approver = User::factory()->approver()->create();
    $moderator = User::factory()->moderator()->create();
    $member = User::factory()->create(['role' => UserRole::Member]);

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Manage user roles')
        ->assertSee('Username')
        ->assertSee('Approver')
        ->assertSee('Moderator');

    $this->get(route('admin.roles.index'))
        ->assertSee($approver->username)
        ->assertSee($moderator->username)
        ->assertDontSee($member->username);

    $this->get(route('home'))
        ->assertDontSee(route('admin.roles.index'));
});

test('the configured admin can assign either allowed role', function (UserRole $role) {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $target = User::factory()->create(['role' => UserRole::Member]);

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => $target->username,
            'role' => $role->value,
        ])
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('status');

    expect($target->fresh()->role)->toBe($role);
})->with([
    'approver' => UserRole::Approver,
    'moderator' => UserRole::Moderator,
]);

test('the configured admin can switch a user between allowed roles', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $target = User::factory()->approver()->create();

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => $target->username,
            'role' => UserRole::Moderator->value,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($target->fresh()->role)->toBe(UserRole::Moderator);
});

test('the configured admin can demote a staff user to member and the user leaves the list', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $target = User::factory()->moderator()->create();

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => $target->username,
            'role' => UserRole::Member->value,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($target->fresh()->role)->toBe(UserRole::Member);

    $this->get(route('admin.roles.index'))
        ->assertDontSee('aria-label="Role for '.$target->username.'"');
});

test('the configured admin can demote their own account and retain panel access', function () {
    $admin = User::factory()->moderator()->create(['email' => 'admin@example.com']);

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => $admin->username,
            'role' => UserRole::Member->value,
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($admin->fresh()->role)->toBe(UserRole::Member);

    $this->get(route('admin.roles.index'))
        ->assertOk();
});

test('the configured admin cannot assign an unknown role', function (string $role) {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $target = User::factory()->moderator()->create();

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => $target->username,
            'role' => $role,
        ])
        ->assertSessionHasErrors('role');

    expect($target->fresh()->role)->toBe(UserRole::Moderator);
})->with([
    'unknown' => 'owner',
]);

test('the configured admin cannot assign a role to an unknown username', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);

    config(['admin.email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'username' => 'unknown_user',
            'role' => UserRole::Approver->value,
        ])
        ->assertSessionHasErrors('username');

    $this->assertDatabaseMissing('users', ['username' => 'unknown_user']);
});
