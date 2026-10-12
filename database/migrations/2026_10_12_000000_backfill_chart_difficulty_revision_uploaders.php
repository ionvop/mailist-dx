<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill the original revision for difficulties uploaded before the
     * baseline was recorded at upload time.
     *
     * Difficulties that already have revisions get their existing original
     * snapshot attributed to the chart's uploader. Difficulties with no
     * revisions at all get a baseline snapshot created from their current code.
     */
    public function up(): void
    {
        $this->attributeExistingOriginals();
        $this->createMissingOriginals();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill; nothing to reverse.
    }

    /**
     * Attribute existing original snapshots to the chart's uploader.
     */
    private function attributeExistingOriginals(): void
    {
        DB::table('chart_difficulty_revisions')
            ->where('is_original', true)
            ->whereNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($revisions): void {
                $uploaders = DB::table('chart_difficulties')
                    ->join('charts', 'charts.id', '=', 'chart_difficulties.chart_id')
                    ->whereIn('chart_difficulties.id', $revisions->pluck('difficulty_id')->unique())
                    ->pluck('charts.uploader_id', 'chart_difficulties.id');

                foreach ($revisions as $revision) {
                    $uploaderId = $uploaders[$revision->difficulty_id] ?? null;

                    if ($uploaderId === null) {
                        continue;
                    }

                    DB::table('chart_difficulty_revisions')
                        ->where('id', $revision->id)
                        ->update(['user_id' => $uploaderId]);
                }
            });
    }

    /**
     * Create a baseline snapshot for difficulties that have none.
     */
    private function createMissingOriginals(): void
    {
        DB::table('chart_difficulties')
            ->join('charts', 'charts.id', '=', 'chart_difficulties.chart_id')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('chart_difficulty_revisions')
                    ->whereColumn('chart_difficulty_revisions.difficulty_id', 'chart_difficulties.id');
            })
            ->orderBy('chart_difficulties.id')
            ->select([
                'chart_difficulties.id as difficulty_id',
                'chart_difficulties.code',
                'chart_difficulties.created_at',
                'charts.uploader_id',
            ])
            ->chunk(500, function ($difficulties): void {
                $now = now();

                $rows = $difficulties->map(fn ($difficulty): array => [
                    'difficulty_id' => $difficulty->difficulty_id,
                    'user_id' => $difficulty->uploader_id,
                    'code' => $difficulty->code,
                    'is_original' => true,
                    'created_at' => $difficulty->created_at ?? $now,
                ])->all();

                DB::table('chart_difficulty_revisions')->insert($rows);
            });
    }
};
