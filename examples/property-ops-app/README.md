# Property Ops — specialised ZapTask consumer

Minimal Node example of an **app on ZapTask**: it never stores tasks itself. It calls the Platform API / SDK so work lands in ZapTask for the company.

## What it demonstrates

1. Authenticate with a `zt_live_` platform key
2. List property **sites** (`/api/v1/assets`)
3. Create a compliance renewal **task** on a site
4. Call **platform AI** (`POST /api/v1/ai`) with preview → confirm

## Setup

```bash
cd examples/property-ops-app
cp .env.example .env
# set ZAPTASK_BASE_URL and ZAPTASK_API_KEY
npm install
npm start
```

Create the API key in ZapTask: **Settings → Platform → Platform API keys**.
Enable Property: **Settings → Apps on ZapTask**.

## Product framing

ZapTask = engine (hierarchy, tasks, automations, AI).  
Property Ops = specialised UX that consumes that engine.

See [`BUILDERS.md`](../../BUILDERS.md) for the full builder story.
