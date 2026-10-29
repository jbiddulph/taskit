<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    SEO_PROPERTY_COMPLIANCE_DESCRIPTION,
    SEO_PROPERTY_COMPLIANCE_TITLE,
} from '@/constants/seo';

const canonical = 'https://www.zaptask.co.uk/property-compliance';
const registerUrl = '/register?source=property-compliance&plan=MIDI';

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: 'ZapTask Property Compliance',
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    description: SEO_PROPERTY_COMPLIANCE_DESCRIPTION,
    url: canonical,
    offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'GBP',
    },
    audience: {
        '@type': 'Audience',
        audienceType: 'Landlords, property managers, and letting agents',
    },
    featureList: [
        'AI PDF date extraction',
        'Property-linked certificates',
        'Gas safety and EICR reminders',
        'Automated compliance tasks',
    ],
};

const steps = [
    {
        title: 'Upload PDF',
        description: 'Add gas safety certificates, EICRs, insurance policies, service records or invoices.',
    },
    {
        title: 'AI extracts dates',
        description: 'ZapTask reads the document, identifies the property, due dates and actions required.',
    },
    {
        title: 'Get reminders',
        description: 'Tasks are created and linked to each address — with alerts before action is needed.',
    },
];

const documentTypes = [
    'Gas safety certificates',
    'Electrical inspection reports (EICRs)',
    'Fire safety documentation',
    'Insurance renewals',
    'Tenancy and maintenance documents',
    'Invoices and payment due dates',
];

const audiences = [
    {
        title: 'Landlords and property managers',
        description: 'See which certificates are due at each address across a multi-property portfolio.',
    },
    {
        title: 'Letting and estate agents',
        description: 'Keep compliance, renewals and contractor follow-ups organised for every managed unit.',
    },
    {
        title: 'Trades and maintenance companies',
        description: 'Track inspection documents, recurring servicing and upcoming renewals for customers.',
    },
];
</script>

<template>
    <SeoHead
        :title="SEO_PROPERTY_COMPLIANCE_TITLE"
        :description="SEO_PROPERTY_COMPLIANCE_DESCRIPTION"
        keywords="gas safety certificate reminder software, property compliance management software, landlord certificate tracking software, document expiry reminder software, EICR tracking software UK"
        :json-ld="jsonLd"
        :canonical="canonical"
        image="/zap_icon.png"
    />
    <Head>
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-slate-50 dark:bg-gray-950">
        <header class="fixed top-0 left-0 right-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2">
                    <img src="/zap_icon.png" alt="ZapTask" class="h-8 w-8 dark:rounded-md dark:bg-white dark:p-1" />
                    <span class="text-xl font-bold text-slate-900 dark:text-white">ZapTask</span>
                </Link>
                <nav class="hidden items-center gap-6 text-sm md:flex">
                    <a href="#how-it-works" class="text-slate-600 hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">How it works</a>
                    <a href="#example" class="text-slate-600 hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">Example</a>
                    <a href="#pricing" class="text-slate-600 hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">Pricing</a>
                    <a href="/demo" class="text-slate-600 hover:text-slate-900 dark:text-gray-300 dark:hover:text-white">Demo</a>
                </nav>
                <PublicNavigation />
            </div>
        </header>

        <main class="pt-24">
            <!-- Hero: one composition — brand, headline, support, CTA, dominant visual -->
            <section class="relative overflow-hidden border-b border-slate-200 dark:border-gray-800">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(14,116,144,0.14),_transparent_55%),linear-gradient(180deg,#f8fafc_0%,#eef6f8_55%,#f8fafc_100%)] dark:bg-[radial-gradient(ellipse_at_top_right,_rgba(34,211,238,0.12),_transparent_50%),linear-gradient(180deg,#030712_0%,#0f172a_100%)]" />
                <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8 lg:py-20">
                    <div>
                        <p class="mb-3 text-sm font-semibold tracking-wide text-cyan-800 dark:text-cyan-300">
                            ZapTask AI
                        </p>
                        <h1 class="mb-4 text-4xl font-bold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                            Never Miss an Important Document Deadline
                        </h1>
                        <p class="mb-8 max-w-xl text-lg text-slate-600 dark:text-gray-300">
                            Upload certificates, service records and invoices. ZapTask uses AI to extract important dates, create tasks and remind you when action is needed — linked to each property you manage.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="registerUrl"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400"
                            >
                                Start managing your documents
                            </Link>
                            <a
                                href="#example"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white/80 px-6 py-3 text-sm font-semibold text-slate-800 hover:bg-white dark:border-gray-600 dark:bg-gray-900/60 dark:text-gray-100 dark:hover:bg-gray-800"
                            >
                                See example extraction
                            </a>
                        </div>
                    </div>

                    <div class="relative" aria-hidden="true">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5 dark:border-gray-700 dark:bg-gray-900">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm text-slate-500 dark:text-gray-400">Gas_Safety_Certificate.pdf</p>
                                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Example AI extraction · Processed</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Ready</span>
                            </div>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-gray-800">
                                    <dt class="text-slate-500 dark:text-gray-400">Document type</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">Gas Safety Certificate</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-gray-800">
                                    <dt class="text-slate-500 dark:text-gray-400">Property</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">24 High Street</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-gray-800">
                                    <dt class="text-slate-500 dark:text-gray-400">Next check due</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">15 March 2027</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-gray-800">
                                    <dt class="text-slate-500 dark:text-gray-400">Assigned to</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">Property Manager</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500 dark:text-gray-400">Suggested reminders</dt>
                                    <dd class="text-right font-medium text-slate-900 dark:text-white">60, 30 &amp; 7 days before</dd>
                                </div>
                            </dl>
                            <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                                Illustrative data. Extracted dates and compliance requirements should be confirmed before reminders are activated.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mb-10 max-w-2xl">
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Upload. Extract. Track. Relax.</h2>
                    <p class="mt-3 text-lg text-slate-600 dark:text-gray-300">
                        ZapTask doesn’t just store PDFs — it turns documents into actionable tasks with automated reminders.
                    </p>
                </div>
                <ol class="grid gap-6 md:grid-cols-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="rounded-xl border border-slate-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-cyan-100 text-sm font-bold text-cyan-900 dark:bg-cyan-900/40 dark:text-cyan-200">
                            {{ index + 1 }}
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-white">{{ step.title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-gray-300">{{ step.description }}</p>
                    </li>
                </ol>
            </section>

            <section id="example" class="border-y border-slate-200 bg-white py-16 dark:border-gray-800 dark:bg-gray-900/40">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-10 max-w-3xl">
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">
                            Every certificate linked to the right property
                        </h2>
                        <p class="mt-3 text-lg text-slate-600 dark:text-gray-300">
                            Managing 20 properties? ZapTask attaches each PDF to an individual address so you can see which gas safety, EICR or insurance renewals are due — and where.
                        </p>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-950">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Track across your portfolio</h3>
                            <ul class="space-y-3 text-sm text-slate-700 dark:text-gray-200">
                                <li v-for="doc in documentTypes" :key="doc" class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-cyan-600 dark:bg-cyan-400" />
                                    {{ doc }}
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-950">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Built for</h3>
                            <div class="space-y-5">
                                <div v-for="audience in audiences" :key="audience.title">
                                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ audience.title }}</h4>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-gray-300">{{ audience.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pricing" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white shadow-lg dark:border-gray-700 sm:p-10">
                    <h2 class="text-3xl font-bold">Property compliance, without the spreadsheet chase</h2>
                    <p class="mt-3 max-w-2xl text-slate-300">
                        MIDI and MAXI plans include Sites &amp; Assets, Property Compliance, document upload and AI scanning. Start free, then upgrade when you need portfolio tracking and AI extraction.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="registerUrl"
                            class="inline-flex items-center rounded-lg bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 hover:bg-cyan-300"
                        >
                            Start free — upgrade for AI compliance
                        </Link>
                        <Link
                            href="/#pricing"
                            class="inline-flex items-center rounded-lg border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            Compare plans
                        </Link>
                        <Link
                            href="/demo"
                            class="inline-flex items-center rounded-lg border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            Try the demo
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white py-10 dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <img src="/zap_icon.png" alt="" class="h-6 w-6 dark:rounded dark:bg-white dark:p-0.5" />
                    <span>ZapTask — AI document, compliance &amp; task management</span>
                </div>
                <div class="flex flex-wrap gap-4">
                    <Link href="/" class="hover:text-slate-800 dark:hover:text-white">Home</Link>
                    <Link href="/property-management" class="hover:text-slate-800 dark:hover:text-white">Property management</Link>
                    <Link href="/contact" class="hover:text-slate-800 dark:hover:text-white">Contact</Link>
                    <Link href="/privacy" class="hover:text-slate-800 dark:hover:text-white">Privacy</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
