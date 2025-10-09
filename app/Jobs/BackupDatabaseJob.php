<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Backup type (full or partial).
     */
    protected string $backupType;

    /**
     * Tables to backup (empty for full backup).
     */
    protected array $tables;

    /**
     * Send notification to admins.
     */
    protected bool $notifyAdmins;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Critical tables to backup.
     */
    protected array $criticalTables = [
        'users',
        'movies',
        'series',
        'series_seasons',
        'series_episodes',
        'genres',
        'movie_genre',
        'series_genre',
        'movie_sources',
        'watchlists',
        'invite_codes',
        'roles',
        'permissions',
        'role_permission',
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $backupType = 'critical',
        array $tables = [],
        bool $notifyAdmins = true
    ) {
        $this->backupType = $backupType;
        $this->tables = empty($tables) ? $this->criticalTables : $tables;
        $this->notifyAdmins = $notifyAdmins;
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('BackupDatabaseJob: Starting database backup', [
                'backup_type' => $this->backupType,
                'tables_count' => count($this->tables),
            ]);

            $startTime = microtime(true);

            // Generate backup
            $backupPath = $this->createBackup();

            // Compress backup
            $compressedPath = $this->compressBackup($backupPath);

            // Clean up uncompressed backup
            Storage::disk('local')->delete($backupPath);

            // Clean up old backups (keep last 7 days)
            $this->cleanupOldBackups();

            $duration = round(microtime(true) - $startTime, 2);
            $fileSize = Storage::disk('local')->size($compressedPath);

            Log::info('BackupDatabaseJob: Database backup completed', [
                'backup_path' => $compressedPath,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'duration_seconds' => $duration,
            ]);

            // Send notification to admins
            if ($this->notifyAdmins) {
                $this->notifyAdmins($compressedPath, $fileSize, $duration);
            }

        } catch (\Exception $e) {
            Log::error('BackupDatabaseJob: Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Notify admins about failure
            $this->notifyBackupFailure($e->getMessage());

            throw $e;
        }
    }

    /**
     * Create database backup.
     */
    private function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$this->backupType}_{$timestamp}.sql";
        $directory = 'backups/database';
        $path = "{$directory}/{$filename}";

        $sql = [];

        // Add header
        $sql[] = "-- Database Backup";
        $sql[] = "-- Generated: " . now()->toDateTimeString();
        $sql[] = "-- Backup Type: {$this->backupType}";
        $sql[] = "-- Tables: " . count($this->tables);
        $sql[] = "";
        $sql[] = "SET FOREIGN_KEY_CHECKS=0;";
        $sql[] = "";

        foreach ($this->tables as $table) {
            if (!$this->tableExists($table)) {
                Log::warning('BackupDatabaseJob: Table does not exist', [
                    'table' => $table,
                ]);
                continue;
            }

            $sql[] = "-- ========================================";
            $sql[] = "-- Table: {$table}";
            $sql[] = "-- ========================================";
            $sql[] = "";

            // Get table structure
            $createTable = $this->getTableStructure($table);
            $sql[] = "DROP TABLE IF EXISTS `{$table}`;";
            $sql[] = $createTable . ";";
            $sql[] = "";

            // Get table data
            $rows = DB::table($table)->get();

            if ($rows->isNotEmpty()) {
                $sql[] = "-- Table data for {$table}";
                $sql[] = "";

                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array) $row as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }

                    $columns = implode('`, `', array_keys((array) $row));
                    $valuesStr = implode(', ', $values);

                    $sql[] = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$valuesStr});";
                }

                $sql[] = "";
            }

            Log::debug('BackupDatabaseJob: Table backed up', [
                'table' => $table,
                'rows' => $rows->count(),
            ]);
        }

        $sql[] = "SET FOREIGN_KEY_CHECKS=1;";
        $sql[] = "";
        $sql[] = "-- Backup completed: " . now()->toDateTimeString();

        $content = implode("\n", $sql);

        // Store backup file
        Storage::disk('local')->put($path, $content);

        return $path;
    }

    /**
     * Check if table exists.
     */
    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get table structure (CREATE TABLE statement).
     */
    private function getTableStructure(string $table): string
    {
        try {
            $result = DB::select("SHOW CREATE TABLE `{$table}`");
            return $result[0]->{'Create Table'};
        } catch (\Exception $e) {
            Log::warning('BackupDatabaseJob: Failed to get table structure', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
            return "CREATE TABLE `{$table}` (id INT)";
        }
    }

    /**
     * Compress backup file using gzip.
     */
    private function compressBackup(string $path): string
    {
        $compressedPath = $path . '.gz';

        $sourceFile = Storage::disk('local')->path($path);
        $destinationFile = Storage::disk('local')->path($compressedPath);

        // Read source file
        $sourceContent = Storage::disk('local')->get($path);

        // Compress using gzip
        $compressed = gzencode($sourceContent, 9); // Maximum compression

        // Write compressed file
        Storage::disk('local')->put($compressedPath, $compressed);

        Log::debug('BackupDatabaseJob: Backup compressed', [
            'original_size_mb' => round(strlen($sourceContent) / 1024 / 1024, 2),
            'compressed_size_mb' => round(strlen($compressed) / 1024 / 1024, 2),
            'compression_ratio' => round((1 - strlen($compressed) / strlen($sourceContent)) * 100, 2) . '%',
        ]);

        return $compressedPath;
    }

    /**
     * Clean up old backups (keep last 7 days).
     */
    private function cleanupOldBackups(): void
    {
        $directory = 'backups/database';
        $files = Storage::disk('local')->files($directory);

        $cutoffDate = now()->subDays(7);
        $deletedCount = 0;
        $deletedSize = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoffDate->timestamp) {
                $fileSize = Storage::disk('local')->size($file);
                Storage::disk('local')->delete($file);
                $deletedCount++;
                $deletedSize += $fileSize;
            }
        }

        if ($deletedCount > 0) {
            Log::info('BackupDatabaseJob: Old backups cleaned up', [
                'deleted_count' => $deletedCount,
                'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            ]);
        }
    }

    /**
     * Notify admins about successful backup.
     */
    private function notifyAdmins(string $backupPath, int $fileSize, float $duration): void
    {
        $adminEmails = $this->getAdminEmails();

        if (empty($adminEmails)) {
            return;
        }

        $sizeMB = round($fileSize / 1024 / 1024, 2);
        $filename = basename($backupPath);

        $message = "Database Backup Completed\n\n" .
            "Backup Type: {$this->backupType}\n" .
            "Tables Backed Up: " . count($this->tables) . "\n" .
            "File: {$filename}\n" .
            "Size: {$sizeMB} MB\n" .
            "Duration: {$duration} seconds\n" .
            "Timestamp: " . now()->toDateTimeString() . "\n\n" .
            "Tables:\n" . implode(", ", $this->tables) . "\n\n" .
            "The backup file is stored securely on the server.\n\n" .
            "Best regards,\nNoobz Cinema System";

        foreach ($adminEmails as $email) {
            try {
                Mail::raw($message, function ($mail) use ($email) {
                    $mail->to($email)
                        ->subject('[Noobz Cinema] Database Backup Completed');
                });

                Log::info('BackupDatabaseJob: Admin notified', [
                    'email' => $email,
                ]);
            } catch (\Exception $e) {
                Log::warning('BackupDatabaseJob: Failed to notify admin', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify admins about backup failure.
     */
    private function notifyBackupFailure(string $errorMessage): void
    {
        $adminEmails = $this->getAdminEmails();

        if (empty($adminEmails)) {
            return;
        }

        $message = "⚠️ Database Backup FAILED\n\n" .
            "Backup Type: {$this->backupType}\n" .
            "Timestamp: " . now()->toDateTimeString() . "\n\n" .
            "Error: {$errorMessage}\n\n" .
            "Please check the logs for more details.\n\n" .
            "Best regards,\nNoobz Cinema System";

        foreach ($adminEmails as $email) {
            try {
                Mail::raw($message, function ($mail) use ($email) {
                    $mail->to($email)
                        ->subject('[ALERT] Database Backup Failed');
                });
            } catch (\Exception $e) {
                Log::error('BackupDatabaseJob: Failed to send failure notification', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get admin emails from database.
     */
    private function getAdminEmails(): array
    {
        try {
            return DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->whereIn('roles.name', ['admin', 'super_admin'])
                ->where('users.status', 'active')
                ->pluck('users.email')
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('BackupDatabaseJob: Failed to get admin emails', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BackupDatabaseJob: Job failed permanently', [
            'backup_type' => $this->backupType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify admins about permanent failure
        $this->notifyBackupFailure($exception->getMessage());
    }
}
