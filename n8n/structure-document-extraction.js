// Reference implementation for the "Structure Document Extraction" n8n Code node.
// Keep in sync with zaptask-document-extraction-workflow.json

const meta = $('Normalize Webhook Input').first().json;
const ai = $input.first().json;

let parsed = ai.message?.content ?? ai.content ?? ai.choices?.[0]?.message?.content ?? ai;

if (typeof parsed === 'string') {
  try {
    parsed = JSON.parse(parsed);
  } catch {
    parsed = {
      document_type: 'other',
      label: meta.title || meta.original_filename || 'Certificate',
      certificate_number: null,
      expiry_date: null,
      issue_date: null,
      engineer_name: null,
      address: null,
      summary: parsed.slice(0, 500),
    };
  }
}

const summary = parsed.summary ?? null;
delete parsed.summary;

const extracted_data = {
  document_type: parsed.document_type ?? 'other',
  label: parsed.label ?? meta.title ?? 'Certificate',
  certificate_number: parsed.certificate_number ?? null,
  expiry_date: parsed.expiry_date ?? null,
  issue_date: parsed.issue_date ?? null,
  engineer_name: parsed.engineer_name ?? null,
  address: parsed.address ?? null,
};

return [
  {
    json: {
      operational_document_id: meta.operational_document_id,
      operational_object_id: meta.operational_object_id,
      user_id: meta.user_id,
      company_id: meta.company_id,
      extracted_data,
      summary,
      processed_at: new Date().toISOString(),
    },
  },
];
