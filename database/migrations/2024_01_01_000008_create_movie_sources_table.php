<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movie_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->string('source_name'); // Server 1, Server 2, etc
            $table->text('embed_url'); // Encrypted
            $table->enum('quality', ['CAM', 'HD', 'FHD', '4K'])->default('HD');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher = preferred
            $table->timestamps();
            
            $table->index(['movie_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_sources');
    }
};