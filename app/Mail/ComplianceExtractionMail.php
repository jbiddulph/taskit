<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalObject;
use App\Support\CertificateTypes;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplianceExtractionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public DocumentExtractionProposal $proposal,
        public OperationalObject $site,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->documentLabel();
        $siteName = $this->site->name;

        return new Envelope(
            subject: "Compliance document read: {$label} — {$siteName}",
        );
    }

    public function content(): Content
    {
        $data = $this->proposal->extracted_data ?? [];

        return new Content(
            view: 'emails.compliance-extraction',
            with: [
                'recipientName' => $this->recipientName,
                'companyName' => $this->company->name,
                'siteName' => $this->site->name,
                'label' => $this->documentLabel(),
                'summary' => $this->proposal->summary,
                'expiryDate' => $data['expiry_date'] ?? null,
                'issueDate' => $data['issue_date'] ?? null,
                'certificateNumber' => $data['certificate_number'] ?? null,
                'engineerName' => $data['engineer_name'] ?? null,
                'reviewUrl' => url('/compliance'),
            ],
        );
    }

    protected function documentLabel(): string
    {
        $data = $this->proposal->extracted_data ?? [];
        if (! empty($data['label'])) {
            return $data['label'];
        }

        $type = $data['document_type'] ?? 'other';

        return CertificateTypes::label($type);
    }
}
