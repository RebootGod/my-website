<?php
/**
 * Migration Cleanup Script
 *
 * This script reorganizes all migration files into a clean, sequential order
 * with proper dependency management and consistent naming.
 */

require_once __DIR__ . '/../vendor/autoload.php';

class MigrationCleanup
{
    private $migrationsPath;
    private $backupPath;

    public function __construct()
    {
        $this->migrationsPath = __DIR__ . '/../database/migrations';
        $this->backupPath = __DIR__ . '/../database/migrations_backup';
    }

    public function run()
    {
        $this->log("🧹 Starting Migration Cleanup...");

        // Step 1: Backup existing migrations
        $this->backupExistingMigrations();

        // Step 2: Generate clean migrations
        $this->generateCleanMigrations();

        $this->log("✅ Migration cleanup completed!");
        $this->log("📁 Original files backed up to: database/migrations_backup/");
        $this->log("🆕 Clean migrations generated in: database/migrations/");
    }

    private function backupExistingMigrations()
    {
        $this->log("📦 Backing up existing migrations...");

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $files = glob($this->migrationsPath . '/*.php');
        foreach ($files as $file) {
            $filename = basename($file);
            copy($file, $this->backupPath . '/' . $filename);
            unlink($file);
        }

        $this->log("✅ Backed up " . count($files) . " migration files");
    }

    private function generateCleanMigrations()
    {
        $this->log("🔨 Generating clean migrations...");

        $migrations = [
            // Core Laravel tables
            '000001' => $this->getUsersTable(),
            '000002' => $this->getCacheTable(),
            '000003' => $this->getJobsTable(),
            '000004' => $this->getPersonalAccessTokensTable(),

            // Role & Permission system
            '000005' => $this->getRolesTable(),
            '000006' => $this->getPermissionsTable(),
            '000007' => $this->getPermissionRoleTable(),
            '000008' => $this->getUserRoleColumn(),

            // Core content tables
            '000009' => $this->getGenresTable(),
            '000010' => $this->getMoviesTable(),
            '000011' => $this->getMovieGenresTable(),
            '000012' => $this->getMovieSourcesTable(),

            // Series tables
            '000013' => $this->getSeriesTable(),
            '000014' => $this->getSeriesGenresTable(),
            '000015' => $this->getSeriesSeasonsTable(),
            '000016' => $this->getSeriesEpisodesTable(),

            // User interaction tables
            '000017' => $this->getWatchlistsTable(),
            '000018' => $this->getMovieViewsTable(),
            '000019' => $this->getSeriesViewsTable(),
            '000020' => $this->getSeriesEpisodeViewsTable(),

            // User management
            '000021' => $this->getInviteCodesTable(),
            '000022' => $this->getUserRegistrationsTable(),
            '000023' => $this->getSearchHistoriesTable(),

            // Reporting & logging
            '000024' => $this->getBrokenLinkReportsTable(),
            '000025' => $this->getUserActionLogsTable(),
            '000026' => $this->getAdminActionLogsTable(),
            '000027' => $this->getAuditLogsTable(),
            '000028' => $this->getUserActivitiesTable(),

            // Performance indexes (last)
            '000029' => $this->getPerformanceIndexes(),
        ];

        foreach ($migrations as $number => $content) {
            $filename = "2024_01_01_{$number}_" . $this->extractTableName($content) . ".php";
            $filepath = $this->migrationsPath . '/' . $filename;
            file_put_contents($filepath, $content);
        }

        $this->log("✅ Generated " . count($migrations) . " clean migration files");
    }

    private function extractTableName($content)
    {
        if (preg_match("/Schema::create\('([^']+)'/", $content, $matches)) {
            return "create_{$matches[1]}_table";
        }
        if (preg_match("/Schema::table\('([^']+)'/", $content, $matches)) {
            return "modify_{$matches[1]}_table";
        }
        if (strpos($content, 'performance') !== false) {
            return "add_performance_indexes";
        }
        return "migration";
    }

    private function getUsersTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('email')->unique();
            \$table->timestamp('email_verified_at')->nullable();
            \$table->string('password');
            \$table->string('avatar_path')->nullable();
            \$table->enum('status', ['active', 'suspended', 'banned'])->default('active');
            \$table->timestamp('last_login')->nullable();
            \$table->rememberToken();
            \$table->timestamps();

            \$table->index(['email', 'status']);
            \$table->index('last_login');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
";
    }

    private function getCacheTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cache', function (Blueprint \$table) {
            \$table->string('key')->primary();
            \$table->mediumText('value');
            \$table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint \$table) {
            \$table->string('key')->primary();
            \$table->string('owner');
            \$table->integer('expiration');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
";
    }

    private function getJobsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobs', function (Blueprint \$table) {
            \$table->bigIncrements('id');
            \$table->string('queue')->index();
            \$table->longText('payload');
            \$table->unsignedTinyInteger('attempts');
            \$table->unsignedInteger('reserved_at')->nullable();
            \$table->unsignedInteger('available_at');
            \$table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint \$table) {
            \$table->string('id')->primary();
            \$table->string('name');
            \$table->integer('total_jobs');
            \$table->integer('pending_jobs');
            \$table->integer('failed_jobs');
            \$table->longText('failed_job_ids');
            \$table->mediumText('options')->nullable();
            \$table->integer('cancelled_at')->nullable();
            \$table->integer('created_at');
            \$table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint \$table) {
            \$table->id();
            \$table->string('uuid')->unique();
            \$table->text('connection');
            \$table->text('queue');
            \$table->longText('payload');
            \$table->longText('exception');
            \$table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
";
    }

    private function getPersonalAccessTokensTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('personal_access_tokens', function (Blueprint \$table) {
            \$table->id();
            \$table->morphs('tokenable');
            \$table->string('name');
            \$table->string('token', 64)->unique();
            \$table->text('abilities')->nullable();
            \$table->timestamp('last_used_at')->nullable();
            \$table->timestamp('expires_at')->nullable();
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
";
    }

    private function getRolesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->unique();
            \$table->string('display_name');
            \$table->text('description')->nullable();
            \$table->integer('level')->default(0);
            \$table->timestamps();

            \$table->index('level');
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
";
    }

    private function getPermissionsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->unique();
            \$table->string('display_name');
            \$table->text('description')->nullable();
            \$table->string('category')->nullable();
            \$table->timestamps();

            \$table->index('category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
";
    }

    private function getPermissionRoleTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permission_role', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('permission_id')->constrained()->onDelete('cascade');
            \$table->foreignId('role_id')->constrained()->onDelete('cascade');
            \$table->timestamps();

            \$table->unique(['permission_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_role');
    }
};
";
    }

    private function getUserRoleColumn()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint \$table) {
            \$table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            \$table->index('role_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint \$table) {
            \$table->dropForeign(['role_id']);
            \$table->dropColumn('role_id');
        });
    }
};
";
    }

    private function getGenresTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('genres', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->unique();
            \$table->string('slug')->unique();
            \$table->text('description')->nullable();
            \$table->string('color', 7)->default('#6B7280');
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();

            \$table->index(['is_active', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('genres');
    }
};
";
    }

    private function getMoviesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movies', function (Blueprint \$table) {
            \$table->id();
            \$table->string('title');
            \$table->text('description')->nullable();
            \$table->string('poster_path')->nullable();
            \$table->string('backdrop_path')->nullable();
            \$table->string('tmdb_id')->unique();
            \$table->enum('status', ['draft', 'published', 'archived'])->default('published');
            \$table->decimal('vote_average', 3, 1)->default(0);
            \$table->integer('vote_count')->default(0);
            \$table->date('release_date')->nullable();
            \$table->integer('runtime')->nullable();
            \$table->string('original_language', 10)->nullable();
            \$table->json('genres')->nullable();
            \$table->string('director')->nullable();
            \$table->text('cast')->nullable();
            \$table->string('trailer_url')->nullable();
            \$table->text('embed_url')->nullable();
            \$table->enum('quality', ['CAM', 'HD', 'FHD', '4K'])->default('HD');
            \$table->boolean('is_active')->default(true);
            \$table->integer('view_count')->default(0);
            \$table->timestamps();

            // Search indexes
            \$table->fullText(['title', 'description']);
            \$table->index(['status', 'is_active']);
            \$table->index(['release_date', 'vote_average']);
            \$table->index('view_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movies');
    }
};
";
    }

    private function getMovieGenresTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movie_genres', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('movie_id')->constrained()->onDelete('cascade');
            \$table->foreignId('genre_id')->constrained()->onDelete('cascade');
            \$table->timestamps();

            \$table->unique(['movie_id', 'genre_id']);
            \$table->index('genre_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_genres');
    }
};
";
    }

    private function getMovieSourcesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movie_sources', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('movie_id')->constrained()->onDelete('cascade');
            \$table->string('source_name');
            \$table->text('embed_url');
            \$table->enum('quality', ['CAM', 'HD', 'FHD', '4K'])->default('HD');
            \$table->boolean('is_active')->default(true);
            \$table->integer('priority')->default(0);
            \$table->timestamps();

            \$table->index(['movie_id', 'is_active']);
            \$table->index('priority');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_sources');
    }
};
";
    }

    private function getSeriesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series', function (Blueprint \$table) {
            \$table->id();
            \$table->string('title');
            \$table->text('description')->nullable();
            \$table->string('poster_path')->nullable();
            \$table->string('backdrop_path')->nullable();
            \$table->string('tmdb_id')->unique();
            \$table->enum('status', ['draft', 'published', 'archived'])->default('published');
            \$table->decimal('vote_average', 3, 1)->default(0);
            \$table->integer('vote_count')->default(0);
            \$table->date('first_air_date')->nullable();
            \$table->date('last_air_date')->nullable();
            \$table->string('original_language', 10)->nullable();
            \$table->json('genres')->nullable();
            \$table->integer('number_of_seasons')->default(1);
            \$table->integer('number_of_episodes')->default(1);
            \$table->boolean('is_active')->default(true);
            \$table->integer('view_count')->default(0);
            \$table->timestamps();

            \$table->fullText(['title', 'description']);
            \$table->index(['status', 'is_active']);
            \$table->index(['first_air_date', 'vote_average']);
            \$table->index('view_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series');
    }
};
";
    }

    private function getSeriesGenresTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series_genres', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('series_id')->constrained()->onDelete('cascade');
            \$table->foreignId('genre_id')->constrained()->onDelete('cascade');
            \$table->timestamps();

            \$table->unique(['series_id', 'genre_id']);
            \$table->index('genre_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series_genres');
    }
};
";
    }

    private function getSeriesSeasonsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series_seasons', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('series_id')->constrained()->onDelete('cascade');
            \$table->integer('season_number');
            \$table->string('name')->nullable();
            \$table->text('overview')->nullable();
            \$table->string('poster_path')->nullable();
            \$table->date('air_date')->nullable();
            \$table->integer('episode_count')->default(0);
            \$table->timestamps();

            \$table->unique(['series_id', 'season_number']);
            \$table->index('air_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series_seasons');
    }
};
";
    }

    private function getSeriesEpisodesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series_episodes', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('series_id')->constrained()->onDelete('cascade');
            \$table->foreignId('season_id')->constrained('series_seasons')->onDelete('cascade');
            \$table->integer('episode_number');
            \$table->string('title');
            \$table->text('overview')->nullable();
            \$table->string('still_path')->nullable();
            \$table->date('air_date')->nullable();
            \$table->integer('runtime')->nullable();
            \$table->decimal('vote_average', 3, 1)->default(0);
            \$table->integer('vote_count')->default(0);
            \$table->text('embed_url')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->integer('view_count')->default(0);
            \$table->timestamps();

            \$table->unique(['season_id', 'episode_number']);
            \$table->index(['series_id', 'is_active']);
            \$table->index('air_date');
            \$table->index('view_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series_episodes');
    }
};
";
    }

    private function getWatchlistsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('watchlists', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->constrained()->onDelete('cascade');
            \$table->morphs('watchable');
            \$table->timestamps();

            \$table->unique(['user_id', 'watchable_type', 'watchable_id']);
            \$table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('watchlists');
    }
};
";
    }

    private function getMovieViewsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movie_views', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('movie_id')->constrained()->onDelete('cascade');
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->timestamp('viewed_at');
            \$table->timestamps();

            \$table->index(['movie_id', 'viewed_at']);
            \$table->index(['user_id', 'viewed_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_views');
    }
};
";
    }

    private function getSeriesViewsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series_views', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('series_id')->constrained()->onDelete('cascade');
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->timestamp('viewed_at');
            \$table->timestamps();

            \$table->index(['series_id', 'viewed_at']);
            \$table->index(['user_id', 'viewed_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series_views');
    }
};
";
    }

    private function getSeriesEpisodeViewsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series_episode_views', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('episode_id')->constrained('series_episodes')->onDelete('cascade');
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->timestamp('viewed_at');
            \$table->timestamps();

            \$table->index(['episode_id', 'viewed_at']);
            \$table->index(['user_id', 'viewed_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('series_episode_views');
    }
};
";
    }

    private function getInviteCodesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invite_codes', function (Blueprint \$table) {
            \$table->id();
            \$table->string('code', 32)->unique();
            \$table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            \$table->integer('max_uses')->default(1);
            \$table->integer('used_count')->default(0);
            \$table->timestamp('expires_at')->nullable();
            \$table->timestamp('used_at')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();

            \$table->index(['is_active', 'expires_at']);
            \$table->index('used_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invite_codes');
    }
};
";
    }

    private function getUserRegistrationsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_registrations', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->constrained()->onDelete('cascade');
            \$table->foreignId('invite_code_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->string('referrer_url')->nullable();
            \$table->timestamp('registered_at');
            \$table->timestamps();

            \$table->index(['invite_code_id', 'registered_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_registrations');
    }
};
";
    }

    private function getSearchHistoriesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_histories', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('search_term');
            \$table->string('query')->nullable();
            \$table->string('ip_address');
            \$table->integer('results_count')->default(0);
            \$table->timestamps();

            \$table->index(['user_id', 'created_at']);
            \$table->index(['search_term', 'created_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_histories');
    }
};
";
    }

    private function getBrokenLinkReportsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broken_link_reports', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('movie_id')->constrained()->onDelete('cascade');
            \$table->foreignId('movie_source_id')->nullable()->constrained()->onDelete('cascade');
            \$table->foreignId('user_id')->constrained()->onDelete('cascade');
            \$table->enum('issue_type', [
                'not_loading',
                'wrong_movie',
                'poor_quality',
                'no_audio',
                'no_subtitle',
                'buffering',
                'other'
            ]);
            \$table->text('description')->nullable();
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->enum('status', ['pending', 'reviewing', 'fixed', 'dismissed'])->default('pending');
            \$table->foreignId('reviewed_by')->nullable()->constrained('users');
            \$table->timestamp('reviewed_at')->nullable();
            \$table->text('admin_notes')->nullable();
            \$table->timestamps();

            \$table->index(['movie_id', 'status']);
            \$table->index(['movie_source_id', 'status']);
            \$table->index(['user_id', 'status']);
            \$table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broken_link_reports');
    }
};
";
    }

    private function getUserActionLogsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_action_logs', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->string('action');
            \$table->string('description')->nullable();
            \$table->json('metadata')->nullable();
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->timestamps();

            \$table->index(['user_id', 'created_at']);
            \$table->index(['action', 'created_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_action_logs');
    }
};
";
    }

    private function getAdminActionLogsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_action_logs', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            \$table->string('action');
            \$table->string('description');
            \$table->json('old_values')->nullable();
            \$table->json('new_values')->nullable();
            \$table->string('ip_address');
            \$table->string('user_agent')->nullable();
            \$table->timestamps();

            \$table->index(['admin_id', 'created_at']);
            \$table->index(['action', 'created_at']);
            \$table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
";
    }

    private function getAuditLogsTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint \$table) {
            \$table->id();
            \$table->string('event');
            \$table->morphs('auditable');
            \$table->json('old_values')->nullable();
            \$table->json('new_values')->nullable();
            \$table->string('url')->nullable();
            \$table->string('ip_address')->nullable();
            \$table->string('user_agent')->nullable();
            \$table->string('tags')->nullable();
            \$table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            \$table->timestamps();

            \$table->index(['auditable_type', 'auditable_id']);
            \$table->index(['user_id', 'created_at']);
            \$table->index(['event', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
";
    }

    private function getUserActivitiesTable()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_activities', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->constrained()->onDelete('cascade');
            \$table->string('activity_type');
            \$table->string('description');
            \$table->json('properties')->nullable();
            \$table->timestamp('performed_at');
            \$table->string('ip_address')->nullable();
            \$table->timestamps();

            \$table->index(['user_id', 'performed_at']);
            \$table->index(['activity_type', 'performed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activities');
    }
};
";
    }

    private function getPerformanceIndexes()
    {
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Additional performance indexes
        Schema::table('movies', function (Blueprint \$table) {
            \$table->index(['created_at', 'status']);
            \$table->index(['vote_average', 'vote_count']);
        });

        Schema::table('series', function (Blueprint \$table) {
            \$table->index(['created_at', 'status']);
            \$table->index(['vote_average', 'vote_count']);
        });

        Schema::table('users', function (Blueprint \$table) {
            \$table->index(['created_at', 'status']);
            \$table->index(['role_id', 'status']);
        });

        Schema::table('genres', function (Blueprint \$table) {
            \$table->index(['name', 'is_active']);
        });
    }

    public function down()
    {
        Schema::table('movies', function (Blueprint \$table) {
            \$table->dropIndex(['created_at', 'status']);
            \$table->dropIndex(['vote_average', 'vote_count']);
        });

        Schema::table('series', function (Blueprint \$table) {
            \$table->dropIndex(['created_at', 'status']);
            \$table->dropIndex(['vote_average', 'vote_count']);
        });

        Schema::table('users', function (Blueprint \$table) {
            \$table->dropIndex(['created_at', 'status']);
            \$table->dropIndex(['role_id', 'status']);
        });

        Schema::table('genres', function (Blueprint \$table) {
            \$table->dropIndex(['name', 'is_active']);
        });
    }
};
";
    }

    private function log($message)
    {
        echo date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
    }
}

// Run the cleanup
$cleanup = new MigrationCleanup();
$cleanup->run();