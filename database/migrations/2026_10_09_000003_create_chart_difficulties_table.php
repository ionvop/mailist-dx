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
        Schema::create('chart_difficulties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_id')->constrained('charts')->cascadeOnDelete();

            $table->string('chart_index');
            $table->string('difficulty');
            $table->string('difficulty_alias')->nullable();
            $table->string('difficulty_color')->nullable();
            $table->string('difficulty_value')->nullable();
            $table->string('charter')->nullable();
            $table->text('info')->nullable();

            $table->longText('code');
            $table->string('fingerprint')->nullable();
            $table->json('extra')->nullable();
            $table->unsignedInteger('ranked_rating')->default(0);

            $table->timestamps();

            $table->unique(['chart_id', 'chart_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_difficulties');
    }
};
