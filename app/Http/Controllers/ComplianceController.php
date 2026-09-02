<?php

namespace App\Http\Controllers;

use App\Models\ComplianceRequirement;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Support\CertificateTypes;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        if (! $user->company_id) {
            abort(403, 'Access denied. Only company users can view compliance.');
        }

        $requirements = ComplianceRequirement::forCompany($user->company_id)
            ->with('operationalObject')
            ->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();
        }

        $requirements = ComplianceRequirement::forCompany($user->company_id)
            ->with('operationalObject')
            ->orderByRaw("case status when 'overdue' then 1 when 'due_soon' then 2 when 'missing' then 3 else 4 end")
            ->orderBy('next_due_date')
            ->get();

        $documents = OperationalDocument::forCompany($user->company_id)
            ->with('operationalObject')
            ->where('status', '!=', OperationalDocument::STATUS_ARCHIVED)
            ->orderByRaw('case when expires_at is null then 1 else 0 end')
            ->orderBy('expires_at')
            ->orderByDesc('created_at')
            ->get();

        $pendingProposals = DocumentExtractionProposal::query()
            ->where('company_id', $user->company_id)
            ->where('status', DocumentExtractionProposal::STATUS_PENDING)
            ->with(['operationalObject', 'operationalDocument'])
            ->orderByDesc('created_at')
            ->get();

        $company = $user->company;

        return Inertia::render('Compliance/Index', [
            'summary' => [
                'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
                'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
                'compliant' => $requirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
                'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
                'total' => $requirements->count(),
                'documents' => $documents->count(),
                'pending_extractions' => $pendingProposals->count(),
            ],
            'requirements' => $requirements->map(fn ($req) => [
                'id' => $req->id,
                'label' => $req->label,
                'requirement_type' => $req->requirement_type,
                'type_label' => CertificateTypes::label($req->requirement_type),
                'status' => $req->status,
                'next_due_date' => $req->next_due_date?->format('Y-m-d'),
                'next_due_display' => $req->next_due_date?->format('j M Y'),
                'site' => $req->operationalObject ? [
                    'id' => $req->operationalObject->id,
                    'name' => $req->operationalObject->name,
                ] : null,
            ]),
            'documents' => $documents->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_type' => $doc->document_type,
                'type_label' => $doc->document_type ? CertificateTypes::label($doc->document_type) : 'Document',
                'status' => $doc->status,
                'expires_at' => $doc->expires_at?->format('Y-m-d'),
                'expires_display' => $doc->expires_at?->format('j M Y'),
                'original_filename' => $doc->original_filename,
                'site' => $doc->operationalObject ? [
                    'id' => $doc->operationalObject->id,
                    'name' => $doc->operationalObject->name,
                ] : null,
            ]),
            'pendingProposals' => $pendingProposals->map(fn ($proposal) => [
                'id' => $proposal->id,
                'summary' => $proposal->summary,
                'extracted_data' => $proposal->extracted_data,
                'document_title' => $proposal->operationalDocument?->title,
                'site' => $proposal->operationalObject ? [
                    'id' => $proposal->operationalObject->id,
                    'name' => $proposal->operationalObject->name,
                ] : null,
            ]),
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'subscription_type' => $company->subscription_type,
            ] : null,
        ]);
    }
}
