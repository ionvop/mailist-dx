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
        Schema::create('chart_difficulty_improvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('difficulty_id')->constrained('chart_difficulties')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('body');

            // Optional code context: a 1-indexed line range plus the snippet
            // extracted from the difficulty's code at the time of posting.
            $table->unsignedInteger('line_start')->nullable();
            $table->unsignedInteger('line_end')->nullable();
            $table->text('snippet')->nullable();

            // Resolution state, set by an editor (owner or collaborator).
            $table->string('status')->default('open');
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('revision_id')->nullable()->constrained('chart_difficulty_revisions')->nullOnDelete();

            $table->timestamps();

            $table->index(['difficulty_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_difficulty_improvements');
    }
};
