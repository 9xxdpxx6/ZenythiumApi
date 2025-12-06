<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class GoalDeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Goal $goal,
        public readonly int $daysRemaining,
        public readonly string $goalUrl,
        public ?string $toEmail = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $daysText = $this->daysRemaining === 1 ? 'день' : ($this->daysRemaining < 5 ? 'дня' : 'дней');
        
        $envelope = new Envelope(
            subject: "⏰ До дедлайна цели осталось {$this->daysRemaining} {$daysText} - Zenythium",
        );

        if ($this->toEmail) {
            $envelope->to($this->toEmail);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.goal-deadline-reminder',
            with: [
                'goal' => $this->goal,
                'daysRemaining' => $this->daysRemaining,
                'goalUrl' => $this->goalUrl,
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
