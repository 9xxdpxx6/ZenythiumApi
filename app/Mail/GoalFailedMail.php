<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class GoalFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Goal $goal,
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
            subject: 'Цель не достигнута - Zenythium',
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
        $currentValue = $this->goal->current_value ?? 0;
        $targetValue = $this->goal->target_value;
        $difference = $targetValue - $currentValue;
        
        return new Content(
            text: 'emails.goal-failed',
            with: [
                'goal' => $this->goal,
                'currentValue' => $currentValue,
                'targetValue' => $targetValue,
                'difference' => $difference,
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
