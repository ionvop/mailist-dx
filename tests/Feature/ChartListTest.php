<?php

use App\Enums\ChartStatus;
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

test('the list page shows charts the user has edit access to', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create(['title' => 'Shared With Me']);
    $chart->collaborators()->attach($user);

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Shared With Me');
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

test('the list page shows the upload time and like count on each chart card', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->for($user, 'uploader')->create();
    $chart->likes()->attach(User::factory()->count(3)->create());

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee($chart->created_at->diffForHumans())
        ->assertSee('3');
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

test('the list page shows the like count and upload time on each chart card', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->for($user, 'uploader')->create([
        'title' => 'Liked Chart',
        'created_at' => now()->subDays(3),
    ]);
    $chart->likes()->attach(User::factory()->count(4)->create());

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Liked Chart')
        ->assertSee('4')
        ->assertSee('3 days ago');
});

test('the list page can filter to charts owned by the user', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'My Owned Chart']);

    $shared = Chart::factory()->create(['title' => 'Shared With Me']);
    $shared->collaborators()->attach($user);

    $this->actingAs($user)
        ->get(route('list', ['ownership' => 'owned']))
        ->assertOk()
        ->assertSee('My Owned Chart')
        ->assertDontSee('Shared With Me');
});

test('the list page can filter to charts shared with the user', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'My Owned Chart']);

    $shared = Chart::factory()->create(['title' => 'Shared With Me']);
    $shared->collaborators()->attach($user);

    $this->actingAs($user)
        ->get(route('list', ['ownership' => 'shared']))
        ->assertOk()
        ->assertSee('Shared With Me')
        ->assertDontSee('My Owned Chart');
});

test('the list page can filter by status', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->status(ChartStatus::Completed)->create(['title' => 'Completed Chart']);
    Chart::factory()->for($user, 'uploader')->status(ChartStatus::Wip)->create(['title' => 'Wip Chart']);

    $this->actingAs($user)
        ->get(route('list', ['status' => ChartStatus::Completed->value]))
        ->assertOk()
        ->assertSee('Completed Chart')
        ->assertDontSee('Wip Chart');
});

test('the list page can search charts by title or artist', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'Unique Song', 'artist' => 'Someone']);
    Chart::factory()->for($user, 'uploader')->create(['title' => 'Other Song', 'artist' => 'Unique Artist']);

    $this->actingAs($user)
        ->get(route('list', ['q' => 'Unique']))
        ->assertOk()
        ->assertSee('Unique Song')
        ->assertSee('Other Song');

    $this->actingAs($user)
        ->get(route('list', ['q' => 'Unique Song']))
        ->assertOk()
        ->assertSee('Unique Song')
        ->assertDontSee('Other Song');
});

test('the list page paginates charts and preserves the query string', function () {
    $user = User::factory()->create();

    foreach (range(1, 13) as $i) {
        Chart::factory()->for($user, 'uploader')->create([
            'title' => sprintf('Chart-%02d', $i),
            'created_at' => now()->subMinutes(13 - $i),
        ]);
    }

    $this->actingAs($user)
        ->get(route('list', ['ownership' => 'owned']))
        ->assertOk()
        ->assertSee('Chart-13')
        ->assertDontSee('Chart-01')
        ->assertSee('ownership=owned', false);

    $this->actingAs($user)
        ->get(route('list', ['ownership' => 'owned', 'page' => 2]))
        ->assertOk()
        ->assertSee('Chart-01')
        ->assertDontSee('Chart-13');
});

test('the list page shows a filtered empty state when no charts match', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'Some Chart']);

    $this->actingAs($user)
        ->get(route('list', ['q' => 'no-such-chart']))
        ->assertOk()
        ->assertSee('No matching charts')
        ->assertDontSee('Some Chart');
});
