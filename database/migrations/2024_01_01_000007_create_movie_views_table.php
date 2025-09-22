<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movie_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->timestamp('watched_at');
            $table->integer('watch_duration')->nullable(); // in seconds
            $table->string('ip_address');
            $table->timestamps();
            
            $table->index(['user_id', 'movie_id']);
            $table->index('watched_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_views');
    }
};