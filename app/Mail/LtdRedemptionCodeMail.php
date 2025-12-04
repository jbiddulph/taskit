<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\RedemptionCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LtdRedemptionCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $planType;
    public string $planName;
    public string $code;
    public string $redeemUrl;
    public ?Company $company;

    /**
     * Create a new message instance.
     */
    public function __construct(?string $recipientName, Company $company, string $planType, RedemptionCode $redemptionCode)
    {
        $this->recipientName = $recipientName ?: 'there';
        $this->company = $company;
        $this->planType = $planType;
        $this->planName = config("stripe.plans.{$planType}.name", $planType);
        $this->code = $redemptionCode->code;
        $this->redeemUrl = 'https://www.zaptask.co.uk/ltd/redeem';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ZapTask Lifetime Deal Redemption Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ltd-redemption-code',
            with: [
                'recipientName' => $this->recipientName,
                'company' => $this->company,
                'planType' => $this->planType,
                'planName' => $this->planName,
                'code' => $this->code,
                'redeemUrl' => $this->redeemUrl,
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


