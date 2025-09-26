<?php

// Test Hostinger SMTP Configurations
// Run: php test-hostinger-smtp.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== HOSTINGER SMTP CONFIGURATION TEST ===\n\n";

$testEmail = 'legendnoobz.ln@gmail.com';

// Test different SMTP configurations
$configs = [
    [
        'name' => 'Port 465 with TLS',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'encryption' => 'tls',
        'username' => 'admin@hahacosmos.xyz',
        'password' => env('MAIL_PASSWORD'),
    ],
    [
        'name' => 'Port 587 with TLS',
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'admin@hahacosmos.xyz',
        'password' => env('MAIL_PASSWORD'),
    ]
];

foreach ($configs as $index => $config) {
    echo ($index + 1) . ". Testing: " . $config['name'] . "\n";
    echo "   Host: {$config['host']}:{$config['port']}\n";
    echo "   Encryption: {$config['encryption']}\n";

    try {
        // Temporarily set mail config
        config([
            'mail.mailers.smtp.host' => $config['host'],
            'mail.mailers.smtp.port' => $config['port'],
            'mail.mailers.smtp.encryption' => $config['encryption'],
            'mail.mailers.smtp.username' => $config['username'],
            'mail.mailers.smtp.password' => $config['password'],
        ]);

        // Clear any cached mail config
        app()->forgetInstance('mailer');

        Mail::raw("Test email from Noobz Cinema\n\nConfiguration: {$config['name']}\nTimestamp: " . date('Y-m-d H:i:s'), function ($message) use ($testEmail, $config) {
            $message->to($testEmail)
                    ->subject('Hostinger SMTP Test - ' . $config['name'])
                    ->from('noobz@hahacosmos.xyz', 'Noobz Cinema');
        });

        echo "   ✅ SUCCESS: Email sent successfully!\n\n";

    } catch (Exception $e) {
        echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
    }
}

echo "=== TEST COMPLETED ===\n";
echo "Check your email inbox for test messages.\n";