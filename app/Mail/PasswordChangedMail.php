<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Password Changed Confirmation Email
 * 
 * Security: XSS protected, sanitized IP/location
 * OWASP: Secure password change notification
 * 
 * @package App\Mail
 * @created 2025-10-09
 */
class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $ipAddress;
    public ?string $location;
    public $timestamp;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string|null $ipAddress
     * @param string|null $location
     */
    public function __construct(User $user, ?string $ipAddress = null, ?string $location = null)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress ? filter_var($ipAddress, FILTER_VALIDATE_IP) : null;
        $this->location = $location ? strip_tags(substr($location, 0, 100)) : 'Unknown';
        $this->timestamp = now();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Changed Successfully',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed',
            with: [
                'username' => e($this->user->username),
                'email' => e($this->user->email),
                'ipAddress' => $this->ipAddress ?? 'Unknown',
                'location' => e($this->location),
                'timestamp' => $this->timestamp->format('F j, Y g:i A'),
                'supportUrl' => url('/support'),
                'profileUrl' => url('/profile'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
