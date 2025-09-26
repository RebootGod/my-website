<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email}';
    protected $description = 'Test SMTP configuration by sending a test email';

    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Testing SMTP configuration...');
        $this->info('SMTP Host: ' . config('mail.mailers.smtp.host'));
        $this->info('SMTP Port: ' . config('mail.mailers.smtp.port'));
        $this->info('SMTP Username: ' . config('mail.mailers.smtp.username'));
        $this->info('SMTP Encryption: ' . config('mail.mailers.smtp.encryption'));

        try {
            Mail::raw('This is a test email to verify SMTP configuration.', function ($message) use ($email) {
                $message->to($email)
                       ->subject('SMTP Test - Noobz Cinema')
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info('✅ Email sent successfully!');
            $this->info('If you don\'t receive the email, check:');
            $this->info('1. Spam/junk folder');
            $this->info('2. Email provider settings');
            $this->info('3. SMTP credentials');

        } catch (Exception $e) {
            $this->error('❌ Email sending failed!');
            $this->error('Error: ' . $e->getMessage());

            if (str_contains($e->getMessage(), '535')) {
                $this->error('');
                $this->error('SMTP Authentication Failed (Error 535)');
                $this->error('Possible solutions:');
                $this->error('1. Check email password in Laravel Forge environment');
                $this->error('2. Verify email account is not suspended');
                $this->error('3. Check if 2FA is enabled (need app password)');
                $this->error('4. Verify SMTP settings with email provider');
            }
        }
    }
}