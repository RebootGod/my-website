<?php

// Debug Hostinger Authentication Issues
// Run: php debug-hostinger-auth.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HOSTINGER AUTHENTICATION DEBUG ===\n\n";

// Get current environment variables
$mailPassword = env('MAIL_PASSWORD');
$mailUsername = env('MAIL_USERNAME', 'admin@hahacosmos.xyz');

echo "Current Configuration:\n";
echo "MAIL_USERNAME: " . $mailUsername . "\n";
echo "MAIL_PASSWORD: " . (strlen($mailPassword) > 0 ? str_repeat('*', strlen($mailPassword)) : 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS') . "\n\n";

// Test different authentication scenarios
$testScenarios = [
    [
        'name' => 'Current config (admin@hahacosmos.xyz)',
        'username' => 'admin@hahacosmos.xyz',
        'password' => $mailPassword,
    ],
    [
        'name' => 'Try without @domain (admin only)',
        'username' => 'admin',
        'password' => $mailPassword,
    ],
    [
        'name' => 'Try with from_address as username',
        'username' => env('MAIL_FROM_ADDRESS', 'noobz@hahacosmos.xyz'),
        'password' => $mailPassword,
    ]
];

foreach ($testScenarios as $index => $scenario) {
    echo ($index + 1) . ". Testing: " . $scenario['name'] . "\n";
    echo "   Username: " . $scenario['username'] . "\n";
    echo "   Password: " . (strlen($scenario['password']) > 0 ? str_repeat('*', strlen($scenario['password'])) : 'NOT SET') . "\n";

    if (empty($scenario['password'])) {
        echo "   ❌ SKIPPED: Password not set\n\n";
        continue;
    }

    try {
        // Test SMTP connection manually
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            'smtp.hostinger.com',
            465,
            true // Use encryption
        );

        $transport->setUsername($scenario['username']);
        $transport->setPassword($scenario['password']);

        // Try to start the transport (this will test authentication)
        $transport->start();

        echo "   ✅ SUCCESS: Authentication successful!\n";

        // If successful, try sending a test email
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $email = (new \Symfony\Component\Mime\Email())
            ->from('noobz@hahacosmos.xyz')
            ->to('legendnoobz.ln@gmail.com')
            ->subject('Hostinger Auth Test - ' . $scenario['name'])
            ->text('Authentication test successful with: ' . $scenario['username']);

        $mailer->send($email);
        echo "   ✅ EMAIL SENT: Test email sent successfully!\n\n";

        $transport->stop();
        break; // Stop testing if one works

    } catch (Exception $e) {
        echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
    }
}

// Additional checks
echo "=== ADDITIONAL CHECKS ===\n\n";

echo "1. Environment Variables Check:\n";
$envVars = ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];

foreach ($envVars as $var) {
    $value = env($var);
    if ($var === 'MAIL_PASSWORD') {
        $display = $value ? str_repeat('*', strlen($value)) : 'NOT SET';
    } else {
        $display = $value ?: 'NOT SET';
    }
    echo "   $var: $display\n";
}

echo "\n2. Hostinger SMTP Requirements:\n";
echo "   - Host: smtp.hostinger.com\n";
echo "   - Port: 465 (SSL/TLS) or 587 (STARTTLS)\n";
echo "   - Authentication: Required\n";
echo "   - Username: Usually full email address\n";
echo "   - Password: Email account password or app password\n";
echo "   - Domain: Must be added and verified in Hostinger\n\n";

echo "3. Troubleshooting Steps:\n";
echo "   a) Verify domain 'hahacosmos.xyz' is added in Hostinger Email section\n";
echo "   b) Verify email account 'admin@hahacosmos.xyz' exists in Hostinger\n";
echo "   c) Check if 2FA is enabled (may need app-specific password)\n";
echo "   d) Try logging into webmail with same credentials\n";
echo "   e) Check Hostinger email logs for failed attempts\n\n";

echo "=== DEBUG COMPLETED ===\n";