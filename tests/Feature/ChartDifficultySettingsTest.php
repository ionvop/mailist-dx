<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Upload a chart with a stored maidata file and return it.
 */
function difficultySettingsChart(User $owner): Chart
{
    test()->actingAs($owner)->post(route('charts.store'), [
        'package' => chartPackageZip([
            'maidata.txt' => sampleMaidata(),
            'bg.jpg' => fakeJpegBytes(),
            'track.mp3' => 'fake-audio-bytes',
        ]),
    ])->assertRedirect();

    return Chart::firstOrFail();
}

test('the Settings tab is a link for the owner', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()->for(Chart::factory()->for($owner, 'uploader'))->create();

    $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty]))
        ->assertOk()
        ->assertSee(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'settings']));
});

test('the Settings tab is not a link for a non-owner', function () {
    $difficulty = ChartDifficulty::factory()->for(Chart::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty]))
        ->assertOk()
        ->assertDontSee(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'settings']));
});

test('a non-owner cannot open the settings tab', function () {
    $difficulty = ChartDifficulty::factory()->for(Chart::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'settings']))
        ->assertNotFound();
});

test('the owner sees the settings form with a disabled delete button', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()->for(Chart::factory()->for($owner, 'uploader'))->create();

    $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'settings']))
        ->assertOk()
        ->assertSee('Difficulty details')
        ->assertSee('Delete difficulty')
        ->assertSee('disabled', false);
});

test('a non-owner cannot update difficulty settings', function () {
    $difficulty = ChartDifficulty::factory()->for(Chart::factory())->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.difficulties.settings.update', [$difficulty->chart, $difficulty]), [
            'difficulty_value' => '15',
        ])
        ->assertNotFound();
});

test('the owner can update the level value and charter, and it is written to maidata', function () {
    $owner = User::factory()->create();
    $chart = difficultySettingsChart($owner);
    $difficulty = $chart->difficulties()->where('chart_index', '5')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$chart, $difficulty]), [
            'difficulty_value' => '15',
            'charter' => 'New Charter',
        ])
        ->assertRedirect(route('charts.difficulties.show', [$chart, $difficulty, 'settings']))
        ->assertSessionHas('status');

    $difficulty->refresh();

    expect($difficulty->difficulty_value)->toBe('15')
        ->and($difficulty->charter)->toBe('New Charter');

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)
        ->toContain('&lv_5=15')
        ->toContain('&des_5=New Charter')
        // Other charts and the chart code are untouched.
        ->toContain('&lv_4=12')
        ->toContain('&inote_5=(180){4}1,2,3,4,5,');
});

test('clearing the charter falls back to the level note designer', function () {
    $owner = User::factory()->create();
    $chart = difficultySettingsChart($owner);
    $difficulty = $chart->difficulties()->where('chart_index', '5')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$chart, $difficulty]), [
            'difficulty_value' => '14+',
            'charter' => '',
        ])
        ->assertRedirect();

    $difficulty->refresh();

    expect($difficulty->charter)->toBe('Test Charter');

    expect(Storage::disk('local')->get($chart->maidata_path))->not->toContain('&des_5=');
});

test('an empty level value is rejected because the chart would fail to parse', function () {
    $owner = User::factory()->create();
    $chart = difficultySettingsChart($owner);
    $difficulty = $chart->difficulties()->where('chart_index', '5')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$chart, $difficulty]), [
            'difficulty_value' => '',
            'charter' => 'Master Charter',
        ])
        ->assertSessionHasErrors('difficulty_value');

    expect($difficulty->refresh()->difficulty_value)->toBe('14+');

    expect(Storage::disk('local')->get($chart->maidata_path))->toContain('&lv_5=14+');
});

test('difficulty settings are applied directly when the chart has no maidata file', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader'))
        ->create(['difficulty_value' => '10', 'charter' => 'Old']);

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$difficulty->chart, $difficulty]), [
            'difficulty_value' => '13',
            'charter' => 'Direct',
        ])
        ->assertRedirect();

    $difficulty->refresh();

    expect($difficulty->difficulty_value)->toBe('13')
        ->and($difficulty->charter)->toBe('Direct');
});

test('a difficulty from another chart cannot be updated through the settings route', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $otherDifficulty = ChartDifficulty::factory()->for(Chart::factory()->for($owner, 'uploader'))->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$chart, $otherDifficulty]), [
            'difficulty_value' => '15',
        ])
        ->assertNotFound();
});
