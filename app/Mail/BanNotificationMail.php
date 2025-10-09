<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BanNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user who was banned.
     */
    public User $user;

    /**
     * The reason for the ban.
     */
    public string $banReason;

    /**
     * The admin who performed the ban.
     */
    public ?string $adminName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $banReason = 'Violation of Terms of Service', ?string $adminName = null)
    {
        $this->user = $user;
        $this->banReason = $banReason;
        $this->adminName = $adminName ?? 'Noobz Cinema Team';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Account Banned - Noobz Cinema',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ban-notification',
            with: [
                'username' => $this->user->username,
                'email' => $this->user->email,
                'banReason' => $this->banReason,
                'adminName' => $this->adminName,
                'banDate' => now()->format('F j, Y \a\t g:i A'),
                'supportEmail' => config('mail.from.address'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
