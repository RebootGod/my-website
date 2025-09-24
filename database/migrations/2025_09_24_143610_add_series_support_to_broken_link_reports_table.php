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
        Schema::table('broken_link_reports', function (Blueprint $table) {
            // Add series support
            $table->foreignId('series_id')->nullable()->constrained()->onDelete('cascade')->after('movie_id');
            $table->foreignId('episode_id')->nullable()->constrained()->onDelete('cascade')->after('series_id');

            // Make movie_id nullable since we now support series reports
            $table->foreignId('movie_id')->nullable()->change();

            // Update issue types to include series-specific issues
            $table->enum('issue_type', [
                'not_loading',
                'wrong_movie',
                'wrong_episode', // New for series
                'poor_quality',
                'no_audio',
                'no_subtitle',
                'buffering',
                'other'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broken_link_reports', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropForeign(['episode_id']);
            $table->dropColumn(['series_id', 'episode_id']);

            // Restore movie_id as required
            $table->foreignId('movie_id')->nullable(false)->change();

            // Restore original issue types
            $table->enum('issue_type', [
                'not_loading',
                'wrong_movie',
                'poor_quality',
                'no_audio',
                'no_subtitle',
                'buffering',
                'other'
            ])->change();
        });
    }
};
