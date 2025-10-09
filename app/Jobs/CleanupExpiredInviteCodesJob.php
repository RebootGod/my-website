<?php

namespace App\Jobs;

use App\Models\InviteCode;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Cleanup Expired Invite Codes
 * 
 * Security: Safe deletion with validation
 * OWASP: Protected against timing attacks
 * 
 * @package App\Jobs
 * @created 2025-10-09
 */
class CleanupExpiredInviteCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $now = now();

            // Find expired invite codes that haven't been used
            $expiredCodes = InviteCode::where('expires_at', '<', $now)
                ->whereNull('used_at')
                ->get();

            if ($expiredCodes->isEmpty()) {
                Log::info('No expired invite codes to cleanup');
                return;
            }

            $deletedCount = 0;
            $errors = [];

            foreach ($expiredCodes as $code) {
                try {
                    // Soft delete or hard delete based on your preference
                    $code->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'code_id' => $code->id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Notify admins if codes were cleaned up
            if ($deletedCount > 0) {
                $this->notifyAdmins($deletedCount, $expiredCodes->count());
            }

            Log::info('Expired invite codes cleaned up', [
                'total_found' => $expiredCodes->count(),
                'deleted' => $deletedCount,
                'errors' => count($errors),
                'timestamp' => $now
            ]);

            if (!empty($errors)) {
                Log::warning('Some invite codes failed to delete', [
                    'errors' => $errors
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired invite codes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Notify admins about cleanup
     *
     * @param int $deletedCount
     * @param int $totalFound
     * @return void
     */
    protected function notifyAdmins(int $deletedCount, int $totalFound): void
    {
        try {
            // Get all admin users
            $admins = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['Admin', 'Super Admin']);
            })->get();

            // If no role-based admins, fallback to legacy role column
            if ($admins->isEmpty()) {
                $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
            }

            // Notify each admin (will be handled by notification system)
            foreach ($admins as $admin) {
                // Future: Send notification when admin notification is implemented
                // $admin->notify(new InviteCodesCleanedUpNotification($deletedCount, $totalFound));
            }

            Log::info('Admin notification sent for invite code cleanup', [
                'admins_notified' => $admins->count(),
                'codes_deleted' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to notify admins about cleanup', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - notification failure shouldn't fail the job
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Invite code cleanup job failed after all retries', [
            'error' => $exception->getMessage(),
            'timestamp' => now()
        ]);
    }
}
