<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Run the backfill migration against the current database state.
 */
function runRevisionBackfillMigration(): void
{
    $migration = require database_path('migrations/2026_10_12_000000_backfill_chart_difficulty_revision_uploaders.php');

    $migration->up();
}

test('the backfill attributes an existing original revision to the uploader', function () {
    $uploader = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($uploader, 'uploader'))
        ->create(['code' => '1,2,3,4,']);

    $original = ChartDifficultyRevision::factory()
        ->for($difficulty, 'difficulty')
        ->original()
        ->create(['user_id' => null, 'code' => '1,2,3,4,']);

    runRevisionBackfillMigration();

    expect($original->fresh()->user_id)->toBe($uploader->id)
        ->and($difficulty->revisions()->count())->toBe(1);
});

test('the backfill creates a baseline for a difficulty with no revisions', function () {
    $uploader = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($uploader, 'uploader'))
        ->create(['code' => '5,6,7,8,']);

    runRevisionBackfillMigration();

    $revisions = $difficulty->revisions()->get();

    expect($revisions)->toHaveCount(1)
        ->and($revisions->first()->is_original)->toBeTrue()
        ->and($revisions->first()->user_id)->toBe($uploader->id)
        ->and($revisions->first()->code)->toBe('5,6,7,8,');
});

test('the backfill leaves difficulties that already have a baseline untouched', function () {
    $uploader = User::factory()->create();
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for($uploader, 'uploader'))
        ->create(['code' => '1,2,3,4,']);

    ChartDifficultyRevision::factory()
        ->for($difficulty, 'difficulty')
        ->original()
        ->create(['user_id' => $uploader->id, 'code' => '1,2,3,4,']);

    runRevisionBackfillMigration();

    expect($difficulty->revisions()->count())->toBe(1);
});
