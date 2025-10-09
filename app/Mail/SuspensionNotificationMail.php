<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user who was suspended.
     */
    public User $user;

    /**
     * The reason for the suspension.
     */
    public string $suspensionReason;

    /**
     * The admin who performed the suspension.
     */
    public ?string $adminName;

    /**
     * The duration of suspension (optional).
     */
    public ?string $duration;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $suspensionReason = 'Violation of Terms of Service',
        ?string $adminName = null,
        ?string $duration = null
    ) {
        $this->user = $user;
        $this->suspensionReason = $suspensionReason;
        $this->adminName = $adminName ?? 'Noobz Cinema Team';
        $this->duration = $duration;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Account Suspended - Noobz Cinema',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.suspension-notification',
            with: [
                'username' => $this->user->username,
                'email' => $this->user->email,
                'suspensionReason' => $this->suspensionReason,
                'adminName' => $this->adminName,
                'duration' => $this->duration,
                'suspensionDate' => now()->format('F j, Y \a\t g:i A'),
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
