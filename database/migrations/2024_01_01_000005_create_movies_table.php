<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->integer('tmdb_id')->unique()->nullable();
            $table->string('imdb_id')->unique()->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('embed_url'); // Will be encrypted
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->year('year')->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->decimal('rating', 3, 1)->nullable(); // e.g., 8.5
            $table->enum('quality', ['CAM', 'HD', 'FHD', '4K'])->default('HD');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('view_count')->default(0);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('tmdb_id');
            $table->index('status');
            $table->index(['status', 'created_at']);
            $table->fullText(['title', 'description']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('movies');
    }
};