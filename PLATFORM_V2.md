# ZapTask Platform v2

This document summarises the Platform v2 foundation delivered against the multi-app PRD.

## Product principle

**ZapTask is the engine specialised apps build upon.**

- Core hierarchy and work graph live in ZapTask
- Apps (Property, Fleet, Estate, Facilities, Personal, or third-party) consume `/api/v1` + `@zaptask/sdk`
- Automations and AI are platform services — not dashboard-only features
- Builder guide: [`BUILDERS.md`](BUILDERS.md) · Example consumer: [`examples/property-ops-app`](examples/property-ops-app)

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
| TypeScript SDK | `packages/zaptask-sdk` (`@zaptask/sdk`) — tasks, assets, automations, **AI** |
| AI (session) | `POST /api/ai` + dashboard **What needs doing?** box (preview → confirm) |
| AI (apps) | `POST /api/v1/ai` with `ai.write` permission — same preview → confirm contract |
| Automations | `taskit_automations`; event triggers wired from Todo lifecycle; scheduled via `platform:run-automations` |
| App registry | `taskit_applications` / `taskit_company_applications` + **Settings → Apps on ZapTask** |
| Example app | `examples/property-ops-app` — Node consumer of sites, tasks, and platform AI |

## Automations engine

| Trigger | How it fires |
|---|---|
| `task_created` | TodoObserver on create (skips `source=automation` to avoid loops) |
| `task_completed` | TodoObserver when status → `done` / `completed` |
| `date_reached` | Daily `platform:run-automations` |
| `task_overdue` | Same command — incomplete todos past due |
| `compliance_expiring` | Same command — requirements due within `days_before` |

Actions: `create_task`, `create_future_task`, `send_notification`, `change_status`, `assign_user`.

Optional `trigger_config` filters: `category`, `project_id`, `asset_id`.

## Property MVP

Extends **Sites** (not a fork) for specialised property / estate-agent work:

- Property fields on sites: `property_type`, `bedrooms`, `tenure`, `occupancy_status`
- **Multiple listing photos** per site (cover + gallery) — UI on Sites, API `/api/v1/assets/{id}/photos`
- Compliance record extras: `issued_at`, `provider`
- Canonical `taskit_compliance_types` catalogue (seeded from certificate types)
- Nav order: Clients → Compliance → Sites
- Platform API asset payloads include `property` + `client_id` + `photo_count` / `cover_photo_url`
- Companies can enable the `property` platform application
- **ZapProperty listings**: `show_on_zapproperty` + listing fields on sites; the portal reads them cross-company via `/api/v1/zapproperty/*` with the single `ZAPPROPERTY_API_KEY` (see BUILDERS.md)

## Auth rules

- Company scope is derived from the authenticated session, Sanctum token, or API key.
- Clients must never supply a trusted `company_id`.
- Cross-company ID access returns 404.
- The one exception is the ZapProperty portal key (`zp_live_`), which is read-only across companies for *published* listings and can only write tasks into the listing's own company.

## Settings surfaces

- **Platform** — overview
- **Apps on ZapTask** — enable Property / Fleet / …
- **Platform API keys** — `zt_live_` keys
- **Automations** — when/then rules shared by all apps

## Next milestones

1. Deeper Property workflows (tenancy dates, richer compliance-type UI)
2. Hosted specialised app shells beyond the Node example
3. Webhook delivery for automation actions (`send_email`, external HTTP)
