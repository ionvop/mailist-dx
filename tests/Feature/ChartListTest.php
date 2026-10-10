<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the list page shows the upload call to action', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Upload chart');
});

test('the list page shows the charts owned by the user', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'My Owned Chart']);
    Chart::factory()->create(['title' => 'Someone Elses Chart']);

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('My Owned Chart')
        ->assertDontSee('Someone Elses Chart');
});

test('the list page shows a placeholder when the user has no charts', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Charts you own or can edit will be listed here.');
});

test('the list page shows the difficulty values for each chart', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->for($user, 'uploader')->create();
    ChartDifficulty::factory()->for($chart)->create(['difficulty_value' => '13+']);

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('13+');
});

test('the list page shows an overflow count when a chart has more than five difficulties', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->for($user, 'uploader')->create();
    ChartDifficulty::factory()->for($chart)->count(7)->create();

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('+2');
});
