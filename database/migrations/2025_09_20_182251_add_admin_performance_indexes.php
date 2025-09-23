<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->toArray();

        return in_array($indexName, $indexes);
    }

    /**
     * Run the migrations - Add performance indexes for admin functionality
     */
    public function up(): void
    {
        // Movies table indexes
        Schema::table('movies', function (Blueprint $table) {
            // Check if indexes don't already exist
            if (!$this->indexExists('movies', 'idx_movies_status_created')) {
                $table->index(['status', 'created_at'], 'idx_movies_status_created');
            }

            if (!$this->indexExists('movies', 'idx_movies_year')) {
                $table->index('year', 'idx_movies_year');
            }

            if (!$this->indexExists('movies', 'idx_movies_view_count')) {
                $table->index('view_count', 'idx_movies_view_count');
            }

            if (!$this->indexExists('movies', 'idx_movies_updated')) {
                $table->index('updated_at', 'idx_movies_updated');
            }
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before creating indexes
            if (Schema::hasColumn('users', 'last_login_at')) {
                if (!$this->indexExists('users', 'idx_users_status_login')) {
                    $table->index(['status', 'last_login_at'], 'idx_users_status_login');
                }
                if (!$this->indexExists('users', 'idx_users_last_login')) {
                    $table->index('last_login_at', 'idx_users_last_login');
                }
            }
            if (!$this->indexExists('users', 'idx_users_role_created')) {
                $table->index(['role', 'created_at'], 'idx_users_role_created');
            }
        });

        // Series table indexes (if exists)
        if (Schema::hasTable('series')) {
            Schema::table('series', function (Blueprint $table) {
                if (!$this->indexExists('series', 'idx_series_status_created')) {
                    $table->index(['status', 'created_at'], 'idx_series_status_created');
                }
                if (!$this->indexExists('series', 'idx_series_view_count')) {
                    $table->index('view_count', 'idx_series_view_count');
                }
                if (!$this->indexExists('series', 'idx_series_year')) {
                    $table->index('year', 'idx_series_year');
                }
            });
        }

        // Movie views table indexes (for analytics)
        if (Schema::hasTable('movie_views')) {
            Schema::table('movie_views', function (Blueprint $table) {
                // Composite index for trending calculations
                $table->index(['movie_id', 'created_at'], 'idx_movie_views_movie_date');
                $table->index('created_at', 'idx_movie_views_created');
            });
        }

        // Invite codes table indexes
        if (Schema::hasTable('invite_codes')) {
            Schema::table('invite_codes', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_invite_codes_status_created');
            });
        }
                    // Removed index creation for 'used_at' as it does not exist

        // Broken link reports table indexes
        if (Schema::hasTable('broken_link_reports')) {
            Schema::table('broken_link_reports', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_reports_status_created');
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Movies table indexes
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_movies_status_created');
            $table->dropIndex('idx_movies_year');
            $table->dropIndex('idx_movies_view_count');
            $table->dropIndex('idx_movies_updated');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex('idx_users_status_login');
                $table->dropIndex('idx_users_last_login');
            }
            $table->dropIndex('idx_users_role_created');
        });

        // Series table indexes
        if (Schema::hasTable('series')) {
            Schema::table('series', function (Blueprint $table) {
                $table->dropIndex('idx_series_status_created');
                $table->dropIndex('idx_series_view_count');
                $table->dropIndex('idx_series_year');
            });
        }

        // Movie views table indexes
        if (Schema::hasTable('movie_views')) {
            Schema::table('movie_views', function (Blueprint $table) {
                $table->dropIndex('idx_movie_views_movie_date');
                $table->dropIndex('idx_movie_views_created');
            });
        }

        // Invite codes table indexes
        if (Schema::hasTable('invite_codes')) {
            Schema::table('invite_codes', function (Blueprint $table) {
                $table->dropIndex('idx_invite_codes_status_created');
            });
        }
                    // Removed index drop for 'used_at' as it does not exist

        // Broken link reports table indexes
        if (Schema::hasTable('broken_link_reports')) {
            Schema::table('broken_link_reports', function (Blueprint $table) {
                $table->dropIndex('idx_reports_status_created');
            });
        }
    }
};
