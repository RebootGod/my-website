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
        Schema::table('user_registrations', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['invite_code_id']);
            
            // Make invite_code_id nullable and add new foreign key with SET NULL
            $table->unsignedBigInteger('invite_code_id')->nullable()->change();
            $table->foreign('invite_code_id')->references('id')->on('invite_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_registrations', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['invite_code_id']);
            
            // Restore original constraint (cascade delete)
            $table->unsignedBigInteger('invite_code_id')->nullable(false)->change();
            $table->foreign('invite_code_id')->references('id')->on('invite_codes')->onDelete('cascade');
        });
    }
};
