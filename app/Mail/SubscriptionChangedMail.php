<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $planType;
    public string $planName;
    public bool $isLifetime;

    /**
     * Create a new message instance.
     */
    public function __construct(?string $recipientName, string $planType)
    {
        $this->recipientName = $recipientName ?: 'there';
        $this->planType = $planType;

        $plans = config('stripe.plans');
        $planConfig = $plans[$planType] ?? ['name' => $planType];

        $this->planName = $planConfig['name'] ?? $planType;
        $this->isLifetime = (bool) ($planConfig['is_lifetime'] ?? false);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ZapTask Subscription Has Been Updated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-changed',
            with: [
                'recipientName' => $this->recipientName,
                'planType' => $this->planType,
                'planName' => $this->planName,
                'isLifetime' => $this->isLifetime,
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


