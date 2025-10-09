<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Job: Send Welcome Email to New User
 * 
 * Security: XSS protected, validated user data
 * OWASP: Safe email handling with queue retry mechanism
 * 
 * @package App\Jobs
 * @created 2025-10-09
 */
class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected $user;
    protected $inviteCode;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string|null $inviteCode
     */
    public function __construct(User $user, ?string $inviteCode = null)
    {
        $this->user = $user;
        $this->inviteCode = $inviteCode ? strip_tags($inviteCode) : null;
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
            // Validate user email
            if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
                Log::channel('security')->warning('Invalid email format in welcome job', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email
                ]);
                return;
            }

            // Send welcome email
            Mail::to($this->user->email)
                ->send(new WelcomeMail($this->user, $this->inviteCode));

            Log::info('Welcome email sent successfully', [
                'user_id' => $this->user->id,
                'email' => $this->user->email
            ]);

        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to send welcome email', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Re-throw to trigger retry mechanism
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
        Log::channel('security')->critical('Welcome email job failed after all retries', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
