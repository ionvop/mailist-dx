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
        Schema::create('charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->string('title_alias')->nullable();
            $table->string('artist');
            $table->string('artist_alias')->nullable();
            $table->string('artist_link')->nullable();
            $table->string('illustrator')->nullable();
            $table->string('illustrator_link')->nullable();
            $table->string('audio_bpm')->nullable();

            $table->text('info')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();

            $table->string('status')->default('wip');
            $table->boolean('is_private')->default(false);

            $table->string('art_path')->nullable();
            $table->string('track_path')->nullable();
            $table->string('pv_path')->nullable();
            $table->string('maidata_path')->nullable();
            $table->string('download_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charts');
    }
};
