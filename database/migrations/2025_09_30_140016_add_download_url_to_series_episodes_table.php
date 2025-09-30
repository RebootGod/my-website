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
        Schema::table('series_episodes', function (Blueprint $table) {
            $table->text('download_url')->nullable()->after('embed_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series_episodes', function (Blueprint $table) {
            $table->dropColumn('download_url');
        });
    }
};
