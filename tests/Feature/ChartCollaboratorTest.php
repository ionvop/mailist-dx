<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot manage chart collaborators', function () {
    $chart = Chart::factory()->create();

    $this->post(route('charts.settings.collaborators.store', $chart), ['username' => 'friend'])
        ->assertRedirect(route('login'));
});

test('the owner can grant edit access by username', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create(['username' => 'chartmaster']);

    $this->actingAs($owner)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'chartmaster'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('chart_collaborators', [
        'chart_id' => $chart->id,
        'user_id' => $collaborator->id,
    ]);

    expect($chart->refresh()->isCollaborator($collaborator))->toBeTrue();
});

test('granting edit access to an unknown username fails validation', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'does-not-exist'])
        ->assertSessionHasErrors('username');

    $this->assertDatabaseCount('chart_collaborators', 0);
});

test('the owner cannot grant edit access to themselves', function () {
    $owner = User::factory()->create(['username' => 'owner']);
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'owner'])
        ->assertSessionHasErrors('username');

    $this->assertDatabaseCount('chart_collaborators', 0);
});

test('granting edit access twice does not create duplicates', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    User::factory()->create(['username' => 'chartmaster']);

    $this->actingAs($owner)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'chartmaster'])
        ->assertRedirect();

    $this->actingAs($owner)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'chartmaster'])
        ->assertRedirect();

    $this->assertDatabaseCount('chart_collaborators', 1);
});

test('a non-owner cannot grant edit access', function () {
    $chart = Chart::factory()->create();
    User::factory()->create(['username' => 'chartmaster']);

    $this->actingAs(User::factory()->create())
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'chartmaster'])
        ->assertNotFound();

    $this->assertDatabaseCount('chart_collaborators', 0);
});

test('a collaborator cannot grant edit access', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create(['username' => 'editor']);
    $chart->collaborators()->attach($collaborator->id);

    User::factory()->create(['username' => 'newcomer']);

    $this->actingAs($collaborator)
        ->post(route('charts.settings.collaborators.store', $chart), ['username' => 'newcomer'])
        ->assertNotFound();

    expect($chart->refresh()->collaborators)->toHaveCount(1);
});

test('the owner can revoke edit access', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($owner)
        ->delete(route('charts.settings.collaborators.destroy', [$chart, $collaborator]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->refresh()->isCollaborator($collaborator))->toBeFalse();
});

test('a non-owner cannot revoke edit access', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.settings.collaborators.destroy', [$chart, $collaborator]))
        ->assertNotFound();

    expect($chart->refresh()->isCollaborator($collaborator))->toBeTrue();
});

test('a collaborator can view a private chart', function () {
    $chart = Chart::factory()->private()->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($collaborator)
        ->get(route('charts.show', $chart))
        ->assertOk();
});

test('a collaborator can open the settings tab and only sees the leave control', function () {
    $chart = Chart::factory()->private()->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($collaborator)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Remove my edit access')
        ->assertDontSee('Chart details')
        ->assertDontSee('Delete chart');
});

test('a collaborator can remove their own edit access', function () {
    $chart = Chart::factory()->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($collaborator)
        ->delete(route('charts.settings.collaborators.leave', $chart))
        ->assertRedirect(route('list'))
        ->assertSessionHas('status');

    expect($chart->refresh()->isCollaborator($collaborator))->toBeFalse();
});

test('a non-collaborator cannot remove edit access they do not have', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('charts.settings.collaborators.leave', $chart))
        ->assertNotFound();
});

test('the owner cannot remove their own edit access', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->delete(route('charts.settings.collaborators.leave', $chart))
        ->assertNotFound();
});

test('a guest cannot remove edit access', function () {
    $chart = Chart::factory()->create();

    $this->delete(route('charts.settings.collaborators.leave', $chart))
        ->assertRedirect(route('login'));
});

test('the owner sees collaborators on the settings page', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create(['username' => 'chartmaster']);
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($owner)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Edit access')
        ->assertSee('chartmaster');
});
