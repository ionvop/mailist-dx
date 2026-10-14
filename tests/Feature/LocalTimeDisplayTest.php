<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('chart comments render a UTC datetime for client-side localization', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create([
        'created_at' => Carbon::parse('2026-01-02 03:04:05', 'UTC'),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.comments.index', $chart))
        ->assertOk()
        ->assertSee('data-local-time', false)
        ->assertSee('datetime="'.$comment->created_at->toIso8601String().'"', false)
        ->assertSee('+00:00', false);
});

test('difficulty comments render a UTC datetime for client-side localization', function () {
    $difficulty = ChartDifficulty::factory()->create();
    $comment = $difficulty->comments()->create([
        'user_id' => User::factory()->create()->id,
        'body' => 'Nice work!',
        'created_at' => Carbon::parse('2026-01-02 03:04:05', 'UTC'),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']))
        ->assertOk()
        ->assertSee('data-local-time', false)
        ->assertSee('datetime="'.$comment->created_at->toIso8601String().'"', false)
        ->assertSee('+00:00', false);
});

test('revision timeline renders a UTC datetime for client-side localization', function () {
    $difficulty = ChartDifficulty::factory()->create();
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create([
        'created_at' => Carbon::parse('2026-01-02 03:04:05', 'UTC'),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'history']))
        ->assertOk()
        ->assertSee('data-local-time', false)
        ->assertSee('datetime="'.$revision->created_at->toIso8601String().'"', false)
        ->assertSee('+00:00', false);
});

test('the revision diff endpoint serializes created_at in UTC', function () {
    $difficulty = ChartDifficulty::factory()->create();
    $revision = ChartDifficultyRevision::factory()->for($difficulty, 'difficulty')->create([
        'created_at' => Carbon::parse('2026-01-02 03:04:05', 'UTC'),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('charts.difficulties.revisions.diff', [$difficulty->chart, $difficulty, $revision]))
        ->assertOk()
        ->assertJsonPath('current.created_at', '2026-01-02T03:04:05+00:00');
});
