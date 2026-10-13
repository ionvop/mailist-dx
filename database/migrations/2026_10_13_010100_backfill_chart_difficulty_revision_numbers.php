<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Assign a per-difficulty sequence number (1, 2, 3, ...) to every existing
     * revision, ordered by id so the oldest snapshot is #1.
     */
    public function up(): void
    {
        DB::table('chart_difficulty_revisions')
            ->select('difficulty_id')
            ->distinct()
            ->orderBy('difficulty_id')
            ->pluck('difficulty_id')
            ->chunk(200)
            ->each(function ($difficultyIds): void {
                foreach ($difficultyIds as $difficultyId) {
                    DB::table('chart_difficulty_revisions')
                        ->where('difficulty_id', $difficultyId)
                        ->orderBy('id')
                        ->pluck('id')
                        ->each(function ($revisionId, $index): void {
                            DB::table('chart_difficulty_revisions')
                                ->where('id', $revisionId)
                                ->update(['revision_number' => $index + 1]);
                        });
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('chart_difficulty_revisions')->update(['revision_number' => null]);
    }
};
