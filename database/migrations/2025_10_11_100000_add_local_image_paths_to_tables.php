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
        // Add local_poster_path and local_backdrop_path to movies table
        Schema::table('movies', function (Blueprint $table) {
            $table->string('local_poster_path')->nullable()->after('poster_path');
            $table->string('local_backdrop_path')->nullable()->after('backdrop_path');
            
            $table->index('local_poster_path');
            $table->index('local_backdrop_path');
        });

        // Add local_poster_path and local_backdrop_path to series table
        Schema::table('series', function (Blueprint $table) {
            $table->string('local_poster_path')->nullable()->after('poster_url');
            $table->string('local_backdrop_path')->nullable()->after('backdrop_url');
            
            $table->index('local_poster_path');
            $table->index('local_backdrop_path');
        });

        // Add local_poster_path to series_seasons table
        Schema::table('series_seasons', function (Blueprint $table) {
            $table->string('local_poster_path')->nullable()->after('poster_path');
            
            $table->index('local_poster_path');
        });

        // Add local_still_path to series_episodes table
        Schema::table('series_episodes', function (Blueprint $table) {
            $table->string('local_still_path')->nullable()->after('still_path');
            
            $table->index('local_still_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex(['local_poster_path']);
            $table->dropIndex(['local_backdrop_path']);
            $table->dropColumn(['local_poster_path', 'local_backdrop_path']);
        });

        Schema::table('series', function (Blueprint $table) {
            $table->dropIndex(['local_poster_path']);
            $table->dropIndex(['local_backdrop_path']);
            $table->dropColumn(['local_poster_path', 'local_backdrop_path']);
        });

        Schema::table('series_seasons', function (Blueprint $table) {
            $table->dropIndex(['local_poster_path']);
            $table->dropColumn('local_poster_path');
        });

        Schema::table('series_episodes', function (Blueprint $table) {
            $table->dropIndex(['local_still_path']);
            $table->dropColumn('local_still_path');
        });
    }
};
