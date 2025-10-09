<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification: Welcome New User
 * 
 * Security: XSS protected, sanitized data
 * OWASP: Safe notification handling
 * 
 * @package App\Notifications
 * @created 2025-10-09
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $inviteCode;

    /**
     * Create a new notification instance.
     *
     * @param string|null $inviteCode
     */
    public function __construct(?string $inviteCode = null)
    {
        $this->inviteCode = $inviteCode ? strip_tags($inviteCode) : null;
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Welcome to Noobz Cinema! 🎬')
            ->greeting('Hello ' . e($notifiable->username) . '!')
            ->line('Welcome to Noobz Cinema - your destination for the best movies and series.')
            ->line('Your account has been successfully created and you can now start exploring our collection.');

        if ($this->inviteCode) {
            $message->line('You registered using invite code: ' . e($this->inviteCode));
        }

        $message->action('Explore Movies', url('/'))
            ->line('Thank you for joining us!')
            ->line('Happy watching! 🍿');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Welcome to Noobz Cinema!',
            'message' => 'Your account has been created successfully. Start exploring now!',
            'invite_code' => $this->inviteCode,
            'action_url' => url('/'),
            'action_text' => 'Explore Movies',
            'timestamp' => now()->toIso8601String()
        ];
    }
}
