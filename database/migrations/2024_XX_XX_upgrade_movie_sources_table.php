<?php
// ========================================
// MIGRATION TO UPGRADE MOVIE SOURCES TABLE
// ========================================
// File: database/migrations/2024_XX_XX_upgrade_movie_sources_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movie_sources', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('movie_sources', 'type')) {
                $table->enum('type', ['embed', 'direct', 'torrent'])->default('embed')->after('quality');
            }
            
            if (!Schema::hasColumn('movie_sources', 'language')) {
                $table->string('language')->default('English')->after('type');
            }
            
            if (!Schema::hasColumn('movie_sources', 'has_subtitle')) {
                $table->boolean('has_subtitle')->default(false)->after('language');
            }
            
            if (!Schema::hasColumn('movie_sources', 'report_count')) {
                $table->integer('report_count')->default(0)->after('priority');
            }
            
            if (!Schema::hasColumn('movie_sources', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('report_count');
            }
            
            if (!Schema::hasColumn('movie_sources', 'added_by')) {
                $table->string('added_by')->nullable()->after('last_checked_at');
            }
            
            if (!Schema::hasColumn('movie_sources', 'notes')) {
                $table->text('notes')->nullable()->after('added_by');
            }
            
            // Update quality enum to include more options
            $table->string('quality', 10)->change();
            
            // Add indexes for better performance
            $table->index(['movie_id', 'is_active', 'quality']);
            $table->index('report_count');
        });
    }

    public function down()
    {
        Schema::table('movie_sources', function (Blueprint $table) {
            $table->dropColumn(['type', 'language', 'has_subtitle', 'report_count', 'last_checked_at', 'added_by', 'notes']);
            $table->dropIndex(['movie_id', 'is_active', 'quality']);
            $table->dropIndex(['report_count']);
        });
    }
};