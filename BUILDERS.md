# Building apps on ZapTask

ZapTask is the **engine**. Specialised products (Property, Fleet, Estate, Facilities, Personal, or your own) are **apps** that consume it.

## Product story

| Layer | Who owns it | What it does |
|---|---|---|
| **ZapTask core** | Platform | Company → Clients → Compliance → Sites → Projects → Tasks, identity, notifications |
| **Platform API + SDK** | Platform | `/api/v1/*`, `zt_live_*` keys, `@zaptask/sdk`, automations, AI |
| **Specialised app** | You | Domain UX (property ops, fleet MOT, estate viewings) that calls the engine |

Do **not** fork tasks into a parallel database. Create sites/assets, projects, and tasks through the API so they show up in ZapTask for the whole team.

## Hierarchy (do not invent a new one)

```
Company → Clients → Compliance → Sites → Projects → Tasks
```

- **Sites** = operational objects / assets (properties, vehicles, locations)
- **Compliance** = portfolio view of certificates / renewals on those sites
- Apps extend site metadata (e.g. Property fields) — they do not replace Sites

## Quick start

1. In ZapTask: **Settings → Platform → Platform API keys** → create a `zt_live_…` key
2. Enable your app under **Settings → Apps on ZapTask** (e.g. Property)
3. Install the SDK:

```bash
npm install @zaptask/sdk
# or local monorepo: "file:../../packages/zaptask-sdk"
```

```typescript
import { ZapTaskClient } from '@zaptask/sdk'

const zt = new ZapTaskClient({
  baseUrl: process.env.ZAPTASK_BASE_URL, // https://app.zaptask.co.uk
  apiKey: process.env.ZAPTASK_API_KEY,   // zt_live_...
})

// List property sites
const { assets } = await zt.assets.list({ type: 'property' })

// Create a compliance follow-up task on a site
await zt.tasks.create({
  title: 'Book Gas Safety Certificate',
  assetId: assets[0].id,
  dueDate: '2026-11-01',
  category: 'compliance',
  source: 'property-ops-app',
})

// AI as a service (preview → confirm — never silent writes)
const proposal = await zt.ai.propose({
  message: 'Remind Alex to renew the EPC for Unit 4B two weeks before 14 March',
  context: 'property_ops',
})

if (proposal.task) {
  await zt.ai.confirm({
    confirm: true,
    task: proposal.task as {
      title: string
      assigned_to?: string
      asset_id?: number
      due_date?: string
      category?: string
      priority?: string
      description?: string
    },
  })
}
```

## Platform capabilities for apps

| Capability | Endpoint / surface |
|---|---|
| Tasks | `GET/POST/PATCH /api/v1/tasks` |
| Sites / assets | `GET/POST/PATCH /api/v1/assets` |
| Checklists | `/api/v1/tasks/{id}/checklist` |
| Automations | `/api/v1/automations` + Settings → Automations |
| AI proposals | `POST /api/v1/ai` (permission `ai.write`) |
| Event automations | `task_created`, `task_completed` fire when todos change |
| Scheduled automations | `platform:run-automations` → date_reached, task_overdue, compliance_expiring |

Company scope is always derived from the API key. **Never** send a trusted `company_id` from the client.

### Cross-company portals (ZapProperty)

A consumer that aggregates *every* company — such as the ZapProperty listings portal — cannot use a `zt_live_` key, which only ever sees one company. It uses the platform-level portal key instead:

| | |
|---|---|
| Key | `ZAPPROPERTY_API_KEY` on the ZapTask server (generate with `php artisan zapproperty:key`, prefix `zp_live_`). Unset = portal disabled (503). |
| Auth | `Authorization: Bearer zp_live_…` — rejected on every company endpoint, and company keys are rejected here. |
| `GET /api/v1/zapproperty/listings` | Every active site with **Show on ZapProperty** ticked, across all companies. Same asset payload as `/assets` plus `agent` (`id`, `name`, `logo_url`, `website`). Filters: `type`, `listing_type`, `company_id`, `updated_since`, `search`; `per_page` ≤ 100. `meta.unpublished_total` counts active sites that are not ticked. |
| `GET /api/v1/zapproperty/listings/{id}` | Detail incl. `photos[]`; 404 unless published. |
| `GET /api/v1/zapproperty/listings/{id}/photos/{photoId}` | Photo bytes. |
| `GET/POST /api/v1/zapproperty/listings/{id}/tasks` | Tasks for a listing, created *inside the listing's company* (as the site's creator), `source` defaults to `zapproperty`. |

Unticking the box on a site removes it from the portal immediately — the filter is applied server-side.

## Example specialised consumer

See [`examples/property-ops-app`](examples/property-ops-app) — a small Node app that:

1. Lists property sites via the API
2. Creates renewal tasks for sites missing a category
3. Uses platform AI to propose a follow-up from natural language

Run it against a local or staging ZapTask with a real `zt_live_` key.

## Auth rules

- Session SPA: cookie + CSRF (dashboard /settings)
- Platform apps: `Authorization: Bearer zt_live_…`
- Chrome extension: Sanctum PAT on `/api/extension/*`
- Missing permission → 403; cross-company IDs → 404

## What “done” looks like for a specialised app

- [ ] Uses `/api/v1` or `@zaptask/sdk` (not a private DB clone of todos)
- [ ] Creates/updates Sites and Tasks that appear in ZapTask
- [ ] Optionally registers/enables a slug under Apps on ZapTask
- [ ] Uses automations and/or `POST /api/v1/ai` instead of reinventing them
