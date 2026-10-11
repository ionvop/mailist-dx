<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a difficulty (owned by the given user) with an initial code value.
 */
function difficultyFor(?User $owner = null, string $code = '(120){1}1,2,3,4,'): ChartDifficulty
{
    $owner ??= User::factory()->create();

    return ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader'))
        ->create(['code' => $code]);
}

test('the first save records an original snapshot before the change', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), [
            'code' => '4,5,6,7,',
        ])
        ->assertRedirect();

    $revisions = $difficulty->revisions()->get();

    expect($revisions)->toHaveCount(2)
        ->and($revisions->last()->is_original)->toBeTrue()
        ->and($revisions->last()->code)->toBe('1,2,3,4,')
        ->and($revisions->last()->user_id)->toBeNull()
        ->and($revisions->first()->is_original)->toBeFalse()
        ->and($revisions->first()->code)->toBe('4,5,6,7,')
        ->and($revisions->first()->user_id)->toBe($owner->id);
});

test('saving identical code records no snapshot', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), [
            'code' => '1,2,3,4,',
        ])
        ->assertRedirect();

    expect($difficulty->revisions()->count())->toBe(0);
});

test('a subsequent change appends a snapshot without a new original', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '7,8,9,0,']);

    $revisions = $difficulty->revisions()->get();

    expect($revisions)->toHaveCount(3)
        ->and($revisions->where('is_original', true))->toHaveCount(1)
        ->and($revisions->first()->code)->toBe('7,8,9,0,')
        ->and($revisions->last()->code)->toBe('1,2,3,4,');
});

test('an editor can restore an earlier revision and history is preserved', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $original = $difficulty->revisions()->get()->last();

    $this->actingAs($owner)
        ->post(route('charts.difficulties.revisions.restore', [$difficulty->chart, $difficulty, $original]))
        ->assertRedirect(route('charts.difficulties.show', [$difficulty->chart, $difficulty]));

    expect($difficulty->fresh()->code)->toBe('1,2,3,4,');

    $revisions = $difficulty->revisions()->get();

    expect($revisions)->toHaveCount(3)
        ->and($revisions->first()->code)->toBe('1,2,3,4,')
        ->and($revisions->first()->is_original)->toBeFalse()
        ->and($revisions->where('is_original', true))->toHaveCount(1);
});

test('a non-editor cannot restore a revision', function () {
    $difficulty = difficultyFor();
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.revisions.restore', [$difficulty->chart, $difficulty, $revision]))
        ->assertNotFound();
});

test('a revision belonging to another difficulty is not found', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner);
    $foreign = ChartDifficultyRevision::factory()
        ->for(ChartDifficulty::factory()->for(Chart::factory()->for($owner, 'uploader')), 'difficulty')
        ->create();

    $this->actingAs($owner)
        ->post(route('charts.difficulties.revisions.restore', [$difficulty->chart, $difficulty, $foreign]))
        ->assertNotFound();
});

test('the diff endpoint returns the revision and its predecessor', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $latest = $difficulty->revisions()->first();

    $this->actingAs($owner)
        ->getJson(route('charts.difficulties.revisions.diff', [$difficulty->chart, $difficulty, $latest]))
        ->assertOk()
        ->assertJsonPath('current.code', '4,5,6,7,')
        ->assertJsonPath('current.is_original', false)
        ->assertJsonPath('current.author', $owner->username)
        ->assertJsonPath('previous.code', '1,2,3,4,')
        ->assertJsonPath('previous.is_original', true)
        ->assertJsonPath('previous.author', null);
});

test('the original revision has no predecessor in the diff', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $original = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->original()->create(['code' => '1,2,3,4,']);

    $this->actingAs($owner)
        ->getJson(route('charts.difficulties.revisions.diff', [$difficulty->chart, $difficulty, $original]))
        ->assertOk()
        ->assertJsonPath('current.is_original', true)
        ->assertJsonPath('previous', null);
});

test('a non-owner cannot read the diff of a private chart', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->private()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('charts.difficulties.revisions.diff', [$chart, $difficulty, $revision]))
        ->assertNotFound();
});

test('the history tab lists snapshots for a viewer', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('Original version')
        ->assertSee($owner->username);
});

test('the history tab shows an empty state when there are no snapshots', function () {
    $difficulty = difficultyFor();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('No history yet');
});

test('a guest cannot open the history tab', function () {
    $difficulty = difficultyFor();

    $this->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertRedirect(route('login'));
});
