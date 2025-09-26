<?php

// Debug Email Configuration and Queue Jobs
// Run: php debug-email.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== EMAIL DEBUG INFORMATION ===\n\n";

// 1. Check Email Configuration
echo "1. EMAIL CONFIGURATION:\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// 2. Check Queue Configuration
echo "2. QUEUE CONFIGURATION:\n";
echo "QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "Queue Driver: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n\n";

// 3. Check Failed Jobs
echo "3. FAILED JOBS:\n";
try {
    $failedJobs = DB::table('failed_jobs')->count();
    echo "Failed jobs count: " . $failedJobs . "\n";

    if ($failedJobs > 0) {
        $recentFailed = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentFailed as $job) {
            echo "- Failed at: " . $job->failed_at . "\n";
            echo "  Exception: " . substr($job->exception, 0, 200) . "...\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking failed jobs: " . $e->getMessage() . "\n";
}

// 4. Check Jobs Table
echo "4. PENDING JOBS:\n";
try {
    $pendingJobs = DB::table('jobs')->count();
    echo "Pending jobs count: " . $pendingJobs . "\n";

    if ($pendingJobs > 0) {
        $recentJobs = DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentJobs as $job) {
            echo "- Created at: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
            echo "  Attempts: " . $job->attempts . "\n";
            echo "  Queue: " . $job->queue . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking jobs: " . $e->getMessage() . "\n";
}

// 5. Test Email Configuration
echo "5. SMTP CONNECTION TEST:\n";
try {
    $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        config('mail.mailers.smtp.host'),
        config('mail.mailers.smtp.port'),
        config('mail.mailers.smtp.encryption') === 'ssl'
    );

    $transport->setUsername(config('mail.mailers.smtp.username'));
    $transport->setPassword(config('mail.mailers.smtp.password'));

    // Just test if we can create transport (basic validation)
    echo "SMTP Transport created successfully\n";
    echo "Host: " . config('mail.mailers.smtp.host') . "\n";
    echo "Port: " . config('mail.mailers.smtp.port') . "\n";
    echo "Encryption: " . config('mail.mailers.smtp.encryption') . "\n";

} catch (Exception $e) {
    echo "SMTP Configuration Error: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";