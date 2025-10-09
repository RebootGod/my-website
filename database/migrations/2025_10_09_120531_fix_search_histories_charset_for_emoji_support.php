<?php
// ========================================
// FIX SEARCH_HISTORIES CHARSET FOR EMOJI SUPPORT
// ========================================
// File: database/migrations/2025_10_09_fix_search_histories_charset_for_emoji_support.php
// 
// BUG FIX: SQLSTATE[HY000]: General error: 1366 Incorrect string value
// Issue: Users can input emoji in search (🎬, 😊, etc) but table charset is utf8/latin1
// Solution: Convert table and column to utf8mb4 to support 4-byte Unicode characters
// 
// Related Error: app/Http/Controllers/HomeController.php:37 SearchHistory::create()

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert table and all string columns to utf8mb4 for emoji support
        DB::statement('ALTER TABLE search_histories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Explicitly convert search_term column (most important)
        DB::statement('ALTER TABLE search_histories MODIFY search_term VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Convert ip_address column as well
        DB::statement('ALTER TABLE search_histories MODIFY ip_address VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to utf8 (not recommended, may lose emoji data)
        DB::statement('ALTER TABLE search_histories CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        DB::statement('ALTER TABLE search_histories MODIFY search_term VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        DB::statement('ALTER TABLE search_histories MODIFY ip_address VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL');
    }
};

