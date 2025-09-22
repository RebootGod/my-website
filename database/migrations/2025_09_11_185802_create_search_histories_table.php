<?php
// ========================================
// SEARCH HISTORY MIGRATION
// ========================================
// File: database/migrations/2024_XX_XX_create_search_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('search_term');
            $table->integer('results_count')->default(0);
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('search_term');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_histories');
    }
};