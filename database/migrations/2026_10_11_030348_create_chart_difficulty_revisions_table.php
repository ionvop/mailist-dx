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
        Schema::create('chart_difficulty_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('difficulty_id')->constrained('chart_difficulties')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->longText('code');
            $table->boolean('is_original')->default(false);

            // Revisions are immutable, so only a creation timestamp is kept.
            $table->timestamp('created_at')->nullable();

            $table->index(['difficulty_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_difficulty_revisions');
    }
};
