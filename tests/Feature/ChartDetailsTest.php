<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot view a chart', function () {
    $chart = Chart::factory()->create();

    $this->get(route('charts.show', $chart))->assertRedirect(route('login'));
});

test('a user can view a public chart', function () {
    $chart = Chart::factory()->create(['title' => 'Public Song']);
    ChartDifficulty::factory()->for($chart)->create(['difficulty_value' => '13']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Public Song')
        ->assertSee('13');
});

test('the details page shows the note designer', function () {
    $chart = Chart::factory()->create(['note_designer' => 'Note Designer Name']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Note design')
        ->assertSee('Note Designer Name');
});

test('the details page shows when the chart was uploaded', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Uploaded')
        ->assertSee($chart->created_at->toIso8601String(), false);
});

test('the owner can view their private chart', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->private()->create();

    $this->actingAs($owner)
        ->get(route('charts.show', $chart))
        ->assertOk();
});

test('a non-owner cannot view a private chart', function () {
    $chart = Chart::factory()->private()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertNotFound();
});
