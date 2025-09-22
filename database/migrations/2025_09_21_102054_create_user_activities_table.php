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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // login, logout, watch_movie, watch_series, etc.
            $table->string('description'); // Human readable description
            $table->json('metadata')->nullable(); // Additional data (movie_id, series_id, etc.)
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('activity_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'activity_at']);
            $table->index(['activity_type', 'activity_at']);
            $table->index('activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
