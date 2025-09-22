<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes for optimal query execution
     */
    public function up(): void
    {
        // =======================================
        // MOVIES TABLE PERFORMANCE INDEXES
        // =======================================
        Schema::table('movies', function (Blueprint $table) {
            // Frequently queried columns in HomeController and searches
            $table->index('rating', 'idx_movies_rating');
            $table->index('year', 'idx_movies_year');
            $table->index('view_count', 'idx_movies_view_count');
            $table->index('is_active', 'idx_movies_is_active');
            $table->index('is_featured', 'idx_movies_is_featured');
            $table->index('release_date', 'idx_movies_release_date');

            // Composite indexes for common filter combinations
            $table->index(['is_active', 'rating'], 'idx_movies_active_rating');
            $table->index(['is_active', 'year'], 'idx_movies_active_year');
            $table->index(['is_active', 'created_at'], 'idx_movies_active_created');
            $table->index(['is_featured', 'is_active'], 'idx_movies_featured_active');

            // Performance index for trending calculations (7-day views)
            $table->index(['is_active', 'view_count', 'created_at'], 'idx_movies_trending');
        });

        // =======================================
        // MOVIE_GENRES TABLE PERFORMANCE INDEXES
        // =======================================
        Schema::table('movie_genres', function (Blueprint $table) {
            // Individual indexes for genre filtering
            $table->index('genre_id', 'idx_movie_genres_genre_id');
            $table->index('movie_id', 'idx_movie_genres_movie_id');
        });

        // =======================================
        // MOVIE_VIEWS TABLE PERFORMANCE INDEXES
        // =======================================
        Schema::table('movie_views', function (Blueprint $table) {
            // Composite index for trending movies calculation
            $table->index(['movie_id', 'created_at'], 'idx_movie_views_trending');
            $table->index(['created_at', 'movie_id'], 'idx_movie_views_date_movie');
        });

        // =======================================
        // SEARCH_HISTORIES TABLE PERFORMANCE INDEXES
        // =======================================
        Schema::table('search_histories', function (Blueprint $table) {
            // Composite index for popular searches calculation
            $table->index(['search_term', 'created_at'], 'idx_search_histories_term_date');
        });

        // =======================================
        // GENRES TABLE PERFORMANCE INDEXES
        // =======================================
        Schema::table('genres', function (Blueprint $table) {
            // Index for alphabetical ordering
            $table->index('name', 'idx_genres_name');
            $table->index('slug', 'idx_genres_slug');
        });

        // =======================================
        // MOVIE_SOURCES TABLE PERFORMANCE INDEXES
        // =======================================
        if (Schema::hasTable('movie_sources')) {
            Schema::table('movie_sources', function (Blueprint $table) {
                // Quality filtering index
                $table->index('quality', 'idx_movie_sources_quality');
                $table->index(['movie_id', 'quality'], 'idx_movie_sources_movie_quality');
            });
        }

        // =======================================
        // SERIES TABLE PERFORMANCE INDEXES (if exists)
        // =======================================
        if (Schema::hasTable('series')) {
            Schema::table('series', function (Blueprint $table) {
                $table->index('is_active', 'idx_series_is_active');
                $table->index('rating', 'idx_series_rating');
                $table->index('year', 'idx_series_year');
                $table->index(['is_active', 'created_at'], 'idx_series_active_created');
            });
        }

        // =======================================
        // SERIES_GENRES TABLE PERFORMANCE INDEXES (if exists)
        // =======================================
        if (Schema::hasTable('series_genres')) {
            Schema::table('series_genres', function (Blueprint $table) {
                $table->index('genre_id', 'idx_series_genres_genre_id');
                $table->index('series_id', 'idx_series_genres_series_id');
            });
        }
    }

    /**
     * Reverse the migrations - Remove performance indexes
     */
    public function down(): void
    {
        // =======================================
        // DROP MOVIES TABLE INDEXES
        // =======================================
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_movies_rating');
            $table->dropIndex('idx_movies_year');
            $table->dropIndex('idx_movies_view_count');
            $table->dropIndex('idx_movies_is_active');
            $table->dropIndex('idx_movies_is_featured');
            $table->dropIndex('idx_movies_release_date');
            $table->dropIndex('idx_movies_active_rating');
            $table->dropIndex('idx_movies_active_year');
            $table->dropIndex('idx_movies_active_created');
            $table->dropIndex('idx_movies_featured_active');
            $table->dropIndex('idx_movies_trending');
        });

        // =======================================
        // DROP MOVIE_GENRES TABLE INDEXES
        // =======================================
        Schema::table('movie_genres', function (Blueprint $table) {
            $table->dropIndex('idx_movie_genres_genre_id');
            $table->dropIndex('idx_movie_genres_movie_id');
        });

        // =======================================
        // DROP MOVIE_VIEWS TABLE INDEXES
        // =======================================
        Schema::table('movie_views', function (Blueprint $table) {
            $table->dropIndex('idx_movie_views_trending');
            $table->dropIndex('idx_movie_views_date_movie');
        });

        // =======================================
        // DROP SEARCH_HISTORIES TABLE INDEXES
        // =======================================
        Schema::table('search_histories', function (Blueprint $table) {
            $table->dropIndex('idx_search_histories_term_date');
        });

        // =======================================
        // DROP GENRES TABLE INDEXES
        // =======================================
        Schema::table('genres', function (Blueprint $table) {
            $table->dropIndex('idx_genres_name');
            $table->dropIndex('idx_genres_slug');
        });

        // =======================================
        // DROP MOVIE_SOURCES TABLE INDEXES
        // =======================================
        if (Schema::hasTable('movie_sources')) {
            Schema::table('movie_sources', function (Blueprint $table) {
                $table->dropIndex('idx_movie_sources_quality');
                $table->dropIndex('idx_movie_sources_movie_quality');
            });
        }

        // =======================================
        // DROP SERIES TABLE INDEXES
        // =======================================
        if (Schema::hasTable('series')) {
            Schema::table('series', function (Blueprint $table) {
                $table->dropIndex('idx_series_is_active');
                $table->dropIndex('idx_series_rating');
                $table->dropIndex('idx_series_year');
                $table->dropIndex('idx_series_active_created');
            });
        }

        // =======================================
        // DROP SERIES_GENRES TABLE INDEXES
        // =======================================
        if (Schema::hasTable('series_genres')) {
            Schema::table('series_genres', function (Blueprint $table) {
                $table->dropIndex('idx_series_genres_genre_id');
                $table->dropIndex('idx_series_genres_series_id');
            });
        }
    }
};