<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('a guest cannot download a chart file', function () {
    $chart = Chart::factory()->create(['art_path' => 'charts/1/art.jpg']);

    $this->get(route('charts.files', [$chart, 'art']))->assertRedirect(route('login'));
});

test('a user can download a public chart file', function () {
    $chart = Chart::factory()->create(['art_path' => 'charts/1/art.jpg']);
    Storage::disk('local')->put('charts/1/art.jpg', fakeJpegBytes());

    $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'art']))
        ->assertOk();
});

test('a non-owner cannot download a private chart file', function () {
    $chart = Chart::factory()->private()->create(['art_path' => 'charts/1/art.jpg']);
    Storage::disk('local')->put('charts/1/art.jpg', fakeJpegBytes());

    $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'art']))
        ->assertNotFound();
});

test('a user can download the chart package as a zip', function () {
    $chart = Chart::factory()->create([
        'title' => 'My Chart',
        'download_path' => 'charts/1/download.zip',
    ]);
    Storage::disk('local')->put('charts/1/download.zip', 'package');

    $response = $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'download']));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('My Chart.zip');
});

test('a user can download the chart package as an adx', function () {
    $chart = Chart::factory()->create([
        'title' => 'My Chart',
        'download_path' => 'charts/1/download.zip',
    ]);
    Storage::disk('local')->put('charts/1/download.zip', 'package');

    $response = $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'adx']));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('My Chart.adx');
});

test('an unknown file type is not found', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'secret']))
        ->assertNotFound();
});

test('a missing file is not found', function () {
    $chart = Chart::factory()->create(['art_path' => 'charts/1/missing.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.files', [$chart, 'art']))
        ->assertNotFound();
});
