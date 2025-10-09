<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Welcome Email
 * 
 * Security: XSS protected with e() helper
 * OWASP: Safe email template rendering
 * 
 * @package App\Mail
 * @created 2025-10-09
 */
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $inviteCode;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string|null $inviteCode
     */
    public function __construct(User $user, ?string $inviteCode = null)
    {
        $this->user = $user;
        $this->inviteCode = $inviteCode ? strip_tags($inviteCode) : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Noobz Cinema! 🎬',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'username' => e($this->user->username),
                'email' => e($this->user->email),
                'inviteCode' => $this->inviteCode,
                'homeUrl' => url('/'),
                'moviesUrl' => url('/movies'),
                'seriesUrl' => url('/series'),
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
