<?php

// Test Email Sending Directly
// Run: php test-email.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== EMAIL SENDING TEST ===\n\n";

$testEmail = 'legendnoobz.ln@gmail.com';

try {
    // Test 1: Simple Mail Test
    echo "1. Testing simple email...\n";

    Mail::raw('This is a test email from Noobz Cinema.', function ($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test Email from Noobz Cinema')
                ->from(config('mail.from.address'), config('mail.from.name'));
    });

    echo "✅ Simple email sent successfully!\n\n";

    // Test 2: Reset Password Notification Test
    echo "2. Testing ResetPasswordNotification...\n";

    $user = \App\Models\User::where('email', $testEmail)->first();

    if ($user) {
        $token = \Illuminate\Support\Str::random(60);
        $user->notify(new \App\Notifications\ResetPasswordNotification($token));
        echo "✅ Reset password notification sent successfully!\n";
    } else {
        echo "❌ User with email $testEmail not found\n";
        echo "Creating test user...\n";

        $user = \App\Models\User::create([
            'username' => 'testuser',
            'email' => $testEmail,
            'password' => bcrypt('password'),
            'role' => 'member'
        ]);

        $token = \Illuminate\Support\Str::random(60);
        $user->notify(new \App\Notifications\ResetPasswordNotification($token));
        echo "✅ Test user created and notification sent!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";

    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";