<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->integer('tmdb_id')->unique()->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
            
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('genres');
    }
};
