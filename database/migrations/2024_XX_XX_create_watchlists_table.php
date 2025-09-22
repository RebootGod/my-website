<?php
// ========================================
// WATCHLIST MIGRATION
// ========================================
// File: database/migrations/2024_XX_XX_create_watchlists_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['user_id', 'movie_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('watchlists');
    }
};