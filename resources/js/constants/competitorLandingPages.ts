export type CompetitorLandingKind = 'comparison' | 'alternative';

export interface CompetitorLandingPage {
    slug: string;
    kind: CompetitorLandingKind;
    name: string;
    headline: string;
    subheadline: string;
    points: string[];
    ctaHeadline: string;
}

export const COMPETITOR_LANDING_PAGES: CompetitorLandingPage[] = [
    {
        slug: 'trello',
        kind: 'comparison',
        name: 'Trello',
        headline: 'ZapTask vs Trello for teams who work on site',
        subheadline:
            'Trello is great for freeform boards, but they get messy fast. ZapTask keeps an opinionated workflow, pins jobs to addresses, and helps field teams plan routes.',
        points: [
            'Opinionated columns (Todo → In Progress → Q&A → Done) instead of endless freeform lists',
            'Location tasks on a map with routes and on-site check-in',
            'Built for estate agents, trades, and small businesses—not just remote software teams',
            'Real-time activity feed and collaboration without board sprawl',
        ],
        ctaHeadline: 'Try ZapTask alongside your Trello boards',
    },
    {
        slug: 'asana',
        kind: 'comparison',
        name: 'Asana',
        headline: 'ZapTask vs Asana when you want less setup',
        subheadline:
            'Asana is powerful, but the views and configuration can overwhelm smaller teams. ZapTask stays lean so people actually use the board.',
        points: [
            'Less configuration—start with a clear board in minutes',
            'Maps and routes for viewings, call-outs, and site visits',
            'Transparent pricing with simple FREE, MIDI, and MAXI tiers',
            'Voice tasks and meeting notes for teams away from a desk',
        ],
        ctaHeadline: 'Switch one project from Asana to ZapTask this week',
    },
    {
        slug: 'monday',
        kind: 'comparison',
        name: 'Monday.com',
        headline: 'ZapTask vs Monday.com for property and trade teams',
        subheadline:
            'Monday.com can feel like a full work OS. ZapTask focuses on jobs, locations, and compliance reminders without the enterprise complexity.',
        points: [
            'Pin maintenance, viewings, and inspections to real addresses',
            'Plan daily routes across town for negotiators and engineers',
            'Track certificates and renewals with AI document reminders',
            'A board non-technical staff will recognise immediately',
        ],
        ctaHeadline: 'See Monday.com complexity side-by-side with ZapTask',
    },
    {
        slug: 'jira',
        kind: 'comparison',
        name: 'Jira',
        headline: 'ZapTask vs Jira without the admin overhead',
        subheadline:
            'Jira shines for engineering orgs. Agencies and field teams often need a cleaner board clients and contractors can follow.',
        points: [
            'Easier onboarding for clients and non-technical stakeholders',
            'No complex workflows, schemes, or admin tax',
            'Fast, clean UI tuned for everyday execution',
            'Location-aware tasks when work happens on site',
        ],
        ctaHeadline: 'Run your next client project in ZapTask',
    },
    {
        slug: 'trello',
        kind: 'alternative',
        name: 'Trello',
        headline: 'A Trello alternative built for location work',
        subheadline:
            'Keep the simplicity of cards, then add maps, routes, check-in, and guardrails so boards stay useful as your team grows.',
        points: [
            'Clear workflow columns instead of a wall of cards',
            'Filtering, bulk editing, and an activity feed for growing teams',
            'Start free, then upgrade when you need compliance and AI scanning',
            'Designed for estate agents, agencies, trades, and founders',
        ],
        ctaHeadline: 'Move your next board off Trello',
    },
    {
        slug: 'asana',
        kind: 'alternative',
        name: 'Asana',
        headline: 'An Asana alternative that stays out of the way',
        subheadline:
            'If Asana feels like too many views for the job, ZapTask gives one focused board with just enough structure.',
        points: [
            'Fast to onboard negotiators, contractors, and office staff',
            'Location tasks, routes, and on-site check-in',
            'Voice capture for updates between appointments',
            'No maze of portfolios, goals, and custom fields to learn first',
        ],
        ctaHeadline: 'Try ZapTask as your lighter Asana alternative',
    },
    {
        slug: 'monday',
        kind: 'alternative',
        name: 'Monday.com',
        headline: 'A Monday.com alternative for teams on the road',
        subheadline:
            'Replace dashboards-of-everything with jobs pinned to addresses, simple boards, and reminders before deadlines slip.',
        points: [
            'Property and trade workflows without a full work OS',
            'Portfolio map for sites, listings, and call-outs',
            'AI document scanning for certificates and renewals',
            'Pricing that stays understandable as you grow',
        ],
        ctaHeadline: 'Replace one Monday board with ZapTask',
    },
    {
        slug: 'jira',
        kind: 'alternative',
        name: 'Jira',
        headline: 'A Jira alternative for agencies and everyday delivery',
        subheadline:
            'Skip issue types and workflow schemes. ZapTask removes admin overhead while keeping a board clients can understand.',
        points: [
            'Lightweight enough for fast-moving agencies',
            'Powerful where it matters: filters, activity, and collaboration',
            'Clean client-facing boards without developer jargon',
            'Optional maps when delivery includes site visits',
        ],
        ctaHeadline: 'Use ZapTask for your next non-dev project',
    },
    {
        slug: 'spreadsheets',
        kind: 'alternative',
        name: 'spreadsheets',
        headline: 'A spreadsheet alternative for jobs and compliance',
        subheadline:
            'Stop chasing deadlines in Excel or Google Sheets. ZapTask turns documents and due dates into trackable work with reminders.',
        points: [
            'Upload certificates and let AI extract renewal dates',
            'Attach jobs to property addresses across a portfolio',
            'See overdue work before it becomes a problem',
            'Share a clear board instead of another shared tab',
        ],
        ctaHeadline: 'Retire the job-tracker spreadsheet',
    },
];

export function getCompetitorLandingPage(
    kind: CompetitorLandingKind,
    slug: string,
): CompetitorLandingPage | undefined {
    return COMPETITOR_LANDING_PAGES.find((page) => page.kind === kind && page.slug === slug);
}
