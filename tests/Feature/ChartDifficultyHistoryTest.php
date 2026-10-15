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
        ->and($revisions->last()->user_id)->toBe($owner->id)
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
        ->assertJsonPath('previous.author', $owner->username);
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
        ->assertSee('Uploaded')
        ->assertSee($owner->username);
});

test('the history tab shows an empty state when there are no snapshots', function () {
    $difficulty = difficultyFor();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('No history yet');
});

test('a guest can open the history tab of a public chart', function () {
    $difficulty = difficultyFor();

    $this->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk();
});

test('revisions are numbered sequentially per difficulty', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '7,8,9,0,']);

    $revisions = $difficulty->revisions()->reorder('id')->get();

    expect($revisions->pluck('revision_number')->all())->toBe([1, 2, 3])
        ->and($revisions->first()->is_original)->toBeTrue();
});

test('the history tab shows the revision number and an anchor for each record', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $latest = $difficulty->revisions()->first();

    $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('#2')
        ->assertSee('id="revision-'.$latest->id.'"', false);
});

test('the history tab jumps to the page containing the linked revision', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    // 25 revisions -> the target on page 1 scrolls off page 2.
    for ($i = 1; $i <= 24; $i++) {
        $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => "1,2,3,{$i},"]);
    }

    $target = $difficulty->revisions()->reorder('id')->first(); // oldest = #1

    $response = $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']).'?revision='.$target->id)
        ->assertOk();

    // The target is the oldest of 25, so it sits on page 2.
    expect($response->viewData('revisions')->currentPage())->toBe(2)
        ->and($response->viewData('focusRevisionId'))->toBe($target->id);

    $response->assertSee('id="revision-'.$target->id.'"', false);
});

test('an unknown revision query is ignored', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $response = $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']).'?revision=999999')
        ->assertOk();

    expect($response->viewData('focusRevisionId'))->toBeNull()
        ->and($response->viewData('revisions')->currentPage())->toBe(1);
});

test('a collaborator can restore a revision authored by another user', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');

    $editor = User::factory()->create();
    $difficulty->chart->collaborators()->attach($editor);

    // A different user makes a change...
    $this->actingAs($editor)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);
    $byEditor = $difficulty->revisions()->first();

    // ...the owner changes the code again...
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '9,9,9,9,']);

    // ...and then restores the revision the *editor* authored.
    $this->actingAs($owner)
        ->post(route('charts.difficulties.revisions.restore', [$difficulty->chart, $difficulty, $byEditor]))
        ->assertRedirect(route('charts.difficulties.show', [$difficulty->chart, $difficulty]));

    expect($difficulty->fresh()->code)->toBe('4,5,6,7,');
});

test('the author can add a note to their revision', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $this->actingAs($owner)->patch(route('charts.difficulties.update', [$difficulty->chart, $difficulty]), ['code' => '4,5,6,7,']);

    $revision = $difficulty->revisions()->first();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $revision]), [
            'note' => 'Fixed the final stream.',
        ])
        ->assertRedirect();

    expect($revision->fresh()->note)->toBe('Fixed the final stream.');
});

test('the author can update and clear a note', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->for($owner)->withNote('First note')->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $revision]), [
            'note' => 'Second note',
        ])
        ->assertRedirect();

    expect($revision->fresh()->note)->toBe('Second note');

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $revision]), [
            'note' => null,
        ])
        ->assertRedirect();

    expect($revision->fresh()->note)->toBeNull();
});

test('a user who did not author the revision cannot edit its note', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $revision]), [
            'note' => 'Not mine to edit.',
        ])
        ->assertForbidden();

    expect($revision->fresh()->note)->toBeNull();
});

test('a note longer than 255 characters is rejected', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->for($owner)->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $revision]), [
            'note' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('note');

    expect($revision->fresh()->note)->toBeNull();
});

test('a revision belonging to another difficulty cannot have its note edited', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner);
    $foreign = ChartDifficultyRevision::factory()
        ->for(ChartDifficulty::factory()->for(Chart::factory()->for($owner, 'uploader')), 'difficulty')
        ->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.revisions.note.update', [$difficulty->chart, $difficulty, $foreign]), [
            'note' => 'Wrong difficulty.',
        ])
        ->assertNotFound();
});

test('the history tab shows the note and an edit affordance to its author', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->for($owner)->withNote('Tweaked the intro.')->create();

    $this->actingAs($owner)
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('Tweaked the intro.')
        ->assertSee('Edit note');
});

test('the history tab shows a note to other viewers without an edit affordance', function () {
    $owner = User::factory()->create();
    $difficulty = difficultyFor($owner, '1,2,3,4,');
    ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->withNote('Tweaked the intro.')->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('Tweaked the intro.')
        ->assertDontSee('Edit note');
});
