<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Create a chart with a single difficulty and one revision recorded at the
 * given time, returning the chart.
 */
function feedChartWithRevision(User $uploader, string $title, Carbon $at): Chart
{
    $chart = Chart::factory()->for($uploader, 'uploader')->create(['title' => $title]);
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    Carbon::setTestNow($at);
    ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create();
    Carbon::setTestNow();

    return $chart;
}

test('a guest sees a log in prompt instead of a feed', function () {
    $this->get(route('feed'))
        ->assertOk()
        ->assertSee('Log in to see your feed');
});

test('a signed-in user with no activity sees an empty feed', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('feed'))
        ->assertOk()
        ->assertSee('Your feed is empty');
});

test('the feed shows charts the user has liked', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create(['title' => 'Liked Chart']);
    $chart->likes()->attach($user->id);

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertSee('Liked Chart');
});

test('the feed shows charts from users the user follows', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $user->following()->attach($followed->id);
    Chart::factory()->for($followed, 'uploader')->create(['title' => 'Followed Chart']);

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertSee('Followed Chart');
});

test('the feed hides charts the user has neither liked nor followed', function () {
    $user = User::factory()->create();
    Chart::factory()->create(['title' => 'Unrelated Chart']);

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertDontSee('Unrelated Chart');
});

test('the feed hides private charts', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->private()->create(['title' => 'Private Chart']);
    $chart->likes()->attach($user->id);

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertDontSee('Private Chart');
});

test('a chart that is both liked and from a followed user appears only once', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $user->following()->attach($followed->id);

    $chart = Chart::factory()->for($followed, 'uploader')->create(['title' => 'Overlap Chart']);
    $chart->likes()->attach($user->id);

    $response = $this->actingAs($user)->get(route('feed'))->assertOk();

    expect(substr_count($response->getContent(), route('charts.show', $chart)))->toBe(1);
});

test('the feed is sorted by the most recent revision across any difficulty', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $user->following()->attach($followed->id);

    feedChartWithRevision($followed, 'Older Chart', Carbon::parse('2026-01-01 00:00:00'));
    feedChartWithRevision($followed, 'Newer Chart', Carbon::parse('2026-06-01 00:00:00'));

    $html = $this->actingAs($user)->get(route('feed'))->assertOk()->getContent();

    expect(strpos($html, 'Newer Chart'))->toBeLessThan(strpos($html, 'Older Chart'));
});

test('the feed uses the newest revision across all of a chart difficulties', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create();
    $user->following()->attach($followed->id);

    $chart = Chart::factory()->for($followed, 'uploader')->create(['title' => 'Multi Difficulty Chart']);
    $first = ChartDifficulty::factory()->for($chart)->create();
    $second = ChartDifficulty::factory()->for($chart)->create();

    Carbon::setTestNow(Carbon::parse('2026-01-01 00:00:00'));
    ChartDifficultyRevision::factory()->for($first, 'difficulty')->create(['revision_number' => 1]);

    Carbon::setTestNow(Carbon::parse('2026-06-01 00:00:00'));
    ChartDifficultyRevision::factory()->for($second, 'difficulty')->create(['revision_number' => 5]);
    Carbon::setTestNow();

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertSee('revision #5');
});

test('the feed shows the revision number and author of the latest revision', function () {
    $user = User::factory()->create();
    $followed = User::factory()->create(['username' => 'chart_author']);
    $user->following()->attach($followed->id);

    $chart = Chart::factory()->for($followed, 'uploader')->create(['title' => 'Annotated Chart']);
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create([
        'user_id' => $followed->id,
        'revision_number' => 3,
    ]);

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertSee('revision #3')
        ->assertSee('by chart_author');
});
