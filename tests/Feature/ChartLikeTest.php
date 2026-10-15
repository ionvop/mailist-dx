<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot like a chart', function () {
    $chart = Chart::factory()->create();

    $this->post(route('charts.likes.store', $chart))
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('chart_likes', 0);
});

test('a user can like a public chart', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('charts.likes.store', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('chart_likes', [
        'chart_id' => $chart->id,
        'user_id' => $user->id,
    ]);

    expect($chart->refresh()->isLikedBy($user))->toBeTrue();
});

test('liking a chart twice does not create duplicates', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('charts.likes.store', $chart))->assertRedirect();
    $this->actingAs($user)->post(route('charts.likes.store', $chart))->assertRedirect();

    $this->assertDatabaseCount('chart_likes', 1);
});

test('a user can remove their like from a chart', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();
    $chart->likes()->attach($user->id);

    $this->actingAs($user)
        ->delete(route('charts.likes.destroy', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseCount('chart_likes', 0);

    expect($chart->refresh()->isLikedBy($user))->toBeFalse();
});

test('the owner can like their own chart', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->post(route('charts.likes.store', $chart))
        ->assertRedirect();

    expect($chart->refresh()->isLikedBy($owner))->toBeTrue();
});

test('a non-owner cannot like a private chart', function () {
    $chart = Chart::factory()->private()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.likes.store', $chart))
        ->assertNotFound();

    $this->assertDatabaseCount('chart_likes', 0);
});

test('a non-owner cannot remove a like from a private chart', function () {
    $chart = Chart::factory()->private()->create();
    $user = User::factory()->create();
    $chart->likes()->attach($user->id);

    $this->actingAs($user)
        ->delete(route('charts.likes.destroy', $chart))
        ->assertNotFound();

    $this->assertDatabaseCount('chart_likes', 1);
});

test('the details page shows the like count', function () {
    $chart = Chart::factory()->create();
    $chart->likes()->attach(User::factory()->count(3)->create());

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Like (3)');
});

test('the details page shows the liked state for a user who liked the chart', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();
    $chart->likes()->attach($user->id);

    $this->actingAs($user)
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Liked (1)');
});
