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
        Schema::table('series', function (Blueprint $table) {
            // Add all the required fields for series
            $table->integer('tmdb_id')->nullable()->unique();
            $table->string('original_title')->nullable();
            $table->string('slug')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->integer('year')->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->decimal('rating', 3, 1)->nullable();
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->integer('view_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('overview')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->integer('runtime')->nullable();
            $table->date('release_date')->nullable();
            $table->integer('vote_count')->nullable();
            $table->decimal('popularity', 10, 2)->nullable();
            $table->string('language', 5)->nullable();
            $table->boolean('has_subtitle')->default(false);
            $table->boolean('is_dubbed')->default(false);
            $table->text('cast')->nullable();
            $table->string('director')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('first_air_date')->nullable();
            $table->date('last_air_date')->nullable();
            $table->integer('number_of_seasons')->nullable();
            $table->integer('number_of_episodes')->nullable();
            $table->boolean('in_production')->default(false);
            $table->string('type')->nullable();

            // Add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop all added columns
            $table->dropColumn([
                'tmdb_id', 'original_title', 'slug', 'poster_path', 'backdrop_path',
                'year', 'duration', 'rating', 'status', 'view_count', 'created_by',
                'updated_by', 'overview', 'poster_url', 'backdrop_url', 'trailer_url',
                'runtime', 'release_date', 'vote_count', 'popularity', 'language',
                'has_subtitle', 'is_dubbed', 'cast', 'director', 'is_featured',
                'is_active', 'first_air_date', 'last_air_date', 'number_of_seasons',
                'number_of_episodes', 'in_production', 'type'
            ]);
        });
    }
};