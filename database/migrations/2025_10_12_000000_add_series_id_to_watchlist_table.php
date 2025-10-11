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
            // Add series_id column
            $table->foreignId('series_id')->nullable()->after('movie_id')->constrained('series')->onDelete('cascade');
            
            // Drop old unique constraint
            $table->dropUnique(['user_id', 'movie_id']);
            
            // Add new unique constraints
            $table->unique(['user_id', 'movie_id'], 'watchlist_user_movie_unique');
            $table->unique(['user_id', 'series_id'], 'watchlist_user_series_unique');
            
            // Add index
            $table->index('series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            // Drop new constraints
            $table->dropUnique('watchlist_user_movie_unique');
            $table->dropUnique('watchlist_user_series_unique');
            $table->dropIndex(['series_id']);
            
            // Drop series_id column
            $table->dropForeign(['series_id']);
            $table->dropColumn('series_id');
            
            // Restore old unique constraint
            $table->unique(['user_id', 'movie_id']);
        });
    }
};
