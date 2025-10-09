<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Send Password Reset Email
 * 
 * Security: Token sanitized, XSS protected
 * OWASP: Secure password reset flow with queue
 * 
 * @package App\Jobs
 * @created 2025-10-09
 */
class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected $user;
    protected $token;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $token
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = strip_tags($token); // XSS protection
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Validate email format
            if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
                Log::channel('security')->warning('Invalid email in password reset', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email
                ]);
                return;
            }

            // Send password reset notification
            $this->user->notify(new ResetPasswordNotification($this->token));

            Log::info('Password reset email sent', [
                'user_id' => $this->user->id,
                'email' => $this->user->email
            ]);

        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to send password reset email', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
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
        Log::channel('security')->critical('Password reset email failed after all retries', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage()
        ]);
    }
}
