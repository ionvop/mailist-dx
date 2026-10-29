<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalise stored simai code to LF line endings.
     *
     * Chart code and its revision snapshots are stored with LF only. Rows
     * written before that invariant existed may still contain CRLF (or lone
     * CR) line endings, which made the code editor's caret offsets and the
     * revision diffs disagree with the stored maidata file.
     *
     * Stored `maidata.txt` files are not touched here: every write path now
     * emits LF, so a file is converted the next time its chart is modified.
     */
    public function up(): void
    {
        $this->normalizeTable('chart_difficulties');
        $this->normalizeTable('chart_difficulty_revisions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalisation; nothing to reverse.
    }

    /**
     * Rewrite CRLF and lone CR line endings to LF in the given table's code column.
     */
    private function normalizeTable(string $table): void
    {
        DB::table($table)
            ->select(['id', 'code'])
            ->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $normalized = str_replace(["\r\n", "\r"], "\n", (string) $row->code);

                    if ($normalized === $row->code) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['code' => $normalized]);
                }
            });
    }
};
