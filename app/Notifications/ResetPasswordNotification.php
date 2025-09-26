<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification // implements ShouldQueue
{
    // use Queueable;

    public string $token;
    public int $expiryHours;

    /**
     * Create a new notification instance
     */
    public function __construct(string $token, int $expiryHours = 1)
    {
        $this->token = $token;
        $this->expiryHours = $expiryHours;

        // Queue options disabled for testing
        // $this->onQueue('emails');
        // $this->delay(now()->addSeconds(2));
    }

    /**
     * Get the notification's delivery channels
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification
     */
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $appName = config('app.name', 'Noobz Cinema');
        $supportEmail = config('mail.support_email', 'noobz@noobz.space');

        return (new MailMessage)
            ->subject(Lang::get('Reset Password - ' . $appName))
            ->greeting(Lang::get('Halo :name!', ['name' => $notifiable->username ?? 'User']))
            ->line(Lang::get('Kami menerima permintaan untuk mereset password akun Anda di :app.', ['app' => $appName]))
            ->line(Lang::get('Klik tombol di bawah untuk mereset password:'))
            ->action(Lang::get('Reset Password'), $resetUrl)
            ->line(Lang::get('Link reset password ini akan expired dalam **:hours jam**.', ['hours' => $this->expiryHours]))
            ->line(Lang::get('Jika Anda tidak meminta reset password, abaikan email ini. Password Anda akan tetap aman.'))
            ->line('')
            ->line('**Informasi Keamanan:**')
            ->line('• Jangan bagikan link ini kepada siapa pun')
            ->line('• Kami tidak akan pernah meminta password via email')
            ->line('• Jika Anda merasa ini mencurigakan, hubungi support')
            ->line('')
            ->line(Lang::get('Butuh bantuan? Hubungi support: :email', ['email' => $supportEmail]))
            ->salutation(Lang::get('Salam hangat,') . "\n" . $appName . ' Team')
            ->priority(1); // High priority
    }

    /**
     * Get the reset URL for the given notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'password_reset',
            'token_created' => now(),
            'expires_in_hours' => $this->expiryHours,
            'ip_address' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown'
        ];
    }
}