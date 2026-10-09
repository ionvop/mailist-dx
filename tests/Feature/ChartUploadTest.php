<?php

use App\Enums\ChartDifficultyName;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('a guest cannot view the upload page', function () {
    $this->get(route('charts.upload'))->assertRedirect(route('login'));
});

test('a guest cannot upload a chart', function () {
    $this->post(route('charts.store'))->assertRedirect(route('login'));
});

test('the upload page renders for a signed-in user', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('charts.upload'))
        ->assertOk()
        ->assertSee('Upload chart');
});

test('a user can upload a chart package', function () {
    $user = User::factory()->create();

    $package = chartPackageZip([
        'maidata.txt' => sampleMaidata(),
        'bg.jpg' => fakeJpegBytes(),
        'track.mp3' => 'fake-audio-bytes',
    ]);

    $this->actingAs($user)
        ->post(route('charts.store'), ['package' => $package])
        ->assertRedirect();

    $chart = Chart::firstOrFail();

    expect($chart->uploader_id)->toBe($user->id)
        ->and($chart->title)->toBe('Test Song')
        ->and($chart->artist)->toBe('Test Artist')
        ->and($chart->difficulties)->toHaveCount(2)
        ->and($chart->difficulties->pluck('difficulty')->all())
        ->toBe([ChartDifficultyName::Expert, ChartDifficultyName::Master]);

    Storage::disk('local')->assertExists($chart->maidata_path);
    Storage::disk('local')->assertExists($chart->art_path);
    Storage::disk('local')->assertExists($chart->track_path);
    Storage::disk('local')->assertExists($chart->download_path);
});

test('a manual component override replaces the packaged file', function () {
    $user = User::factory()->create();

    $package = chartPackageZip([
        'maidata.txt' => sampleMaidata(),
        'track.mp3' => 'packaged-audio',
    ]);

    $this->actingAs($user)
        ->post(route('charts.store'), [
            'package' => $package,
            'track' => UploadedFile::fake()->create('override.ogg', 10, 'audio/ogg'),
        ])
        ->assertRedirect();

    $chart = Chart::firstOrFail();

    expect($chart->track_path)->toEndWith('track.ogg');
    Storage::disk('local')->assertExists($chart->track_path);
});

test('a chart can be uploaded as private with a description', function () {
    $user = User::factory()->create();

    $package = chartPackageZip(['maidata.txt' => sampleMaidata()]);

    $this->actingAs($user)
        ->post(route('charts.store'), [
            'package' => $package,
            'description' => 'A **great** chart.',
            'is_private' => '1',
        ])
        ->assertRedirect();

    $chart = Chart::firstOrFail();

    expect($chart->is_private)->toBeTrue()
        ->and($chart->description)->toBe('A **great** chart.');
});

test('the package is required', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('charts.store'))
        ->assertSessionHasErrors('package');
});

test('the package must be a zip', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('charts.store'), [
            'package' => UploadedFile::fake()->create('chart.txt', 1, 'text/plain'),
        ])
        ->assertSessionHasErrors('package');
});

test('an invalid package surfaces parser errors', function () {
    $user = User::factory()->create();

    $package = chartPackageZip(['maidata.txt' => "&title=Song\n&artist=Artist\n"]);

    $this->actingAs($user)
        ->post(route('charts.store'), ['package' => $package])
        ->assertSessionHasErrors('package');

    expect(Chart::count())->toBe(0);
});

test('a package without a maidata file is rejected', function () {
    $user = User::factory()->create();

    $package = chartPackageZip(['readme.txt' => 'nothing here']);

    $this->actingAs($user)
        ->post(route('charts.store'), ['package' => $package])
        ->assertSessionHasErrors('package');

    expect(Chart::count())->toBe(0);
});
