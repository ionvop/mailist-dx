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
            // Optional, author-editable summary of the change, like a git
            // commit message. Nullable because it may be filled in later.
            $table->string('note', 255)->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_difficulty_revisions', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
