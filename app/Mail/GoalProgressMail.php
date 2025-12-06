<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class GoalProgressMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Goal $goal,
        public readonly int $milestone,
        public readonly string $goalUrl,
        public ?string $toEmail = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: "Отличный прогресс! {$this->milestone}% до цели - Zenythium",
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
        $remaining = max(0, $this->goal->target_value - ($this->goal->current_value ?? 0));
        
        return new Content(
            text: 'emails.goal-progress',
            with: [
                'goal' => $this->goal,
                'milestone' => $this->milestone,
                'remaining' => $remaining,
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
