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
        Schema::table('watchlist', function (Blueprint $table) {
            // Drop existing unique constraints first
            $table->dropUnique('watchlist_user_movie_unique');
            $table->dropUnique('watchlist_user_series_unique');
            
            // Make movie_id nullable
            $table->foreignId('movie_id')->nullable()->change();
            
            // Recreate unique constraints with nullable support
            $table->unique(['user_id', 'movie_id'], 'watchlist_user_movie_unique');
            $table->unique(['user_id', 'series_id'], 'watchlist_user_series_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            // Drop unique constraints
            $table->dropUnique('watchlist_user_movie_unique');
            $table->dropUnique('watchlist_user_series_unique');
            
            // Make movie_id not nullable again
            $table->foreignId('movie_id')->nullable(false)->change();
            
            // Recreate constraints
            $table->unique(['user_id', 'movie_id'], 'watchlist_user_movie_unique');
            $table->unique(['user_id', 'series_id'], 'watchlist_user_series_unique');
        });
    }
};
