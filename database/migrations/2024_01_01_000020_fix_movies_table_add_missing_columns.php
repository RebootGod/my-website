<?php
// ========================================
// FIX MOVIES TABLE - ADD MISSING COLUMNS
// ========================================
// File: database/migrations/2024_01_01_000020_fix_movies_table_add_missing_columns.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            // Check and add missing columns one by one
            
            // First, add vote_count if not exists
            if (!Schema::hasColumn('movies', 'vote_count')) {
                $table->integer('vote_count')->default(0)->after('rating');
            }
            
            // Then add other columns in correct order
            if (!Schema::hasColumn('movies', 'popularity')) {
                $table->float('popularity')->default(0)->after('vote_count');
            }
            
            if (!Schema::hasColumn('movies', 'language')) {
                $table->string('language', 50)->nullable()->after('popularity');
            }
            
            if (!Schema::hasColumn('movies', 'original_title')) {
                $table->string('original_title')->nullable()->after('title');
            }
            
            if (!Schema::hasColumn('movies', 'overview')) {
                $table->text('overview')->nullable()->after('slug');
            }
            
            if (!Schema::hasColumn('movies', 'poster_url')) {
                $table->string('poster_url')->nullable()->after('embed_url');
            }
            
            if (!Schema::hasColumn('movies', 'backdrop_url')) {
                $table->string('backdrop_url')->nullable()->after('poster_url');
            }
            
            if (!Schema::hasColumn('movies', 'trailer_url')) {
                $table->string('trailer_url')->nullable()->after('backdrop_url');
            }
            
            if (!Schema::hasColumn('movies', 'runtime')) {
                $table->integer('runtime')->nullable()->after('trailer_url');
            }
            
            if (!Schema::hasColumn('movies', 'release_date')) {
                $table->date('release_date')->nullable()->after('runtime');
            }
            
            if (!Schema::hasColumn('movies', 'has_subtitle')) {
                $table->boolean('has_subtitle')->default(false)->after('quality');
            }
            
            if (!Schema::hasColumn('movies', 'is_dubbed')) {
                $table->boolean('is_dubbed')->default(false)->after('has_subtitle');
            }
            
            if (!Schema::hasColumn('movies', 'cast')) {
                $table->text('cast')->nullable()->after('overview');
            }
            
            if (!Schema::hasColumn('movies', 'director')) {
                $table->string('director')->nullable()->after('cast');
            }
            
            if (!Schema::hasColumn('movies', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_dubbed');
            }
            
            if (!Schema::hasColumn('movies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_featured');
            }
        });
    }

    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['language']);
            $table->dropIndex(['quality']);
            $table->dropIndex(['has_subtitle']);
            $table->dropIndex(['is_dubbed']);
            $table->dropIndex(['popularity']);
            $table->dropIndex(['is_active']);
            
            // Drop columns
            $table->dropColumn([
                'vote_count',
                'popularity',
                'language',
                'original_title',
                'overview',
                'poster_url',
                'backdrop_url',
                'trailer_url',
                'runtime',
                'release_date',
                'has_subtitle',
                'is_dubbed',
                'cast',
                'director',
                'is_featured',
                'is_active'
            ]);
        });
    }
};