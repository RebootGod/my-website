<?php
// ========================================
// FIX USER_AGENT COLUMN LENGTH IN USER_ACTIVITIES
// ========================================
// File: database/migrations/2025_10_09_fix_user_agent_column_length_in_user_activities.php
//
// BUG FIX: SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'user_agent'
// Issue: Modern browsers have very long User-Agent strings (500-1000+ chars)
//        Current column VARCHAR(255) is too small
// Solution: Change to TEXT type (65,535 bytes max) to handle all User-Agent strings
//
// Related Error: app/Services/UserActivityService.php:34 UserActivity::create()

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
        Schema::table('user_activities', function (Blueprint $table) {
            // Change user_agent from VARCHAR(255) to TEXT
            // TEXT supports up to 65,535 bytes (more than enough for any User-Agent)
            $table->text('user_agent')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Revert to VARCHAR(255) (not recommended, may truncate data)
            $table->string('user_agent')->nullable()->change();
        });
    }
};

