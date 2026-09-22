# ZapTask Platform v2

This document summarises the Platform v2 foundation delivered against the multi-app PRD.

## Product hierarchy

ZapTask organises work as:

**Company → Clients → Compliance → Sites → Projects → Tasks**

- **Clients** — who you work for
- **Compliance** — portfolio overview (overdue / due soon / renewals) across a client’s sites
- **Sites** — each property or asset (certificates, inspections, property details)
- **Projects / Tasks** — boards and work linked under sites

Do not add a separate Workspaces layer in the ZapTask product UI.

## What already existed

ZapTask already had a strong multi-tenant core:

- Companies + users
- Projects + todos (tasks)
- Comments, attachments, activity, notifications
- Operational objects (sites / assets) + compliance
- Session SPA API under `/api/*` and Sanctum PATs for the Chrome extension

## What Platform v2 adds

| Capability | Implementation |
|---|---|
| Versioned API | `/api/v1/*` |
| API keys | `zt_live_*` hashed keys in `taskit_api_keys` with scoped permissions |
| Assets API | Existing `taskit_operational_objects` as `/api/v1/assets` |
| Task upgrades | `category`, `metadata`, `recurrence`, `completed_at`; `asset_id` aliases `operational_object_id` |
| Checklists | `taskit_todo_checklist_items` + `/api/v1/tasks/{id}/checklist` |
| TypeScript SDK | `packages/zaptask-sdk` (`@zaptask/sdk`) |
| AI create | `POST /api/ai` + dashboard **What needs doing?** box (preview → confirm) |
| Automations | `taskit_automations` + `platform:run-automations` |
| App registry | `taskit_applications` / `taskit_company_applications` |

## Property MVP (this milestone)

Extends **Sites** (not a fork) for specialised property work:

- Property fields on sites: `property_type`, `bedrooms`, `tenure`, `occupancy_status`
- Compliance record extras: `issued_at`, `provider`
- Canonical `taskit_compliance_types` catalogue (seeded from certificate types)
- Nav order: Clients → Compliance → Sites
- Platform API asset payloads include `property` + `client_id`
- Companies can enable the `property` platform application

## Auth rules

- Company scope is derived from the authenticated session, Sanctum token, or API key.
- Clients must never supply a trusted `company_id`.
- Cross-company ID access returns 404.

## Next milestones

1. Deeper Property workflows (tenancy dates, auto tasks from compliance types UI)
2. First external specialised app consuming `/api/v1` + SDK
3. Stronger automation / AI services for apps
