<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplianceExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $recipientName,
        public string $label,
        public string $siteName,
        public string $whenText,
        public ?string $expiryDate,
        public string $subjectLine,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compliance-expiry',
            with: [
                'recipientName' => $this->recipientName,
                'companyName' => $this->company->name,
                'label' => $this->label,
                'siteName' => $this->siteName,
                'whenText' => $this->whenText,
                'expiryDate' => $this->expiryDate,
                'complianceUrl' => url('/compliance'),
            ],
        );
    }
}
