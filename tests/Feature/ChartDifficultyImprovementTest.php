<?php

use App\Enums\ImprovementStatus;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyImprovement;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a difficulty (owned by the given user) with an initial code value.
 */
function improvementDifficultyFor(?User $owner = null, string $code = "1,2,3,\n4,5,6,\n7,8,9,"): ChartDifficulty
{
    $owner ??= User::factory()->create();

    return ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader'))
        ->create(['code' => $code]);
}

test('a guest cannot view a difficulty\'s improvements', function () {
    $difficulty = improvementDifficultyFor();

    $this->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']))
        ->assertRedirect(route('login'));
});

test('a user can view a difficulty\'s improvements', function () {
    $difficulty = improvementDifficultyFor();
    ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['body' => 'Timing feels off here']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']))
        ->assertOk()
        ->assertSee('Timing feels off here');
});

test('a user can post a suggestion with a code range and the snippet is extracted server-side', function () {
    $difficulty = improvementDifficultyFor();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'These notes do not match the song.',
            'line_start' => 2,
            'line_end' => 3,
        ])
        ->assertRedirect(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']))
        ->assertSessionHas('status');

    $improvement = ChartDifficultyImprovement::firstOrFail();

    expect($improvement->difficulty_id)->toBe($difficulty->id)
        ->and($improvement->user_id)->toBe($user->id)
        ->and($improvement->line_start)->toBe(2)
        ->and($improvement->line_end)->toBe(3)
        ->and($improvement->snippet)->toBe("4,5,6,\n7,8,9,")
        ->and($improvement->status)->toBe(ImprovementStatus::Open);
});

test('a suggestion can be posted without a code range', function () {
    $difficulty = improvementDifficultyFor();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'General feedback.',
        ])
        ->assertRedirect();

    $improvement = ChartDifficultyImprovement::firstOrFail();

    expect($improvement->line_start)->toBeNull()
        ->and($improvement->snippet)->toBeNull();
});

test('a suggestion requires a body', function () {
    $difficulty = improvementDifficultyFor();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => '',
        ])
        ->assertSessionHasErrors('body');

    expect(ChartDifficultyImprovement::count())->toBe(0);
});

test('the end line must be at or after the start line', function () {
    $difficulty = improvementDifficultyFor();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'Bad range.',
            'line_start' => 3,
            'line_end' => 1,
        ])
        ->assertSessionHasErrors('line_end');

    expect(ChartDifficultyImprovement::count())->toBe(0);
});

test('an editor can resolve a suggestion with a remark and a linked revision', function () {
    $owner = User::factory()->create();
    $difficulty = improvementDifficultyFor($owner);
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => 'Fixed in the latest revision.',
            'revision_id' => $revision->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $improvement->refresh();

    expect($improvement->status)->toBe(ImprovementStatus::Resolved)
        ->and($improvement->resolution_note)->toBe('Fixed in the latest revision.')
        ->and($improvement->resolved_by)->toBe($owner->id)
        ->and($improvement->resolved_at)->not->toBeNull()
        ->and($improvement->revision_id)->toBe($revision->id);
});

test('an editor can reject a suggestion with a reason', function () {
    $owner = User::factory()->create();
    $difficulty = improvementDifficultyFor($owner);
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'rejected',
            'resolution_note' => 'This is intentional.',
        ])
        ->assertRedirect();

    $improvement->refresh();

    expect($improvement->status)->toBe(ImprovementStatus::Rejected)
        ->and($improvement->resolution_note)->toBe('This is intentional.')
        ->and($improvement->revision_id)->toBeNull();
});

test('a resolution requires a remark', function () {
    $owner = User::factory()->create();
    $difficulty = improvementDifficultyFor($owner);
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => '',
        ])
        ->assertSessionHasErrors('resolution_note');

    expect($improvement->refresh()->status)->toBe(ImprovementStatus::Open);
});

test('a non-editor cannot resolve a suggestion', function () {
    $difficulty = improvementDifficultyFor();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => 'Nope.',
        ])
        ->assertNotFound();

    expect($improvement->refresh()->status)->toBe(ImprovementStatus::Open);
});

test('a revision from another difficulty cannot be linked', function () {
    $owner = User::factory()->create();
    $difficulty = improvementDifficultyFor($owner);
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();
    $otherRevision = ChartDifficultyRevision::factory()->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => 'Linked wrongly.',
            'revision_id' => $otherRevision->id,
        ])
        ->assertNotFound();

    expect($improvement->refresh()->revision_id)->toBeNull();
});

test('the author can delete their own suggestion', function () {
    $user = User::factory()->create();
    $difficulty = improvementDifficultyFor();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->for($user)->create();

    $this->actingAs($user)
        ->delete(route('charts.difficulties.improvements.destroy', [$difficulty->chart, $difficulty, $improvement]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ChartDifficultyImprovement::count())->toBe(0);
});

test('a user cannot delete someone else\'s suggestion', function () {
    $difficulty = improvementDifficultyFor();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.difficulties.improvements.destroy', [$difficulty->chart, $difficulty, $improvement]))
        ->assertForbidden();

    expect(ChartDifficultyImprovement::count())->toBe(1);
});

test('a non-owner cannot view a private chart\'s improvements', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader')->private())
        ->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']))
        ->assertNotFound();
});

test('a non-owner cannot post a suggestion on a private chart', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader')->private())
        ->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'Sneaky.',
        ])
        ->assertNotFound();

    expect(ChartDifficultyImprovement::count())->toBe(0);
});

test('a difficulty\'s suggestions are displayed newest first', function () {
    $difficulty = improvementDifficultyFor();
    ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['body' => 'First suggestion']);
    ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['body' => 'Second suggestion']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']))
        ->assertOk()
        ->assertSeeInOrder(['Second suggestion', 'First suggestion']);
});
