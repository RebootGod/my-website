<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification: Account Security Event
 * 
 * Security: XSS protected, sanitized IP/location data
 * OWASP: Secure notification for security events
 * 
 * @package App\Notifications
 * @created 2025-10-09
 */
class AccountSecurityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $eventType;
    protected $ipAddress;
    protected $location;
    protected $timestamp;

    /**
     * Create a new notification instance.
     *
     * @param string $eventType (login, password_changed, account_locked, etc.)
     * @param string|null $ipAddress
     * @param string|null $location
     */
    public function __construct(string $eventType, ?string $ipAddress = null, ?string $location = null)
    {
        $this->eventType = strip_tags($eventType);
        $this->ipAddress = $ipAddress ? filter_var($ipAddress, FILTER_VALIDATE_IP) : null;
        $this->location = $location ? strip_tags(substr($location, 0, 100)) : 'Unknown';
        $this->timestamp = now();
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
        $subject = $this->getSubject();
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . e($notifiable->username) . ',');

        switch ($this->eventType) {
            case 'login':
                $message->line('We detected a new login to your account.')
                    ->line('**Time:** ' . $this->timestamp->format('F j, Y g:i A'))
                    ->line('**IP Address:** ' . ($this->ipAddress ?? 'Unknown'))
                    ->line('**Location:** ' . e($this->location))
                    ->line('If this was you, you can ignore this email.')
                    ->line('If you did not log in, please secure your account immediately.');
                break;

            case 'password_changed':
                $message->line('Your password was successfully changed.')
                    ->line('**Time:** ' . $this->timestamp->format('F j, Y g:i A'))
                    ->line('**IP Address:** ' . ($this->ipAddress ?? 'Unknown'))
                    ->line('**Location:** ' . e($this->location))
                    ->line('If you did not make this change, contact support immediately.');
                break;

            case 'account_locked':
                $message->line('Your account has been locked due to suspicious activity.')
                    ->line('**Time:** ' . $this->timestamp->format('F j, Y g:i A'))
                    ->line('**Reason:** Multiple failed login attempts')
                    ->line('Please contact support to unlock your account.');
                break;

            default:
                $message->line('A security event occurred on your account.')
                    ->line('**Event:** ' . e($this->eventType))
                    ->line('**Time:** ' . $this->timestamp->format('F j, Y g:i A'));
        }

        $message->line('For your security, we recommend enabling two-factor authentication if available.');

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
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'event_type' => $this->eventType,
            'ip_address' => $this->ipAddress,
            'location' => $this->location,
            'timestamp' => $this->timestamp->toIso8601String(),
            'severity' => $this->getSeverity()
        ];
    }

    /**
     * Get notification subject
     *
     * @return string
     */
    protected function getSubject(): string
    {
        return match ($this->eventType) {
            'login' => 'New Login to Your Account',
            'password_changed' => 'Password Changed Successfully',
            'account_locked' => 'Account Locked - Action Required',
            default => 'Security Alert'
        };
    }

    /**
     * Get notification title
     *
     * @return string
     */
    protected function getTitle(): string
    {
        return match ($this->eventType) {
            'login' => 'New Login Detected',
            'password_changed' => 'Password Changed',
            'account_locked' => 'Account Locked',
            default => 'Security Event'
        };
    }

    /**
     * Get notification message
     *
     * @return string
     */
    protected function getMessage(): string
    {
        return match ($this->eventType) {
            'login' => 'New login from ' . $this->location . ' at ' . $this->timestamp->format('g:i A'),
            'password_changed' => 'Your password was changed successfully',
            'account_locked' => 'Your account has been locked due to suspicious activity',
            default => 'A security event occurred on your account'
        };
    }

    /**
     * Get severity level
     *
     * @return string
     */
    protected function getSeverity(): string
    {
        return match ($this->eventType) {
            'account_locked' => 'high',
            'password_changed' => 'medium',
            'login' => 'low',
            default => 'info'
        };
    }
}
