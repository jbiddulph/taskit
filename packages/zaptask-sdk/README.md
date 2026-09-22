# @zaptask/sdk

TypeScript client for the ZapTask Platform API (`/api/v1`).

## Install

```bash
npm install @zaptask/sdk
```

Or use the local package path in a monorepo:

```json
{
  "dependencies": {
    "@zaptask/sdk": "file:packages/zaptask-sdk"
  }
}
```

## Usage

```typescript
import { ZapTaskClient } from '@zaptask/sdk'

const client = new ZapTaskClient({
  baseUrl: 'https://app.zaptask.co.uk',
  apiKey: process.env.ZAPTASK_API_KEY, // zt_live_...
})

const { tasks } = await client.tasks.list()

await client.tasks.create({
  title: 'Renew Gas Safety Certificate',
  dueDate: '2027-02-14',
  assetId: 42,
  priority: 'high',
  source: 'api',
})

await client.assets.create({
  type: 'property',
  name: '24 High Street',
  reference: 'Unit 4B',
  property_type: 'flat',
  bedrooms: 2,
  tenure: 'leasehold',
  occupancy_status: 'occupied',
  address_line_1: '24 High Street',
  city: 'London',
  postal_code: 'E1 1AA',
})
```

Hierarchy reminder: **Company → Clients → Compliance → Sites → Projects → Tasks**.
Sites/assets are the property layer specialised apps extend.

## AI for apps

```typescript
const proposal = await client.ai.propose({
  message: 'Remind Alex to renew gas safety two weeks before 14 March',
  context: 'property_ops',
})

await client.ai.confirm({
  confirm: true,
  task: {
    title: proposal.task!.title as string,
    due_date: proposal.task!.due_date as string | undefined,
    asset_id: proposal.task!.asset_id as number | undefined,
    category: 'compliance',
  },
})
```

Requires API key permission `ai.write` (`POST /api/v1/ai`).

## Auth

Pass a company API key (`zt_live_…`) created via `POST /api/v1/api-keys` or **Settings → Platform API keys**.
Company scope is derived from the key — never send `company_id` from the client.

See [`BUILDERS.md`](../../BUILDERS.md) for the apps-on-ZapTask product story.
