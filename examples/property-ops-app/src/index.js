/**
 * Property Ops — specialised ZapTask consumer.
 *
 * Usage:
 *   ZAPTASK_BASE_URL=... ZAPTASK_API_KEY=zt_live_... npm start
 *   npm run sync-renewals
 *   npm run ai-demo
 */

import { readFileSync, existsSync } from 'node:fs'
import { resolve, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import { ZapTaskClient } from '@zaptask/sdk'

const __dirname = dirname(fileURLToPath(import.meta.url))

function loadEnvFile() {
  const envPath = resolve(__dirname, '../.env')
  if (!existsSync(envPath)) return
  for (const line of readFileSync(envPath, 'utf8').split('\n')) {
    const trimmed = line.trim()
    if (!trimmed || trimmed.startsWith('#')) continue
    const eq = trimmed.indexOf('=')
    if (eq === -1) continue
    const key = trimmed.slice(0, eq).trim()
    const value = trimmed.slice(eq + 1).trim()
    if (!process.env[key]) process.env[key] = value
  }
}

loadEnvFile()

const baseUrl = process.env.ZAPTASK_BASE_URL
const apiKey = process.env.ZAPTASK_API_KEY

if (!baseUrl || !apiKey || apiKey.includes('replace_me')) {
  console.error('Set ZAPTASK_BASE_URL and ZAPTASK_API_KEY (zt_live_…) in .env')
  process.exit(1)
}

const client = new ZapTaskClient({ baseUrl, apiKey })
const args = new Set(process.argv.slice(2))

async function listPropertySites() {
  const result = await client.assets.list({ type: 'property', per_page: 50 })
  const assets = result.assets ?? result?.data?.assets ?? []
  console.log(`\nProperty sites (${assets.length}):`)
  for (const site of assets) {
    const prop = site.property ?? {}
    console.log(
      `  #${site.id}  ${site.name}` +
        (site.reference ? ` [${site.reference}]` : '') +
        (prop.property_type ? ` · ${prop.property_type}` : '') +
        (prop.occupancy_status ? ` · ${prop.occupancy_status}` : ''),
    )
  }
  return assets
}

async function syncRenewals(assets) {
  if (!assets.length) {
    console.log('\nNo property sites found — create one in ZapTask Sites first.')
    return
  }

  const site = assets[0]
  const due = new Date()
  due.setDate(due.getDate() + 21)
  const dueDate = due.toISOString().slice(0, 10)

  const task = await client.tasks.create({
    title: `Gas Safety renewal — ${site.name}`,
    description: 'Created by Property Ops example app via Platform API.',
    assetId: site.id,
    dueDate,
    category: 'compliance',
    priority: 'high',
    source: 'property-ops-app',
    metadata: {
      app: 'property-ops',
      workflow: 'certificate_renewal',
    },
  })

  console.log(`\nCreated renewal task #${task.id ?? task?.data?.id}: ${task.title ?? task?.data?.title}`)
}

async function aiDemo() {
  console.log('\nAI propose (no write yet)…')
  const proposal = await client.ai.propose({
    message:
      'Remind the property manager to book the Gas Safety Certificate for the first site two weeks before the end of next month.',
    context: 'property_ops',
  })

  console.log('  intent:', proposal.intent)
  console.log('  preview:', JSON.stringify(proposal.preview ?? proposal.task, null, 2))

  if (!proposal.task?.title) {
    console.log('  No task proposal returned — skipping confirm.')
    return
  }

  const created = await client.ai.confirm({
    confirm: true,
    message: 'property-ops ai-demo',
    task: {
      title: String(proposal.task.title),
      assigned_to: proposal.task.assigned_to ?? undefined,
      asset_id: proposal.task.asset_id ?? undefined,
      due_date: proposal.task.due_date ?? undefined,
      category: proposal.task.category ?? 'compliance',
      priority: proposal.task.priority ?? 'normal',
      description: proposal.task.description ?? undefined,
    },
  })

  console.log(`\nConfirmed AI task #${created.task?.id}: ${created.task?.title}`)
}

async function main() {
  console.log('Property Ops → ZapTask platform')
  console.log(`  base: ${baseUrl}`)

  const assets = await listPropertySites()

  if (args.has('--sync-renewals') || args.size === 0) {
    await syncRenewals(assets)
  }

  if (args.has('--ai-demo') || args.size === 0) {
    await aiDemo()
  }

  console.log('\nDone. Open ZapTask to see tasks created by this specialised app.')
}

main().catch((err) => {
  console.error('\nFailed:', err.message ?? err)
  if (err.body) console.error(JSON.stringify(err.body, null, 2))
  process.exit(1)
})
