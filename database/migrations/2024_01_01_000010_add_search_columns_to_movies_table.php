<?php
// ========================================
// ADD SEARCH & FILTER COLUMNS TO MOVIES
// ========================================
// File: database/migrations/2024_01_01_000010_add_search_columns_to_movies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            // Additional columns for enhanced search (simplified without specific after clauses)
            $table->string('language', 50)->nullable();
            $table->boolean('has_subtitle')->default(false);
            $table->boolean('is_dubbed')->default(false);
            $table->text('cast')->nullable();
            $table->string('director')->nullable();
            $table->float('popularity')->default(0);
            
            // Indexes for better search performance
            $table->index('language');
            $table->index('has_subtitle');
            $table->index('is_dubbed');
            $table->index('popularity');
        });
    }

    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            // Drop regular indexes
            $table->dropIndex(['language']);
            $table->dropIndex(['has_subtitle']);
            $table->dropIndex(['is_dubbed']);
            $table->dropIndex(['popularity']);
            
            // Drop columns
            $table->dropColumn([
                'language',
                'has_subtitle',
                'is_dubbed',
                'cast',
                'director',
                'popularity'
            ]);
        });
    }
};