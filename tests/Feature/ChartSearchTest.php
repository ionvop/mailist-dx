<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can view the search page', function () {
    $this->get(route('search'))->assertOk();
});

test('the search page lists public charts for a guest', function () {
    Chart::factory()->create(['title' => 'Public Song']);

    $this->get(route('search'))
        ->assertOk()
        ->assertSee('Public Song');
});

test('the search page hides private charts from a guest', function () {
    Chart::factory()->private()->create(['title' => 'Secret Song']);

    $this->get(route('search'))
        ->assertOk()
        ->assertDontSee('Secret Song');
});

test('the search page hides private charts from their owner', function () {
    $owner = User::factory()->create();
    Chart::factory()->for($owner, 'uploader')->private()->create(['title' => 'My Secret Song']);

    $this->actingAs($owner)
        ->get(route('search'))
        ->assertOk()
        ->assertDontSee('My Secret Song');
});

test('the search page hides private charts from collaborators', function () {
    $collaborator = User::factory()->create();
    $chart = Chart::factory()->private()->create(['title' => 'Shared Secret Song']);
    $chart->collaborators()->attach($collaborator);

    $this->actingAs($collaborator)
        ->get(route('search'))
        ->assertOk()
        ->assertDontSee('Shared Secret Song');
});

test('the search page can search charts by title, artist, or note designer', function () {
    Chart::factory()->create(['title' => 'Unique Song', 'artist' => 'Someone', 'note_designer' => 'Nobody']);
    Chart::factory()->create(['title' => 'Other Song', 'artist' => 'Unique Artist', 'note_designer' => 'Nobody']);
    Chart::factory()->create(['title' => 'Another Song', 'artist' => 'Someone', 'note_designer' => 'Unique Designer']);

    $this->get(route('search', ['q' => 'Unique']))
        ->assertOk()
        ->assertSee('Unique Song')
        ->assertSee('Other Song')
        ->assertSee('Another Song');

    $this->get(route('search', ['q' => 'Unique Song']))
        ->assertOk()
        ->assertSee('Unique Song')
        ->assertDontSee('Other Song')
        ->assertDontSee('Another Song');
});

test('the search page can filter by status', function () {
    Chart::factory()->status(ChartStatus::Completed)->create(['title' => 'Completed Chart']);
    Chart::factory()->status(ChartStatus::Wip)->create(['title' => 'Wip Chart']);

    $this->get(route('search', ['status' => ChartStatus::Completed->value]))
        ->assertOk()
        ->assertSee('Completed Chart')
        ->assertDontSee('Wip Chart');
});

test('the search page sorts by most recent by default', function () {
    Chart::factory()->create(['title' => 'Older Chart', 'created_at' => now()->subDay()]);
    Chart::factory()->create(['title' => 'Newer Chart', 'created_at' => now()]);

    $this->get(route('search'))
        ->assertOk()
        ->assertSeeInOrder(['Newer Chart', 'Older Chart']);
});

test('the search page can sort by popularity', function () {
    $popular = Chart::factory()->create(['title' => 'Popular Chart']);
    $popular->likes()->attach(User::factory()->count(3)->create());

    $unpopular = Chart::factory()->create(['title' => 'Unpopular Chart']);
    $unpopular->likes()->attach(User::factory()->create());

    $this->get(route('search', ['sort' => 'popularity']))
        ->assertOk()
        ->assertSeeInOrder(['Popular Chart', 'Unpopular Chart']);
});

test('the search page can sort by trending using likes from the past week', function () {
    $trending = Chart::factory()->create(['title' => 'Trending Chart']);
    $trending->likes()->attach(User::factory()->count(2)->create(), [
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $stale = Chart::factory()->create(['title' => 'Stale Chart']);
    $stale->likes()->attach(User::factory()->count(5)->create(), [
        'created_at' => now()->subWeeks(2),
        'updated_at' => now()->subWeeks(2),
    ]);

    $this->get(route('search', ['sort' => 'trending']))
        ->assertOk()
        ->assertSeeInOrder(['Trending Chart', 'Stale Chart']);
});

test('the search page paginates charts and preserves the query string', function () {
    foreach (range(1, 13) as $i) {
        Chart::factory()->create([
            'title' => sprintf('Chart-%02d', $i),
            'created_at' => now()->subMinutes(13 - $i),
        ]);
    }

    $this->get(route('search', ['sort' => 'popularity']))
        ->assertOk()
        ->assertSee('Chart-13')
        ->assertDontSee('Chart-01')
        ->assertSee('sort=popularity', false);

    $this->get(route('search', ['sort' => 'popularity', 'page' => 2]))
        ->assertOk()
        ->assertSee('Chart-01')
        ->assertDontSee('Chart-13');
});

test('the search page shows a filtered empty state when no charts match', function () {
    Chart::factory()->create(['title' => 'Some Chart']);

    $this->get(route('search', ['q' => 'no-such-chart']))
        ->assertOk()
        ->assertSee('No matching charts')
        ->assertDontSee('Some Chart');
});
