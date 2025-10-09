<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification: New User Registered (Admin)
 * 
 * Security: XSS protected, sanitized user data
 * OWASP: Admin notification with safe data handling
 * 
 * @package App\Notifications
 * @created 2025-10-09
 */
class NewUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $newUser;
    protected $inviteCode;

    /**
     * Create a new notification instance.
     *
     * @param User $newUser
     * @param string|null $inviteCode
     */
    public function __construct(User $newUser, ?string $inviteCode = null)
    {
        $this->newUser = $newUser;
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
            ->subject('New User Registration - ' . e($this->newUser->username))
            ->greeting('Hello Admin,')
            ->line('A new user has registered on Noobz Cinema.')
            ->line('**Username:** ' . e($this->newUser->username))
            ->line('**Email:** ' . e($this->newUser->email))
            ->line('**Registration Date:** ' . $this->newUser->created_at->format('F j, Y g:i A'));

        if ($this->inviteCode) {
            $message->line('**Invite Code Used:** ' . e($this->inviteCode));
        }

        if ($this->newUser->last_login_ip) {
            $message->line('**IP Address:** ' . e($this->newUser->last_login_ip));
        }

        $message->action('View User Details', url('/admin/users/' . $this->newUser->id))
            ->line('Total users: ' . User::count());

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
            'title' => 'New User Registration',
            'message' => e($this->newUser->username) . ' just registered',
            'user_id' => $this->newUser->id,
            'username' => e($this->newUser->username),
            'email' => e($this->newUser->email),
            'invite_code' => $this->inviteCode,
            'registration_ip' => $this->newUser->last_login_ip,
            'timestamp' => $this->newUser->created_at->toIso8601String(),
            'action_url' => url('/admin/users/' . $this->newUser->id),
            'total_users' => User::count()
        ];
    }
}
