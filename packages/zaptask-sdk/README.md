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
  type: 'vehicle',
  name: 'Ford Transit',
  reference: 'AB12 CDE',
  metadata: { make: 'Ford', model: 'Transit', year: 2023 },
})
```

## Auth

Pass a company API key (`zt_live_…`) created via `POST /api/v1/api-keys`.
Company scope is derived from the key — never send `company_id` from the client.
