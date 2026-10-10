<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Upload a chart through the normal flow and return it.
 */
function uploadedChart(User $owner): Chart
{
    test()->actingAs($owner)->post(route('charts.store'), [
        'package' => chartPackageZip([
            'maidata.txt' => sampleMaidata(),
            'bg.jpg' => fakeJpegBytes(),
            'track.mp3' => 'fake-audio-bytes',
        ]),
        'is_private' => '1',
    ])->assertRedirect();

    return Chart::firstOrFail();
}

test('a guest cannot view chart settings', function () {
    $chart = Chart::factory()->create();

    $this->get(route('charts.settings', $chart))->assertRedirect(route('login'));
});

test('a non-owner cannot view chart settings', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.settings', $chart))
        ->assertNotFound();
});

test('the owner can view chart settings', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Chart details')
        ->assertSee('Visibility')
        ->assertSee('Delete chart');
});

test('the Settings tab is only a link for the owner', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee(route('charts.settings', $chart));

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertDontSee(route('charts.settings', $chart));
});

test('the owner can update metadata and it is written to maidata', function () {
    $owner = User::factory()->create();
    $chart = uploadedChart($owner);

    $this->actingAs($owner)
        ->patch(route('charts.settings.update', $chart), [
            'title' => 'Renamed Song',
            'artist' => 'New Artist',
            'audio_bpm' => '200',
            'tags' => 'updated, tags',
            'description' => 'A **new** description.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $chart->refresh();

    expect($chart->title)->toBe('Renamed Song')
        ->and($chart->artist)->toBe('New Artist')
        ->and($chart->audio_bpm)->toBe('200')
        ->and($chart->tags)->toBe(['updated', 'tags'])
        ->and($chart->description)->toBe('A **new** description.');

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)
        ->toContain('&title=Renamed Song')
        ->toContain('&artist=New Artist')
        ->toContain('&wholebpm=200')
        ->toContain('&tags=updated, tags');

    // The chart data must be preserved byte-for-byte.
    expect($maidata)->toContain('&inote_5=(180){4}1,2,3,4,5,');
});

test('a non-owner cannot update metadata', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.settings.update', $chart), [
            'title' => 'Hacked',
            'artist' => 'Hacker',
        ])
        ->assertNotFound();
});

test('metadata update requires a title and artist', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->patch(route('charts.settings.update', $chart), ['title' => '', 'artist' => ''])
        ->assertSessionHasErrors(['title', 'artist']);
});

test('metadata is updated directly when the chart has no maidata file', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->patch(route('charts.settings.update', $chart), [
            'title' => 'Direct Title',
            'artist' => 'Direct Artist',
            'tags' => 'one, two',
        ])
        ->assertRedirect();

    $chart->refresh();

    expect($chart->title)->toBe('Direct Title')
        ->and($chart->artist)->toBe('Direct Artist')
        ->and($chart->tags)->toBe(['one', 'two']);
});

test('the owner can update status and privacy', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create(['is_private' => true]);

    $this->actingAs($owner)
        ->patch(route('charts.settings.visibility', $chart), [
            'status' => ChartStatus::Completed->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $chart->refresh();

    expect($chart->status)->toBe(ChartStatus::Completed)
        ->and($chart->is_private)->toBeFalse();
});

test('a non-owner cannot update visibility', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.settings.visibility', $chart), ['status' => ChartStatus::Approved->value])
        ->assertNotFound();
});

test('the owner can delete their chart and its files', function () {
    $owner = User::factory()->create();
    $chart = uploadedChart($owner);
    $directory = "charts/{$chart->id}";

    Storage::disk('local')->assertExists($chart->maidata_path);

    $this->actingAs($owner)
        ->delete(route('charts.destroy', $chart))
        ->assertRedirect(route('list'))
        ->assertSessionHas('status');

    expect(Chart::find($chart->id))->toBeNull();
    Storage::disk('local')->assertMissing($chart->maidata_path);
    Storage::disk('local')->assertMissing("{$directory}/download.zip");
});

test('a non-owner cannot delete a chart', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.destroy', $chart))
        ->assertNotFound();

    expect(Chart::find($chart->id))->not->toBeNull();
});
