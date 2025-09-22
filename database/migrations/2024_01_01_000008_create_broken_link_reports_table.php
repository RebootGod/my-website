<?php
// ========================================
// BROKEN LINK REPORTS MIGRATION
// ========================================
// File: database/migrations/2024_01_01_000008_create_broken_link_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broken_link_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('movie_source_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('issue_type', [
                'not_loading',
                'wrong_movie', 
                'poor_quality',
                'no_audio',
                'no_subtitle',
                'buffering',
                'other'
            ]);
            $table->text('description')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'fixed', 'dismissed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['movie_id', 'status']);
            $table->index(['movie_source_id', 'status']);
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broken_link_reports');
    }
};