export interface IndustryScreenshot {
    src: string;
    alt: string;
    caption: string;
}

export interface IndustryFeature {
    icon: string;
    title: string;
    description: string;
}

export interface IndustryExampleTask {
    title: string;
    location?: string;
    column: string;
}

export interface IndustryLandingPage {
    slug: string;
    label: string;
    audienceLabel: string;
    headline: string;
    subheadline: string;
    keywords: string;
    seoTitle: string;
    seoDescription: string;
    screenshots: IndustryScreenshot[];
    features: IndustryFeature[];
    exampleTasks: IndustryExampleTask[];
    ctaHeadline: string;
    ctaSubheadline: string;
}

const SCREENSHOTS = {
    map: {
        src: '/images/activity-feed.png',
    },
    calendar: {
        src: '/images/calendar.png',
    },
    board: {
        src: '/images/bulk-editor.png',
    },
} as const;

function shots(
    map: { alt: string; caption: string },
    calendar: { alt: string; caption: string },
    board: { alt: string; caption: string },
): IndustryScreenshot[] {
    return [
        { src: SCREENSHOTS.map.src, ...map },
        { src: SCREENSHOTS.calendar.src, ...calendar },
        { src: SCREENSHOTS.board.src, ...board },
    ];
}

export const INDUSTRY_LANDING_PAGES: IndustryLandingPage[] = [
    {
        slug: 'estate-agents',
        label: 'Estate agents',
        audienceLabel: 'Built for estate agents',
        headline: 'Plan viewings, route your team, and never lose track of a listing',
        subheadline:
            'ZapTask is simple task management with a map—not another bloated CRM. Pin tasks to properties, see today\'s viewings on a route, check in when you arrive, and keep sales progression visible on a clear Kanban board.',
        keywords:
            'estate agent task management, property viewing planner, estate agency software UK, viewing route planner, property task app',
        seoTitle: 'Task Management for Estate Agents | ZapTask',
        seoDescription:
            'Plan property viewings on a map, route your negotiators, and track every listing task. ZapTask is built for estate agents who work on the move.',
        screenshots: shots(
            {
                alt: 'Estate agent viewing tasks on a map',
                caption: 'See every viewing, valuation, and inspection pinned to the property address.',
            },
            {
                alt: 'Estate agent calendar with viewing appointments',
                caption: 'Schedule negotiator diaries and morning briefings in one place.',
            },
            {
                alt: 'Sales progression Kanban board for estate agents',
                caption: 'Track offers, surveys, and completions on a board the whole branch understands.',
            },
        ),
        features: [
            {
                icon: '📍',
                title: 'Tasks on the map',
                description:
                    'Attach viewings, valuations, and inspections to property addresses. Branch managers see every job with a location on one map.',
            },
            {
                icon: '🗺️',
                title: 'My route today',
                description:
                    'Filter tasks assigned to you, plan a driving route between viewings, and open Apple or Google Maps with one tap.',
            },
            {
                icon: '✅',
                title: 'Check in on site',
                description:
                    'Negotiators check in when they arrive so the office knows viewings are happening without chasing WhatsApp updates.',
            },
            {
                icon: '📋',
                title: 'Sales progression board',
                description:
                    'Track offers, surveys, exchanges, and completions on a Kanban board—no scrum training required.',
            },
            {
                icon: '🎙️',
                title: 'Voice & meeting notes',
                description:
                    'Record your morning huddle and let AI turn it into tasks—with locations picked up from speech.',
            },
            {
                icon: '👥',
                title: 'Small agency friendly',
                description: 'Free for solo agents. Affordable plans for branches with multiple negotiators and listing portfolios.',
            },
        ],
        exampleTasks: [
            { title: 'Morning viewing — 14 Oak Lane', location: 'Brighton', column: 'Today' },
            { title: 'Vendor valuation — 8 High Street', location: 'Hove', column: 'Today' },
            { title: 'Awaiting survey report', column: 'In progress' },
            { title: 'Exchange — Flat 3, Marina Court', column: 'Exchange' },
        ],
        ctaHeadline: 'Replace sticky notes and scattered spreadsheets',
        ctaSubheadline:
            'Independent agents, lettings teams, and high-street branches use ZapTask to coordinate work without enterprise software complexity.',
    },
    {
        slug: 'property-management',
        label: 'Property management',
        audienceLabel: 'Built for property managers',
        headline: 'Coordinate maintenance, inspections, and tenant requests across your portfolio',
        subheadline:
            'Pin every job to the right property, assign contractors, and see what your team is doing today on a map. ZapTask helps property managers stay on top of repairs, compliance checks, and void works without drowning in email threads.',
        keywords:
            'property management software UK, landlord maintenance tracker, block management tasks, tenant request management, property portfolio task app',
        seoTitle: 'Task Management for Property Management Companies | ZapTask',
        seoDescription:
            'Track maintenance, inspections, and tenant requests across your property portfolio with location tasks, maps, and simple Kanban boards.',
        screenshots: shots(
            {
                alt: 'Property management jobs on a map',
                caption: 'Every unit, block, and portfolio address visible on one map.',
            },
            {
                alt: 'Maintenance schedule calendar for property managers',
                caption: 'Plan inspections, gas safety renewals, and contractor visits.',
            },
            {
                alt: 'Property maintenance workflow board',
                caption: 'Move jobs from reported → assigned → completed with full visibility.',
            },
        ),
        features: [
            {
                icon: '🏢',
                title: 'Portfolio-wide visibility',
                description: 'Attach tasks to individual flats, HMOs, or whole blocks so nothing gets lost between properties.',
            },
            {
                icon: '🔧',
                title: 'Contractor coordination',
                description: 'Assign plumbing, electrical, and cleaning jobs to your team or external trades with clear due dates.',
            },
            {
                icon: '📋',
                title: 'Compliance reminders',
                description: 'Track gas safety, EPC renewals, and inspection deadlines on a board your office can monitor at a glance.',
            },
            {
                icon: '🗺️',
                title: 'Field team routes',
                description: 'Property inspectors and maintenance coordinators plan efficient daily routes across town.',
            },
            {
                icon: '🎙️',
                title: 'Voice capture on site',
                description: 'Record snagging notes or tenant issues by voice and turn them into assigned tasks instantly.',
            },
            {
                icon: '📱',
                title: 'Simple for non-technical teams',
                description: 'No CRM complexity—just tasks, locations, and a board your lettings and maintenance staff will actually use.',
            },
        ],
        exampleTasks: [
            { title: 'Boiler repair — Flat 12, Riverside Court', location: 'Leeds', column: 'Urgent' },
            { title: 'Annual gas safety — 44 Park View', location: 'Leeds', column: 'Scheduled' },
            { title: 'Void refurbishment quote', column: 'Awaiting contractor' },
            { title: 'Tenant check-in inspection', location: 'Manchester', column: 'This week' },
        ],
        ctaHeadline: 'Stop juggling tenant emails and spreadsheet trackers',
        ctaSubheadline: 'Property management companies use ZapTask to keep maintenance, compliance, and void works organised across every address they manage.',
    },
    {
        slug: 'construction',
        label: 'Construction',
        audienceLabel: 'Built for construction teams',
        headline: 'Run site visits, snagging, and subcontractor work from one simple board',
        subheadline:
            'Whether you are a builder, contractor, or project coordinator, ZapTask keeps jobs tied to site addresses, routes your team efficiently, and makes daily progress visible without heavyweight project software.',
        keywords:
            'construction task management, builder job tracker UK, site visit planner, subcontractor coordination app, construction Kanban',
        seoTitle: 'Task Management for Construction Companies | ZapTask',
        seoDescription:
            'Plan site visits, snagging lists, and subcontractor jobs on a map. ZapTask helps construction teams coordinate field work without complex project software.',
        screenshots: shots(
            {
                alt: 'Construction sites on a task map',
                caption: 'See active sites, surveys, and handovers pinned to addresses.',
            },
            {
                alt: 'Construction project schedule calendar',
                caption: 'Plan inspections, deliveries, and trade visits across the week.',
            },
            {
                alt: 'Construction job progress board',
                caption: 'Track quoting, on-site work, snagging, and sign-off in clear columns.',
            },
        ),
        features: [
            {
                icon: '🏗️',
                title: 'Jobs tied to sites',
                description: 'Every task links to the property or site address so your team always knows where to go.',
            },
            {
                icon: '📐',
                title: 'Snagging & handover lists',
                description: 'Capture defects on site and track them through to completion on a shared board.',
            },
            {
                icon: '🚐',
                title: 'Daily route planning',
                description: 'Site managers plan efficient runs between active jobs and supplier pickups.',
            },
            {
                icon: '👷',
                title: 'Subcontractor handoffs',
                description: 'Assign electrical, plumbing, and finishing work with due dates everyone can see.',
            },
            {
                icon: '🎙️',
                title: 'Site notes by voice',
                description: 'Record walk-through notes hands-free and convert them into actionable tasks.',
            },
            {
                icon: '💷',
                title: 'Affordable for SMEs',
                description: 'Built for owner-operators and small construction firms—not enterprise PM suites.',
            },
        ],
        exampleTasks: [
            { title: 'First fix inspection — Plot 7', location: 'Bristol', column: 'On site' },
            { title: 'Order materials — kitchen fit', column: 'To do' },
            { title: 'Snagging — 22 Elm Close', location: 'Bath', column: 'Snagging' },
            { title: 'Client handover walkthrough', column: 'Scheduled' },
        ],
        ctaHeadline: 'Keep your sites moving without spreadsheet chaos',
        ctaSubheadline: 'Builders and contractors use ZapTask to coordinate site work, trades, and client updates from one place.',
    },
    {
        slug: 'electricians',
        label: 'Electricians',
        audienceLabel: 'Built for electricians',
        headline: 'Schedule call-outs, route your vans, and track every job to completion',
        subheadline:
            'Pin domestic and commercial jobs to addresses, plan your day on a map, and keep certification and follow-up work visible on a board your whole team can use from their phones.',
        keywords:
            'electrician job management, electrical contractor scheduler, call-out route planner, electrician task app UK',
        seoTitle: 'Task Management for Electricians | ZapTask',
        seoDescription:
            'Plan electrical call-outs on a map, route your vans, and track certification and follow-ups. ZapTask helps electricians run jobs without paperwork overload.',
        screenshots: shots(
            {
                alt: 'Electrician jobs plotted on a map',
                caption: 'Domestic and commercial call-outs visible by location.',
            },
            {
                alt: 'Electrician appointment calendar',
                caption: 'Book installs, EICRs, and emergency call-outs across the week.',
            },
            {
                alt: 'Electrical job workflow board',
                caption: 'Move jobs from quoted → booked → completed → certified.',
            },
        ),
        features: [
            {
                icon: '⚡',
                title: 'Call-outs on the map',
                description: 'See every job with an address—ideal for solo sparkies and multi-van teams.',
            },
            {
                icon: '🚐',
                title: 'Van route planning',
                description: 'Plan the most efficient run between domestic installs and commercial maintenance.',
            },
            {
                icon: '📋',
                title: 'Certification follow-ups',
                description: 'Track EICR reminders, part-P sign-offs, and return visits on a simple board.',
            },
            {
                icon: '🎙️',
                title: 'Voice notes on site',
                description: 'Dictate snagging items or parts needed while your hands are full.',
            },
            {
                icon: '✅',
                title: 'Check in when you arrive',
                description: 'The office knows you are on site without constant phone calls.',
            },
            {
                icon: '📱',
                title: 'Works on mobile',
                description: 'Update job status from the van—no laptop required.',
            },
        ],
        exampleTasks: [
            { title: 'Consumer unit upgrade — 5 Lane End', location: 'Reading', column: 'Today' },
            { title: 'EICR — rental property', location: 'Slough', column: 'Booked' },
            { title: 'Order RCBOs for Monday job', column: 'Parts' },
            { title: 'Return visit — fault under warranty', column: 'Follow-up' },
        ],
        ctaHeadline: 'Spend more time on the tools, less on admin',
        ctaSubheadline: 'Electricians use ZapTask to organise call-outs, routes, and follow-up work without a complicated FSM system.',
    },
    {
        slug: 'plumbers',
        label: 'Plumbers',
        audienceLabel: 'Built for plumbers',
        headline: 'Organise emergency call-outs, installs, and follow-ups without the paperwork',
        subheadline:
            'Map every job to the customer address, route your engineers efficiently, and track quotes, parts orders, and return visits on a board that works as well on mobile as it does in the office.',
        keywords:
            'plumber job scheduler, plumbing business software UK, emergency call-out tracker, plumber route planner',
        seoTitle: 'Task Management for Plumbers | ZapTask',
        seoDescription:
            'Schedule plumbing call-outs on a map, route your engineers, and track quotes and return visits. Simple task management built for plumbing businesses.',
        screenshots: shots(
            {
                alt: 'Plumbing jobs on a location map',
                caption: 'Emergency and planned jobs pinned to customer addresses.',
            },
            {
                alt: 'Plumber job calendar',
                caption: 'Balance emergency call-outs with boiler installs and service visits.',
            },
            {
                alt: 'Plumbing job status board',
                caption: 'Track quoted work, parts on order, and completed jobs.',
            },
        ),
        features: [
            {
                icon: '🔧',
                title: 'Jobs at the right address',
                description: 'Every leak, install, and service visit tied to the property—no wrong-postcode mistakes.',
            },
            {
                icon: '🚨',
                title: 'Emergency vs planned work',
                description: 'Separate urgent call-outs from scheduled installs on your daily map view.',
            },
            {
                icon: '🗺️',
                title: 'Engineer routes',
                description: 'Plan efficient days across domestic and commercial contracts.',
            },
            {
                icon: '📦',
                title: 'Parts & return visits',
                description: 'Track when materials arrive and schedule follow-up appointments.',
            },
            {
                icon: '🎙️',
                title: 'Voice capture',
                description: 'Record job notes while under the sink—AI turns them into tasks.',
            },
            {
                icon: '💷',
                title: 'Priced for small firms',
                description: 'From sole traders to small plumbing companies with multiple vans.',
            },
        ],
        exampleTasks: [
            { title: 'Emergency leak — 19 Church Road', location: 'Oxford', column: 'Urgent' },
            { title: 'Boiler install — 3 Mill Lane', location: 'Abingdon', column: 'Scheduled' },
            { title: 'Order expansion vessel', column: 'Parts' },
            { title: 'Annual service — landlord portfolio', column: 'This week' },
        ],
        ctaHeadline: 'Keep your engineers on jobs, not on the phone',
        ctaSubheadline: 'Plumbing businesses use ZapTask to coordinate call-outs, installs, and follow-ups from one simple system.',
    },
    {
        slug: 'cleaning-companies',
        label: 'Cleaning companies',
        audienceLabel: 'Built for cleaning companies',
        headline: 'Schedule teams, track site visits, and never miss a recurring clean',
        subheadline:
            'Assign cleans to properties and commercial sites, see where your teams are working today, and manage one-off deep cleans alongside regular contracts on a board everyone understands.',
        keywords:
            'cleaning company scheduler, commercial cleaning task app, domestic cleaning route planner, cleaning business software UK',
        seoTitle: 'Task Management for Cleaning Companies | ZapTask',
        seoDescription:
            'Schedule cleaning teams, plan routes between sites, and track recurring and one-off jobs. ZapTask helps cleaning companies coordinate field staff simply.',
        screenshots: shots(
            {
                alt: 'Cleaning sites on a map',
                caption: 'Office, retail, and domestic cleans plotted by address.',
            },
            {
                alt: 'Cleaning team schedule calendar',
                caption: 'Recurring contracts and one-off deep cleans in one view.',
            },
            {
                alt: 'Cleaning job assignment board',
                caption: 'Assign teams, track quality checks, and manage client requests.',
            },
        ),
        features: [
            {
                icon: '🧹',
                title: 'Sites & addresses',
                description: 'Every clean tied to the client site—offices, Airbnbs, end-of-tenancy properties, and more.',
            },
            {
                icon: '📅',
                title: 'Recurring work',
                description: 'Track weekly contracts and one-off deep cleans without duplicate spreadsheets.',
            },
            {
                icon: '🗺️',
                title: 'Team routes',
                description: 'Supervisors plan efficient runs between client sites across the day.',
            },
            {
                icon: '✅',
                title: 'Check-in on site',
                description: 'Confirm teams arrived at the right property for accountability and client confidence.',
            },
            {
                icon: '📋',
                title: 'Quality checklists',
                description: 'Use task lists for inspection walks and client sign-off items.',
            },
            {
                icon: '👥',
                title: 'Multi-team coordination',
                description: 'Office staff assign work; cleaners update status from their phones.',
            },
        ],
        exampleTasks: [
            { title: 'Weekly office clean — Acme Ltd', location: 'London', column: 'Today' },
            { title: 'End of tenancy deep clean', location: 'Croydon', column: 'Scheduled' },
            { title: 'Client requested extra kitchen clean', column: 'New request' },
            { title: 'Supply restock — Unit 4', column: 'To do' },
        ],
        ctaHeadline: 'Run a tighter operation across every client site',
        ctaSubheadline: 'Cleaning companies use ZapTask to schedule teams, track sites, and respond to client requests without messy group chats.',
    },
    {
        slug: 'roofers',
        label: 'Roofers',
        audienceLabel: 'Built for roofers',
        headline: 'Quote surveys, schedule crews, and track every roof job to sign-off',
        subheadline:
            'Pin surveys and installs to property addresses, route your teams between sites, and keep materials, scaffolding, and weather delays visible on a simple job board.',
        keywords:
            'roofing business software, roofer job tracker, roof survey scheduler, roofing contractor task management UK',
        seoTitle: 'Task Management for Roofers | ZapTask',
        seoDescription:
            'Plan roof surveys, crew schedules, and install jobs on a map. ZapTask helps roofing contractors coordinate site work and client updates simply.',
        screenshots: shots(
            {
                alt: 'Roofing jobs on a map',
                caption: 'Surveys, repairs, and full re-roofs pinned to properties.',
            },
            {
                alt: 'Roofing crew schedule',
                caption: 'Plan around weather windows and scaffolding availability.',
            },
            {
                alt: 'Roofing project board',
                caption: 'From survey → quote → scaffold → install → sign-off.',
            },
        ),
        features: [
            {
                icon: '🏠',
                title: 'Jobs at the property',
                description: 'Every survey and install linked to the roof address—essential for multi-job weeks.',
            },
            {
                icon: '🌧️',
                title: 'Weather-sensitive planning',
                description: 'Reschedule and reassign quickly when conditions change.',
            },
            {
                icon: '🏗️',
                title: 'Scaffold & materials',
                description: 'Track scaffolding bookings and material deliveries as tasks.',
            },
            {
                icon: '🗺️',
                title: 'Crew routing',
                description: 'Plan efficient travel between domestic and commercial roofs.',
            },
            {
                icon: '🎙️',
                title: 'Survey notes by voice',
                description: 'Capture measurements and damage notes on the ladder safely via voice.',
            },
            {
                icon: '📋',
                title: 'Quote to completion',
                description: 'See where every job sits from first survey to final invoice.',
            },
        ],
        exampleTasks: [
            { title: 'Roof survey — 18 Hillcrest', location: 'Sheffield', column: 'Survey' },
            { title: 'Scaffold booking — Oak Avenue', column: 'Planning' },
            { title: 'Slate repair — commercial unit', location: 'Rotherham', column: 'On site' },
            { title: 'Final inspection & photos', column: 'Sign-off' },
        ],
        ctaHeadline: 'Keep crews on the right roof at the right time',
        ctaSubheadline: 'Roofing contractors use ZapTask to coordinate surveys, installs, and client communication without heavyweight software.',
    },
    {
        slug: 'landscapers',
        label: 'Landscapers',
        audienceLabel: 'Built for landscapers',
        headline: 'Plan garden visits, crew schedules, and seasonal work across your client base',
        subheadline:
            'Map maintenance rounds and one-off landscaping projects, route your teams between gardens, and track quotes, planting schedules, and client approvals on a board built for outdoor work.',
        keywords:
            'landscaping business software, gardener schedule app, grounds maintenance planner, landscaping task management UK',
        seoTitle: 'Task Management for Landscapers | ZapTask',
        seoDescription:
            'Schedule garden visits, plan crew routes, and track landscaping projects on a map. ZapTask helps gardeners and landscapers coordinate outdoor work simply.',
        screenshots: shots(
            {
                alt: 'Landscaping jobs on a map',
                caption: 'Maintenance rounds and project sites across your patch.',
            },
            {
                alt: 'Landscaping team calendar',
                caption: 'Balance regular maintenance with design-and-build projects.',
            },
            {
                alt: 'Landscaping project board',
                caption: 'Track quotes, planting, and seasonal programme work.',
            },
        ),
        features: [
            {
                icon: '🌳',
                title: 'Gardens on the map',
                description: 'Every maintenance visit and project pinned to the client address.',
            },
            {
                icon: '📅',
                title: 'Seasonal planning',
                description: 'Schedule lawn care, planting, and winter programmes without lost sticky notes.',
            },
            {
                icon: '🚐',
                title: 'Crew routes',
                description: 'Plan efficient days across domestic gardens and commercial grounds.',
            },
            {
                icon: '📋',
                title: 'Project milestones',
                description: 'Track design approval, hard landscaping, planting, and handover.',
            },
            {
                icon: '🎙️',
                title: 'Site notes outdoors',
                description: 'Voice-capture client requests while you are on the mower.',
            },
            {
                icon: '👥',
                title: 'Team visibility',
                description: 'Office and field staff share one view of who is where today.',
            },
        ],
        exampleTasks: [
            { title: 'Weekly maintenance — Manor Gardens', location: 'Guildford', column: 'Today' },
            { title: 'Patio install — client quote approved', location: 'Woking', column: 'Project' },
            { title: 'Order turf for Thursday', column: 'Materials' },
            { title: 'Spring prune — estate contract', column: 'Scheduled' },
        ],
        ctaHeadline: 'Grow your landscaping business without admin overload',
        ctaSubheadline: 'Gardeners and landscapers use ZapTask to schedule crews, plan routes, and manage projects from one place.',
    },
    {
        slug: 'facilities-management',
        label: 'Facilities management',
        audienceLabel: 'Built for facilities management',
        headline: 'Coordinate maintenance, inspections, and contractor work across every site',
        subheadline:
            'Track reactive repairs and planned maintenance across buildings and estates, assign work to in-house teams or contractors, and see open jobs by location on a map your FM team can run from day one.',
        keywords:
            'facilities management software, FM maintenance tracker, building maintenance task app, soft services scheduling UK',
        seoTitle: 'Task Management for Facilities Management | ZapTask',
        seoDescription:
            'Coordinate building maintenance, inspections, and contractor work across multiple sites with maps, routes, and Kanban boards built for FM teams.',
        screenshots: shots(
            {
                alt: 'Facilities sites on a map',
                caption: 'Offices, schools, and estates plotted for reactive and planned work.',
            },
            {
                alt: 'FM maintenance calendar',
                caption: 'PPM schedules, inspections, and contractor visits aligned.',
            },
            {
                alt: 'Facilities work order board',
                caption: 'Reactive tickets through to completion with full audit trail.',
            },
        ),
        features: [
            {
                icon: '🏢',
                title: 'Multi-site visibility',
                description: 'Every work order tied to a building, floor, or asset location.',
            },
            {
                icon: '🔧',
                title: 'Reactive & planned maintenance',
                description: 'Handle helpdesk tickets and PPM schedules on the same board.',
            },
            {
                icon: '👷',
                title: 'Contractor coordination',
                description: 'Assign external trades and track SLAs without a heavy CAFM system.',
            },
            {
                icon: '🗺️',
                title: 'Mobile engineer routes',
                description: 'Plan daily runs across your estate efficiently.',
            },
            {
                icon: '📋',
                title: 'Inspection checklists',
                description: 'Turn walk-round findings into assigned follow-up tasks.',
            },
            {
                icon: '⚡',
                title: 'Fast to roll out',
                description: 'Lighter than enterprise FM software—live in hours, not months.',
            },
        ],
        exampleTasks: [
            { title: 'HVAC fault — Building B, Level 3', location: 'Birmingham', column: 'Reactive' },
            { title: 'Fire door inspection — Block A', column: 'PPM' },
            { title: 'Contractor quote — lift service', column: 'Awaiting approval' },
            { title: 'Weekly cleaning audit', column: 'Scheduled' },
        ],
        ctaHeadline: 'Give your FM team clarity without CAFM complexity',
        ctaSubheadline: 'Facilities managers use ZapTask to coordinate maintenance and contractors across every site they oversee.',
    },
    {
        slug: 'care-agencies',
        label: 'Care agencies',
        audienceLabel: 'Built for care agencies',
        headline: 'Coordinate visits, staff tasks, and office follow-ups in one simple system',
        subheadline:
            'Plan carer visits and office administration alongside location-based tasks, keep your coordinators aligned on what needs doing today, and give managers visibility without replacing your core care scheduling system.',
        keywords:
            'care agency task management, domiciliary care coordinator software, care home admin tasks, healthcare field team planner UK',
        seoTitle: 'Task Management for Care Agencies | ZapTask',
        seoDescription:
            'Coordinate carer visits, office follow-ups, and compliance tasks with maps and boards. ZapTask supports care agency coordinators alongside your existing systems.',
        screenshots: shots(
            {
                alt: 'Care visit locations on a map',
                caption: 'Supplement rota planning with location-aware office and field tasks.',
            },
            {
                alt: 'Care agency coordinator calendar',
                caption: 'Staff supervisions, training, and client onboarding in one schedule.',
            },
            {
                alt: 'Care agency task board',
                caption: 'Track referrals, assessments, and compliance actions clearly.',
            },
        ),
        features: [
            {
                icon: '🏥',
                title: 'Visit-aware tasks',
                description: 'Link follow-up actions to client addresses for coordinator clarity.',
            },
            {
                icon: '📋',
                title: 'Compliance & onboarding',
                description: 'Track DBS renewals, training, and new client setup steps on a board.',
            },
            {
                icon: '👥',
                title: 'Coordinator visibility',
                description: 'Office team sees open actions without endless email chains.',
            },
            {
                icon: '🗺️',
                title: 'Field supervisor routes',
                description: 'Plan quality visits and spot checks across your patch.',
            },
            {
                icon: '🎙️',
                title: 'Meeting notes to tasks',
                description: 'Turn staff supervisions and family meetings into assigned actions.',
            },
            {
                icon: '🔒',
                title: 'Complements your rota system',
                description: 'ZapTask handles tasks and coordination—not clinical records.',
            },
        ],
        exampleTasks: [
            { title: 'New client home assessment', location: 'Norwich', column: 'Referral' },
            { title: 'Carer supervision — Sarah T.', column: 'This week' },
            { title: 'DBS renewal reminder — James K.', column: 'Compliance' },
            { title: 'Family review meeting notes', column: 'Follow-up' },
        ],
        ctaHeadline: 'Support your coordinators with clearer task visibility',
        ctaSubheadline: 'Care agencies use ZapTask for office coordination, compliance follow-ups, and field supervisor planning.',
    },
    {
        slug: 'restaurants',
        label: 'Restaurants',
        audienceLabel: 'Built for restaurants',
        headline: 'Run opening checklists, supplier runs, and shift tasks without the chaos',
        subheadline:
            'Keep front-of-house and kitchen teams aligned on daily jobs, track deliveries and maintenance visits by location, and replace WhatsApp lists with a board managers can actually monitor.',
        keywords:
            'restaurant task management, hospitality checklist app, restaurant operations software, kitchen task board UK',
        seoTitle: 'Task Management for Restaurants | ZapTask',
        seoDescription:
            'Run opening checklists, coordinate supplier visits, and manage shift tasks. ZapTask helps restaurant teams stay organised during busy service periods.',
        screenshots: shots(
            {
                alt: 'Restaurant operations task feed',
                caption: 'Opening, prep, and closing tasks visible to the whole team.',
            },
            {
                alt: 'Restaurant shift schedule calendar',
                caption: 'Plan maintenance, deliveries, and training around service hours.',
            },
            {
                alt: 'Restaurant task assignment board',
                caption: 'Assign FOH and BOH jobs with clear ownership every shift.',
            },
        ),
        features: [
            {
                icon: '🍽️',
                title: 'Opening & closing lists',
                description: 'Repeatable checklists for every shift—no more laminated sheets.',
            },
            {
                icon: '📦',
                title: 'Deliveries & supplier runs',
                description: 'Track delivery windows and maintenance visits tied to your site.',
            },
            {
                icon: '👨‍🍳',
                title: 'Kitchen & FOH coordination',
                description: 'One board for prep tasks, repairs, and manager follow-ups.',
            },
            {
                icon: '🎙️',
                title: 'Quick voice capture',
                description: 'Managers dictate tasks during service without stopping the flow.',
            },
            {
                icon: '📍',
                title: 'Multi-site groups',
                description: 'Restaurant groups manage tasks across locations from one account.',
            },
            {
                icon: '⚡',
                title: 'Set up in minutes',
                description: 'Staff learn it on their first shift—no training day required.',
            },
        ],
        exampleTasks: [
            { title: 'Opening checklist — Friday lunch', column: 'Pre-service' },
            { title: 'Fridge engineer visit', location: 'Site address', column: 'Scheduled' },
            { title: 'Wine delivery — confirm stock in', column: 'Deliveries' },
            { title: 'Deep clean — extraction hood', column: 'Maintenance' },
        ],
        ctaHeadline: 'Bring order to busy service periods',
        ctaSubheadline: 'Restaurants use ZapTask for shift tasks, maintenance, and supplier coordination without enterprise hospitality software.',
    },
    {
        slug: 'hotels',
        label: 'Hotels',
        audienceLabel: 'Built for hotels',
        headline: 'Coordinate housekeeping, maintenance, and front desk follow-ups across your property',
        subheadline:
            'Track room-ready tasks, engineering jobs, and guest-request follow-ups on a board your duty managers can monitor in real time—plus map-based tasks for multi-property hotel groups.',
        keywords:
            'hotel task management, housekeeping checklist software, hotel maintenance tracker, hospitality operations app UK',
        seoTitle: 'Task Management for Hotels | ZapTask',
        seoDescription:
            'Coordinate housekeeping, engineering, and front desk tasks across your hotel. ZapTask helps hospitality teams manage daily operations simply.',
        screenshots: shots(
            {
                alt: 'Hotel operations task dashboard',
                caption: 'Housekeeping, engineering, and FO tasks in one feed.',
            },
            {
                alt: 'Hotel maintenance schedule',
                caption: 'Plan PPM, room refreshes, and contractor visits.',
            },
            {
                alt: 'Hotel task assignment board',
                caption: 'Guest requests and internal jobs tracked to completion.',
            },
        ),
        features: [
            {
                icon: '🛎️',
                title: 'Guest request tracking',
                description: 'Turn front desk requests into assigned tasks with clear ownership.',
            },
            {
                icon: '🧹',
                title: 'Housekeeping coordination',
                description: 'Room-ready priorities and deep-clean schedules on one board.',
            },
            {
                icon: '🔧',
                title: 'Engineering jobs',
                description: 'Track maintenance tickets from report to room back in service.',
            },
            {
                icon: '🏨',
                title: 'Multi-property support',
                description: 'Hotel groups manage tasks across sites with location tagging.',
            },
            {
                icon: '📋',
                title: 'Audit & compliance tasks',
                description: 'Fire checks, health & safety walks, and brand standards reviews.',
            },
            {
                icon: '👥',
                title: 'Duty manager visibility',
                description: 'See open jobs across departments during peak occupancy.',
            },
        ],
        exampleTasks: [
            { title: 'Room 214 — AC unit fault', column: 'Engineering' },
            { title: 'VIP arrival prep — Suite 501', column: 'Today' },
            { title: 'Pool plant inspection', column: 'PPM' },
            { title: 'Linen delivery confirmation', column: 'Housekeeping' },
        ],
        ctaHeadline: 'Smoother operations for every department',
        ctaSubheadline: 'Hotels use ZapTask to connect housekeeping, engineering, and front desk teams on one simple task board.',
    },
    {
        slug: 'retail',
        label: 'Retail',
        audienceLabel: 'Built for retail',
        headline: 'Manage store tasks, deliveries, and visual merchandising across your shops',
        subheadline:
            'Assign daily opening jobs, track deliveries and maintenance visits by store address, and give area managers visibility across multiple locations without a complex retail ops platform.',
        keywords:
            'retail task management, store operations checklist, multi-store task app, retail staff coordination UK',
        seoTitle: 'Task Management for Retail | ZapTask',
        seoDescription:
            'Manage store opening checklists, deliveries, and merchandising tasks across locations. ZapTask helps retail teams coordinate daily operations simply.',
        screenshots: shots(
            {
                alt: 'Retail store tasks by location',
                caption: 'Every shop, pop-up, and stock room visible on one map.',
            },
            {
                alt: 'Retail operations calendar',
                caption: 'Plan deliveries, refits, and seasonal campaign rollouts.',
            },
            {
                alt: 'Retail task board by store',
                caption: 'Area managers see open jobs across their estate.',
            },
        ),
        features: [
            {
                icon: '🏪',
                title: 'Tasks per store',
                description: 'Every job tagged to the shop address—ideal for multi-site retailers.',
            },
            {
                icon: '📦',
                title: 'Delivery & refit tracking',
                description: 'Confirm stock deliveries and shopfit milestones on a shared board.',
            },
            {
                icon: '📋',
                title: 'Daily store routines',
                description: 'Opening, cashing up, and VM tasks assigned to shift teams.',
            },
            {
                icon: '🗺️',
                title: 'Area manager routes',
                description: 'Plan store visit days efficiently across your region.',
            },
            {
                icon: '📸',
                title: 'Campaign rollouts',
                description: 'Track window changes and promotional setup store by store.',
            },
            {
                icon: '👥',
                title: 'Head office to shop floor',
                description: 'Central teams assign priorities; stores mark complete from mobile.',
            },
        ],
        exampleTasks: [
            { title: 'Delivery receipt — Store 042', location: 'Leicester', column: 'Today' },
            { title: 'Summer window refresh', location: 'Nottingham', column: 'VM' },
            { title: 'Fridge alarm call-out', column: 'Maintenance' },
            { title: 'Area manager store visit', column: 'Scheduled' },
        ],
        ctaHeadline: 'Keep every store on the same page',
        ctaSubheadline: 'Retailers use ZapTask to coordinate store tasks, deliveries, and area manager visits across their estate.',
    },
    {
        slug: 'marketing-agencies',
        label: 'Marketing agencies',
        audienceLabel: 'Built for marketing agencies',
        headline: 'Track campaigns, client deliverables, and on-site activations in one board',
        subheadline:
            'Manage client work without bloated project tools—use Kanban for campaign stages, attach location tasks for events and shoots, and keep your team aligned on what ships this week.',
        keywords:
            'marketing agency project management, campaign task board, client deliverable tracker, agency workflow software UK',
        seoTitle: 'Task Management for Marketing Agencies | ZapTask',
        seoDescription:
            'Track campaigns, client deliverables, and event activations on Kanban boards. ZapTask gives marketing agencies clarity without heavyweight PM software.',
        screenshots: shots(
            {
                alt: 'Marketing campaign activity feed',
                caption: 'Client updates and team actions in one timeline.',
            },
            {
                alt: 'Campaign deadline calendar',
                caption: 'Launch dates, shoots, and client reviews scheduled clearly.',
            },
            {
                alt: 'Marketing campaign Kanban board',
                caption: 'Brief → creative → approval → live—visible to the whole team.',
            },
        ),
        features: [
            {
                icon: '📣',
                title: 'Campaign boards',
                description: 'Track creative, approval, and launch stages on Kanban columns everyone understands.',
            },
            {
                icon: '📍',
                title: 'Event & shoot locations',
                description: 'Pin activation days, photo shoots, and client visits to addresses.',
            },
            {
                icon: '👥',
                title: 'Client project visibility',
                description: 'Separate boards per client or campaign without enterprise PM pricing.',
            },
            {
                icon: '🎙️',
                title: 'Briefing notes to tasks',
                description: 'Turn client calls into assigned deliverables with AI meeting notes.',
            },
            {
                icon: '📅',
                title: 'Deadline clarity',
                description: 'See what is due this week across retainers and one-off projects.',
            },
            {
                icon: '⚡',
                title: 'Agile without the jargon',
                description: 'No sprints or story points—just tasks, owners, and due dates.',
            },
        ],
        exampleTasks: [
            { title: 'Q3 social assets — Client A', column: 'In review' },
            { title: 'Product shoot — warehouse location', location: 'Manchester', column: 'Scheduled' },
            { title: 'Landing page copy approval', column: 'Waiting on client' },
            { title: 'Launch day checklist', column: 'Ready to ship' },
        ],
        ctaHeadline: 'Ship campaigns on time without tool overload',
        ctaSubheadline: 'Marketing agencies use ZapTask for campaign tracking, client deliverables, and event coordination.',
    },
    {
        slug: 'software-development',
        label: 'Software development',
        audienceLabel: 'Built for software teams',
        headline: 'Lightweight task boards for small dev teams who outgrew the sticky-note wall',
        subheadline:
            'Kanban for bugs, features, and releases without Jira complexity. Add location tasks for on-site installs and client visits, capture stand-up notes by voice, and keep your team focused on shipping.',
        keywords:
            'software team task management, lightweight Kanban for developers, startup project board, dev team task app UK',
        seoTitle: 'Task Management for Software Development Teams | ZapTask',
        seoDescription:
            'Kanban boards for bugs, features, and releases without Jira complexity. ZapTask helps small software teams ship with less process overhead.',
        screenshots: shots(
            {
                alt: 'Software team activity feed',
                caption: 'Commits, comments, and task updates in one stream.',
            },
            {
                alt: 'Sprint and release calendar',
                caption: 'Plan releases, on-site installs, and client workshops.',
            },
            {
                alt: 'Software development Kanban board',
                caption: 'Backlog → in progress → review → done—keep it simple.',
            },
        ),
        features: [
            {
                icon: '💻',
                title: 'Simple dev boards',
                description: 'Bugs, features, and chores on Kanban—no sprint ceremony required.',
            },
            {
                icon: '🚀',
                title: 'Release checklists',
                description: 'Track deploy steps, QA sign-off, and rollback tasks clearly.',
            },
            {
                icon: '📍',
                title: 'On-site client work',
                description: 'Pin installation and training visits to client offices on the map.',
            },
            {
                icon: '🎙️',
                title: 'Stand-up & retro notes',
                description: 'AI turns meeting recordings into assigned action items.',
            },
            {
                icon: '👥',
                title: 'Small team friendly',
                description: 'Built for agencies and product teams of 2–20—not enterprise scale.',
            },
            {
                icon: '💷',
                title: 'Honest pricing',
                description: 'Free tier for solo devs; affordable plans as you hire.',
            },
        ],
        exampleTasks: [
            { title: 'Fix login redirect bug', column: 'In progress' },
            { title: 'Client onboarding — onsite training', location: 'London', column: 'Scheduled' },
            { title: 'v2.4 release checklist', column: 'Release' },
            { title: 'API documentation update', column: 'Backlog' },
        ],
        ctaHeadline: 'Ship faster with less process overhead',
        ctaSubheadline: 'Software teams use ZapTask when Jira feels like overkill but spreadsheets are not enough.',
    },
    {
        slug: 'accountants',
        label: 'Accountants',
        audienceLabel: 'Built for accountants',
        headline: 'Track client deadlines, site visits, and practice admin on one clear board',
        subheadline:
            'Manage tax deadlines, audit fieldwork, and client onboarding steps without drowning in email. Assign tasks to partners and staff, plan client visit days on a map, and see what is due this week at a glance.',
        keywords:
            'accountancy practice task management, accountant deadline tracker, client onboarding checklist, accounting firm workflow UK',
        seoTitle: 'Task Management for Accountants | ZapTask',
        seoDescription:
            'Track tax deadlines, client visits, and practice admin on Kanban boards. ZapTask helps accountancy firms coordinate work without complex practice management software.',
        screenshots: shots(
            {
                alt: 'Accountancy practice task feed',
                caption: 'Client deadlines and internal actions in one place.',
            },
            {
                alt: 'Tax deadline calendar',
                caption: 'Self assessment, VAT, and year-end dates scheduled clearly.',
            },
            {
                alt: 'Client work status board',
                caption: 'Onboarding → in progress → review → filed with the team.',
            },
        ),
        features: [
            {
                icon: '📊',
                title: 'Deadline tracking',
                description: 'Assign VAT, payroll, and year-end jobs with clear owners and due dates.',
            },
            {
                icon: '🏢',
                title: 'Client visit planning',
                description: 'Pin audit and onboarding meetings to client offices on the map.',
            },
            {
                icon: '📋',
                title: 'Onboarding checklists',
                description: 'Standardise new client setup steps so nothing is missed.',
            },
            {
                icon: '👥',
                title: 'Partner visibility',
                description: 'Partners see open items across the team without micromanaging.',
            },
            {
                icon: '🎙️',
                title: 'Meeting notes to tasks',
                description: 'Client call actions captured and assigned automatically.',
            },
            {
                icon: '⚡',
                title: 'Lighter than practice suites',
                description: 'Task coordination without replacing your accounting software.',
            },
        ],
        exampleTasks: [
            { title: 'Year-end accounts — Smith & Co', column: 'In progress' },
            { title: 'VAT return — deadline Friday', column: 'Due this week' },
            { title: 'New client AML checks', column: 'Onboarding' },
            { title: 'Onsite stock count — client visit', location: 'Cambridge', column: 'Scheduled' },
        ],
        ctaHeadline: 'Meet every deadline with less partner stress',
        ctaSubheadline: 'Accountancy practices use ZapTask to coordinate client work, deadlines, and site visits alongside their existing tools.',
    },
    {
        slug: 'recruitment',
        label: 'Recruitment',
        audienceLabel: 'Built for recruitment agencies',
        headline: 'Manage candidate pipelines, client visits, and team targets on one board',
        subheadline:
            'Track roles, interview stages, and client meeting follow-ups without a heavyweight ATS add-on. Plan consultant days on a map when you are meeting candidates and clients across town.',
        keywords:
            'recruitment agency task management, recruiter pipeline board, candidate tracking app, recruitment workflow UK',
        seoTitle: 'Task Management for Recruitment Agencies | ZapTask',
        seoDescription:
            'Track candidate pipelines, client meetings, and team follow-ups on Kanban boards. ZapTask helps recruiters stay organised alongside their ATS.',
        screenshots: shots(
            {
                alt: 'Recruitment agency activity feed',
                caption: 'Candidate updates and client actions in one stream.',
            },
            {
                alt: 'Recruiter interview calendar',
                caption: 'Interviews, client visits, and offer deadlines scheduled.',
            },
            {
                alt: 'Recruitment pipeline board',
                caption: 'Role brief → sourcing → interview → offer—clear stages.',
            },
        ),
        features: [
            {
                icon: '🎯',
                title: 'Role pipelines',
                description: 'Kanban stages for each live role—visible to the whole desk.',
            },
            {
                icon: '📍',
                title: 'Client & candidate visits',
                description: 'Plan meeting days across your patch with map-based tasks.',
            },
            {
                icon: '📋',
                title: 'Follow-up discipline',
                description: 'Never lose track of post-interview actions and client callbacks.',
            },
            {
                icon: '🎙️',
                title: 'Briefing call notes',
                description: 'Turn client intake calls into assigned sourcing tasks.',
            },
            {
                icon: '👥',
                title: 'Team targets visibility',
                description: 'Managers see open actions without sitting on every call.',
            },
            {
                icon: '⚡',
                title: 'Works beside your ATS',
                description: 'Coordination layer—not a replacement for candidate records.',
            },
        ],
        exampleTasks: [
            { title: 'Senior dev role — Client X', column: 'Interviewing' },
            { title: 'Candidate coffee — town centre', location: 'Leeds', column: 'Today' },
            { title: 'Send shortlist to hiring manager', column: 'Follow-up' },
            { title: 'New role brief — marketing manager', column: 'New brief' },
        ],
        ctaHeadline: 'Close more placements with clearer follow-ups',
        ctaSubheadline: 'Recruitment agencies use ZapTask to manage pipelines, meetings, and desk coordination.',
    },
    {
        slug: 'schools',
        label: 'Schools',
        audienceLabel: 'Built for schools',
        headline: 'Coordinate maintenance, events, and admin tasks across your school site',
        subheadline:
            'Give office staff, caretakers, and senior leaders one place to track jobs—from classroom repairs to open-day prep. Plan caretaker routes across buildings and keep term-time projects visible without enterprise school MIS add-ons.',
        keywords:
            'school task management, school maintenance tracker, education admin software, caretaker job planner UK',
        seoTitle: 'Task Management for Schools | ZapTask',
        seoDescription:
            'Coordinate maintenance, events, and admin tasks across your school site. ZapTask helps schools manage jobs simply alongside existing systems.',
        screenshots: shots(
            {
                alt: 'School site tasks on a map',
                caption: 'Buildings, playgrounds, and facilities jobs by location.',
            },
            {
                alt: 'School term calendar',
                caption: 'Events, maintenance windows, and project deadlines.',
            },
            {
                alt: 'School operations task board',
                caption: 'Office, caretaking, and leadership tasks tracked clearly.',
            },
        ),
        features: [
            {
                icon: '🏫',
                title: 'Site-wide jobs',
                description: 'Tasks tagged to blocks, playgrounds, and facilities across campus.',
            },
            {
                icon: '🔧',
                title: 'Caretaker coordination',
                description: 'Repairs, inspections, and contractor visits on one board.',
            },
            {
                icon: '📅',
                title: 'Term-time planning',
                description: 'Open evenings, trips, and holiday maintenance projects scheduled.',
            },
            {
                icon: '👥',
                title: 'Office & leadership visibility',
                description: 'SBM and admin staff see open jobs without hallway chasing.',
            },
            {
                icon: '📋',
                title: 'Event checklists',
                description: 'Standardise setup tasks for sports day, parents evening, and more.',
            },
            {
                icon: '🔒',
                title: 'Not your MIS',
                description: 'Task coordination only—complements SIMS, Arbor, and similar systems.',
            },
        ],
        exampleTasks: [
            { title: 'Science lab leak — Room B12', column: 'Urgent' },
            { title: 'Parents evening setup', column: 'This week' },
            { title: 'Playground resurfacing quote', column: 'Projects' },
            { title: 'Fire drill follow-up actions', column: 'Compliance' },
        ],
        ctaHeadline: 'Less hallway chasing, more teaching time',
        ctaSubheadline: 'Schools use ZapTask to coordinate caretaking, events, and admin tasks across the site.',
    },
    {
        slug: 'charities',
        label: 'Charities',
        audienceLabel: 'Built for charities',
        headline: 'Organise campaigns, volunteer coordination, and field work on a simple board',
        subheadline:
            'Small charity teams juggle fundraising, delivery programmes, and grant reporting with limited admin capacity. ZapTask gives you tasks, maps for community visits, and boards your staff and volunteers can use without training.',
        keywords:
            'charity task management, nonprofit project board, volunteer coordination app, charity operations software UK',
        seoTitle: 'Task Management for Charities | ZapTask',
        seoDescription:
            'Organise campaigns, volunteer shifts, and community visits with simple boards and maps. ZapTask helps charity teams coordinate work on tight budgets.',
        screenshots: shots(
            {
                alt: 'Charity programme locations on a map',
                caption: 'Community visits, events, and delivery sites plotted.',
            },
            {
                alt: 'Charity campaign calendar',
                caption: 'Fundraising drives, grant deadlines, and volunteer days.',
            },
            {
                alt: 'Charity project task board',
                caption: 'Campaign planning from idea to delivery—visible to all.',
            },
        ),
        features: [
            {
                icon: '💚',
                title: 'Campaign planning',
                description: 'Track fundraising and awareness campaigns on clear Kanban stages.',
            },
            {
                icon: '📍',
                title: 'Community visits',
                description: 'Pin outreach sessions and beneficiary visits to locations on the map.',
            },
            {
                icon: '🙋',
                title: 'Volunteer coordination',
                description: 'Assign shifts and follow-up tasks without complex volunteer CRMs.',
            },
            {
                icon: '📋',
                title: 'Grant milestone tracking',
                description: 'Reportable deliverables visible to managers and trustees.',
            },
            {
                icon: '🎙️',
                title: 'Trustee meeting notes',
                description: 'AI captures actions from board meetings automatically.',
            },
            {
                icon: '💷',
                title: 'Charity-friendly pricing',
                description: 'Free and low-cost plans for small teams doing big work.',
            },
        ],
        exampleTasks: [
            { title: 'Food bank delivery run', location: 'Newcastle', column: 'Today' },
            { title: 'Grant report draft — due 15th', column: 'Reporting' },
            { title: 'Volunteer induction — Saturday', column: 'Scheduled' },
            { title: 'Summer appeal social assets', column: 'Campaign' },
        ],
        ctaHeadline: 'Do more good with less admin burden',
        ctaSubheadline: 'Charities use ZapTask to coordinate programmes, volunteers, and campaigns without enterprise nonprofit software.',
    },
    {
        slug: 'manufacturing',
        label: 'Manufacturing',
        audienceLabel: 'Built for manufacturing',
        headline: 'Track maintenance, quality checks, and improvement actions on the shop floor',
        subheadline:
            'Give production and maintenance teams a simple board for daily jobs, tie site visits and supplier meetings to locations, and capture gemba walk findings as assigned tasks—without a full MES implementation.',
        keywords:
            'manufacturing task management, shop floor job tracker, maintenance planner SME, factory operations software UK',
        seoTitle: 'Task Management for Manufacturing | ZapTask',
        seoDescription:
            'Track maintenance, quality checks, and improvement actions on the shop floor. ZapTask helps manufacturing SMEs coordinate work without complex MES systems.',
        screenshots: shots(
            {
                alt: 'Manufacturing site tasks',
                caption: 'Maintenance, quality, and supplier visits across your facility.',
            },
            {
                alt: 'Production maintenance calendar',
                caption: 'PPM, shutdowns, and audit dates in one schedule.',
            },
            {
                alt: 'Manufacturing improvement board',
                caption: 'Andon follow-ups, CAPA actions, and project tasks tracked.',
            },
        ),
        features: [
            {
                icon: '🏭',
                title: 'Shop floor actions',
                description: 'Track maintenance tickets, quality holds, and improvement tasks clearly.',
            },
            {
                icon: '🔧',
                title: 'PPM scheduling',
                description: 'Planned maintenance and calibration reminders on a shared board.',
            },
            {
                icon: '📋',
                title: 'Gemba & audit findings',
                description: 'Turn walk-round observations into owned follow-up tasks.',
            },
            {
                icon: '📍',
                title: 'Supplier & site visits',
                description: 'Plan external meetings and deliveries with location tasks.',
            },
            {
                icon: '👥',
                title: 'Shift handover clarity',
                description: 'Open jobs visible between production and maintenance teams.',
            },
            {
                icon: '⚡',
                title: 'SME-appropriate',
                description: 'Operational clarity without six-figure ERP modules.',
            },
        ],
        exampleTasks: [
            { title: 'Line 2 conveyor belt inspection', column: 'Maintenance' },
            { title: 'CAPA — batch 4421 quality hold', column: 'Quality' },
            { title: 'Supplier audit — components Ltd', location: 'Coventry', column: 'Scheduled' },
            { title: 'Kaizen idea — packaging station', column: 'Improvement' },
        ],
        ctaHeadline: 'Operational clarity without ERP complexity',
        ctaSubheadline: 'Manufacturing SMEs use ZapTask for maintenance, quality actions, and improvement tracking on the shop floor.',
    },
];

export const INDUSTRY_LANDING_BY_SLUG: Record<string, IndustryLandingPage> = Object.fromEntries(
    INDUSTRY_LANDING_PAGES.map((page) => [page.slug, page]),
);

export function getIndustryLandingPage(slug: string): IndustryLandingPage | undefined {
    return INDUSTRY_LANDING_BY_SLUG[slug];
}

export const ALL_INDUSTRY_SLUGS = INDUSTRY_LANDING_PAGES.map((page) => page.slug);
