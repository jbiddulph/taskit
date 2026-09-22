# ZapTask Platform v2

This document summarises the Platform v2 foundation delivered against the multi-app PRD.

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
| Workspaces | `taskit_workspaces` + `/api/v1/workspaces` |
| Assets | Existing `taskit_operational_objects` exposed as `/api/v1/assets` (no duplicate task engine) |
| Task upgrades | `workspace_id`, `category`, `metadata`, `recurrence`, `completed_at` on todos; `asset_id` aliases `operational_object_id` |
| Checklists | `taskit_todo_checklist_items` + `/api/v1/tasks/{id}/checklist` |
| Versioned API | `/api/v1/*` |
| API keys | `zt_live_*` hashed keys in `taskit_api_keys` with scoped permissions |
| TypeScript SDK | `packages/zaptask-sdk` (`@zaptask/sdk`) |
| AI create | `POST /api/ai` + dashboard **What needs doing?** box (preview → confirm) |
| Automations | `taskit_automations` + `platform:run-automations` (initial: `date_reached` → create task) |
| App registry | `taskit_applications` / `taskit_company_applications` seed |

## Auth rules

- Company scope is derived from the authenticated session, Sanctum token, or API key.
- Clients must never supply a trusted `company_id`.
- Cross-company ID access returns 404.

## Next milestone

ZapTask Property MVP (properties, compliance types/records, dashboard, auto tasks, document AI).
