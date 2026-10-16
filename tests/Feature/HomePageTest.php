<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can view the home page', function () {
    $this->get(route('home'))->assertOk();
});

test('the home page shows approved charts in the approved sections', function () {
    Chart::factory()->status(ChartStatus::Approved)->create(['title' => 'Approved Song']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Approved Song');
});

test('the home page shows non-approved charts in the non-approved sections', function () {
    Chart::factory()->status(ChartStatus::Wip)->create(['title' => 'Wip Song']);
    Chart::factory()->status(ChartStatus::ForReview)->create(['title' => 'Review Song']);
    Chart::factory()->status(ChartStatus::Completed)->create(['title' => 'Completed Song']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Wip Song')
        ->assertSee('Review Song')
        ->assertSee('Completed Song');
});

test('the home page hides private charts', function () {
    Chart::factory()->private()->create(['title' => 'Secret Song']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Secret Song');
});

test('the home page shows at most three charts per section', function () {
    foreach (range(1, 5) as $i) {
        Chart::factory()->status(ChartStatus::Approved)->create([
            'title' => sprintf('Approved-%02d', $i),
            'created_at' => now()->subMinutes(5 - $i),
        ]);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Approved-05')
        ->assertSee('Approved-04')
        ->assertSee('Approved-03')
        ->assertDontSee('Approved-02')
        ->assertDontSee('Approved-01');
});

test('the home page trending approved section sorts by likes from the past week', function () {
    $trending = Chart::factory()->status(ChartStatus::Approved)->create(['title' => 'Trending Approved']);
    $trending->likes()->attach(User::factory()->count(2)->create(), [
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $stale = Chart::factory()->status(ChartStatus::Approved)->create(['title' => 'Stale Approved']);
    $stale->likes()->attach(User::factory()->count(5)->create(), [
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Trending Approved', 'Stale Approved']);
});

test('the home page links each section to the matching search filters', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('status=approved&amp;sort=trending', escape: false)
        ->assertSee('status=approved', escape: false)
        ->assertSee('sort=trending', escape: false)
        ->assertSee(route('search'), escape: false);
});
