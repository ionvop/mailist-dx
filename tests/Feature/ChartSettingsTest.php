<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

/**
 * Upload a chart that also has a PV and return it.
 */
function uploadedChartWithPv(User $owner): Chart
{
    test()->actingAs($owner)->post(route('charts.store'), [
        'package' => chartPackageZip([
            'maidata.txt' => sampleMaidata(),
            'bg.jpg' => fakeJpegBytes(),
            'track.mp3' => 'fake-audio-bytes',
            'pv.mp4' => 'fake-video-bytes',
        ]),
    ])->assertRedirect();

    return Chart::firstOrFail();
}

/**
 * List the entry names contained in a chart's downloadable package.
 *
 * @return list<string>
 */
function downloadEntries(Chart $chart): array
{
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($chart->download_path));

    $entries = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }

    $zip->close();

    return $entries;
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
        ->assertSee('Assets')
        ->assertSee('Visibility')
        ->assertSee('Delete chart');
});

test('the Settings tab is a link for the owner and collaborators', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator->id);

    $this->actingAs($owner)
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee(route('charts.settings', $chart));

    $this->actingAs($collaborator)
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
            'audio_offset' => '0.5',
            'tags' => 'updated, tags',
            'description' => 'A **new** description.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $chart->refresh();

    expect($chart->title)->toBe('Renamed Song')
        ->and($chart->artist)->toBe('New Artist')
        ->and($chart->audio_bpm)->toBe('200')
        ->and($chart->audio_offset)->toBe('0.5')
        ->and($chart->tags)->toBe(['updated', 'tags'])
        ->and($chart->description)->toBe('A **new** description.');

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)
        ->toContain('&title=Renamed Song')
        ->toContain('&artist=New Artist')
        ->toContain('&wholebpm=200')
        ->toContain('&first=0.5')
        ->toContain('&tags=updated, tags');

    // The chart data must be preserved byte-for-byte.
    expect($maidata)->toContain('&inote_5=(180){4}1,2,3,4,5,');
});

test('an empty offset removes the &first tag', function () {
    $owner = User::factory()->create();
    $chart = uploadedChart($owner);

    $this->actingAs($owner)
        ->patch(route('charts.settings.update', $chart), [
            'title' => 'Test Song',
            'artist' => 'Test Artist',
            'audio_offset' => '',
        ])
        ->assertRedirect();

    $chart->refresh();

    expect($chart->audio_offset)->toBeNull();

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)->not->toContain('&first=');
});

test('a negative offset is accepted', function () {
    $owner = User::factory()->create();
    $chart = uploadedChart($owner);

    $this->actingAs($owner)
        ->patch(route('charts.settings.update', $chart), [
            'title' => 'Test Song',
            'artist' => 'Test Artist',
            'audio_offset' => '-0.25',
        ])
        ->assertRedirect();

    $chart->refresh();

    expect($chart->audio_offset)->toBe('-0.25');

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)->toContain('&first=-0.25');
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

test('the owner cannot set the approved status', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create(['status' => ChartStatus::ForReview]);

    $this->actingAs($owner)
        ->patch(route('charts.settings.visibility', $chart), ['status' => ChartStatus::Approved->value])
        ->assertSessionHasErrors('status');

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('the approved status is not offered on the settings page', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create(['status' => ChartStatus::Wip]);

    $this->actingAs($owner)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertDontSee('value="'.ChartStatus::Approved->value.'"', false);
});

test('the owner can replace the background, track, and pv', function () {
    $owner = User::factory()->create();
    $chart = uploadedChart($owner);
    $oldTrack = $chart->track_path;

    $this->actingAs($owner)
        ->patch(route('charts.settings.assets', $chart), [
            'art' => UploadedFile::fake()->image('new-bg.png', 600, 600),
            'track' => UploadedFile::fake()->create('new-track.ogg', 100, 'audio/ogg'),
            'pv' => UploadedFile::fake()->create('new-pv.webm', 100, 'video/webm'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $chart->refresh();

    expect($chart->art_path)->toBe("charts/{$chart->id}/art.jpg")
        ->and($chart->track_path)->toBe("charts/{$chart->id}/track.ogg")
        ->and($chart->pv_path)->toBe("charts/{$chart->id}/pv.webm");

    Storage::disk('local')->assertExists($chart->art_path);
    Storage::disk('local')->assertExists($chart->track_path);
    Storage::disk('local')->assertExists($chart->pv_path);
    Storage::disk('local')->assertMissing($oldTrack);

    $entries = downloadEntries($chart);

    expect($entries)->toContain('bg.jpg', 'track.ogg', 'pv.webm', 'maidata.txt');
});

test('replacing the pv with the same extension overwrites the existing file', function () {
    $owner = User::factory()->create();
    $chart = uploadedChartWithPv($owner);

    expect($chart->pv_path)->toBe("charts/{$chart->id}/pv.mp4");

    $this->actingAs($owner)
        ->patch(route('charts.settings.assets', $chart), [
            'pv' => UploadedFile::fake()->create('new-pv.mp4', 100, 'video/mp4'),
        ])
        ->assertRedirect();

    expect($chart->refresh()->pv_path)->toBe("charts/{$chart->id}/pv.mp4");
    Storage::disk('local')->assertExists($chart->pv_path);
});

test('the owner can clear the pv', function () {
    $owner = User::factory()->create();
    $chart = uploadedChartWithPv($owner);
    $oldPv = $chart->pv_path;

    $this->actingAs($owner)
        ->patch(route('charts.settings.assets', $chart), ['remove_pv' => '1'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->refresh()->pv_path)->toBeNull();
    Storage::disk('local')->assertMissing($oldPv);
    expect(downloadEntries($chart))->not->toContain('pv.mp4');
});

test('an uploaded pv takes precedence over clearing it', function () {
    $owner = User::factory()->create();
    $chart = uploadedChartWithPv($owner);

    $this->actingAs($owner)
        ->patch(route('charts.settings.assets', $chart), [
            'pv' => UploadedFile::fake()->create('new-pv.webm', 100, 'video/webm'),
            'remove_pv' => '1',
        ])
        ->assertRedirect();

    expect($chart->refresh()->pv_path)->toBe("charts/{$chart->id}/pv.webm");
    Storage::disk('local')->assertExists($chart->pv_path);
});

test('a non-owner cannot update assets', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.settings.assets', $chart), [
            'art' => UploadedFile::fake()->image('bg.png'),
        ])
        ->assertNotFound();
});

test('asset update rejects invalid file types', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->patch(route('charts.settings.assets', $chart), [
            'art' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            'track' => UploadedFile::fake()->create('clip.mp4', 1, 'video/mp4'),
            'pv' => UploadedFile::fake()->create('sound.mp3', 1, 'audio/mpeg'),
        ])
        ->assertSessionHasErrors(['art', 'track', 'pv']);
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
