<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a difficulty (owned by the given user) with an initial code value.
 */
function commentDifficultyFor(?User $owner = null): ChartDifficulty
{
    $owner ??= User::factory()->create();

    return ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader'))
        ->create();
}

test('a guest can view a difficulty\'s comments', function () {
    $difficulty = commentDifficultyFor();
    ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create(['body' => 'Great chart!']);

    $this->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertOk()
        ->assertSee('Great chart!');
});

test('a user can view a difficulty\'s comments', function () {
    $difficulty = commentDifficultyFor();
    $comment = ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create(['body' => 'Great chart!']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertOk()
        ->assertSee('Great chart!')
        ->assertSee($comment->user->username);
});

test('a difficulty\'s comments are displayed newest first', function () {
    $difficulty = commentDifficultyFor();
    ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create(['body' => 'First thought']);
    ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create(['body' => 'Second thought']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertOk()
        ->assertSeeInOrder(['Second thought', 'First thought']);
});

test('a user can post a comment on a difficulty', function () {
    $difficulty = commentDifficultyFor();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => 'Nice work!'])
        ->assertRedirect(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertSessionHas('status');

    $comment = ChartDifficultyComment::firstOrFail();

    expect($comment->difficulty_id)->toBe($difficulty->id)
        ->and($comment->user_id)->toBe($user->id)
        ->and($comment->body)->toBe('Nice work!');
});

test('a comment requires a body', function () {
    $difficulty = commentDifficultyFor();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => ''])
        ->assertSessionHasErrors('body');

    expect(ChartDifficultyComment::count())->toBe(0);
});

test('a guest cannot post a comment', function () {
    $difficulty = commentDifficultyFor();

    $this->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => 'Hello'])
        ->assertRedirect(route('login'));

    expect(ChartDifficultyComment::count())->toBe(0);
});

test('the author can delete their own comment', function () {
    $user = User::factory()->create();
    $difficulty = commentDifficultyFor();
    $comment = ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->for($user)->create();

    $this->actingAs($user)
        ->delete(route('charts.difficulties.comments.destroy', [$difficulty->chart, $difficulty, $comment]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ChartDifficultyComment::count())->toBe(0);
});

test('a user cannot delete someone else\'s comment', function () {
    $difficulty = commentDifficultyFor();
    $comment = ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.difficulties.comments.destroy', [$difficulty->chart, $difficulty, $comment]))
        ->assertForbidden();

    expect(ChartDifficultyComment::count())->toBe(1);
});

test('a comment from another difficulty cannot be deleted', function () {
    $user = User::factory()->create();
    $difficulty = commentDifficultyFor();
    $otherComment = ChartDifficultyComment::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('charts.difficulties.comments.destroy', [$difficulty->chart, $difficulty, $otherComment]))
        ->assertNotFound();

    expect(ChartDifficultyComment::count())->toBe(1);
});

test('a non-owner cannot view a private chart\'s difficulty comments', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader')->private())
        ->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertNotFound();
});

test('a non-owner cannot comment on a private chart', function () {
    $owner = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader')->private())
        ->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => 'Sneaky'])
        ->assertNotFound();

    expect(ChartDifficultyComment::count())->toBe(0);
});
