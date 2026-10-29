<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Run the line-ending backfill migration against the current database state.
 */
function runLineEndingBackfillMigration(): void
{
    $migration = require database_path('migrations/2026_10_20_000000_normalize_chart_code_line_endings.php');

    $migration->up();
}

test('the backfill normalises CRLF chart code to LF', function () {
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for(User::factory(), 'uploader'))
        ->create(['code' => "1,2,3,\r\n4,5,6,"]);

    // Bypass the model mutator to simulate a legacy row.
    DB::table('chart_difficulties')->where('id', $difficulty->id)->update(['code' => "1,2,3,\r\n4,5,6,"]);

    runLineEndingBackfillMigration();

    expect(DB::table('chart_difficulties')->where('id', $difficulty->id)->value('code'))
        ->toBe("1,2,3,\n4,5,6,");
});

test('the backfill normalises lone CR chart code to LF', function () {
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for(User::factory(), 'uploader'))
        ->create();

    DB::table('chart_difficulties')->where('id', $difficulty->id)->update(['code' => "1,2,3,\r4,5,6,"]);

    runLineEndingBackfillMigration();

    expect(DB::table('chart_difficulties')->where('id', $difficulty->id)->value('code'))
        ->toBe("1,2,3,\n4,5,6,");
});

test('the backfill normalises CRLF revision code to LF', function () {
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for(User::factory(), 'uploader'))
        ->create();

    $revision = ChartDifficultyRevision::factory()
        ->for($difficulty, 'difficulty')
        ->create();

    DB::table('chart_difficulty_revisions')->where('id', $revision->id)->update(['code' => "7,8,9,\r\n1,2,3,"]);

    runLineEndingBackfillMigration();

    expect(DB::table('chart_difficulty_revisions')->where('id', $revision->id)->value('code'))
        ->toBe("7,8,9,\n1,2,3,");
});

test('the backfill leaves LF code untouched', function () {
    $difficulty = ChartDifficulty::factory()
        ->for(Chart::factory()->for(User::factory(), 'uploader'))
        ->create(['code' => "1,2,3,\n4,5,6,"]);

    runLineEndingBackfillMigration();

    expect(DB::table('chart_difficulties')->where('id', $difficulty->id)->value('code'))
        ->toBe("1,2,3,\n4,5,6,");
});
