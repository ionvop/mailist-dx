<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chart_difficulty_revisions', function (Blueprint $table) {
            // Human-facing, per-difficulty sequence number (1, 2, 3, ...).
            // Nullable so existing rows can be backfilled by a follow-up
            // data migration without locking the table.
            $table->unsignedInteger('revision_number')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_difficulty_revisions', function (Blueprint $table) {
            $table->dropColumn('revision_number');
        });
    }
};
