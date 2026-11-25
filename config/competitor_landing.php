<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comparison pages (/vs/{slug})
    |--------------------------------------------------------------------------
    */
    'comparisons' => [
        'trello',
        'asana',
        'monday',
        'jira',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alternative pages (/alternative-to/{slug})
    |--------------------------------------------------------------------------
    */
    'alternatives' => [
        'trello',
        'asana',
        'monday',
        'jira',
        'spreadsheets',
    ],

    'seo' => [
        'comparisons' => [
            'trello' => [
                'title' => 'ZapTask vs Trello | Task Management Comparison',
                'description' => 'Compare ZapTask and Trello for estate agents and field teams. Get location tasks, map routes, and focused Kanban boards without messy freeform boards.',
                'keywords' => 'ZapTask vs Trello, Trello alternative for estate agents, location task management UK',
            ],
            'asana' => [
                'title' => 'ZapTask vs Asana | Simpler Task Management',
                'description' => 'Compare ZapTask and Asana for small teams. ZapTask keeps boards lean with maps, routes, and on-site check-in—without heavyweight project setup.',
                'keywords' => 'ZapTask vs Asana, Asana alternative small business, simple task management UK',
            ],
            'monday' => [
                'title' => 'ZapTask vs Monday.com | Focused Field Team Boards',
                'description' => 'Compare ZapTask and Monday.com for trades and property teams. Location tasks, daily routes, and simple boards without enterprise work OS complexity.',
                'keywords' => 'ZapTask vs Monday, Monday.com alternative, field team task management UK',
            ],
            'jira' => [
                'title' => 'ZapTask vs Jira | Task Boards Without the Overhead',
                'description' => 'Compare ZapTask and Jira for agencies and non-dev teams. Clear boards, maps, and client-friendly workflows without admin-heavy issue tracking.',
                'keywords' => 'ZapTask vs Jira, Jira alternative for agencies, simple Kanban UK',
            ],
        ],
        'alternatives' => [
            'trello' => [
                'title' => 'Trello Alternative for Estate Agents & Field Teams | ZapTask',
                'description' => 'Looking for a Trello alternative? ZapTask adds location tasks, map routes, and opinionated workflows built for estate agents, trades, and small businesses.',
                'keywords' => 'Trello alternative for estate agents, Trello alternative UK, location Kanban board',
            ],
            'asana' => [
                'title' => 'Asana Alternative for Small Teams | ZapTask',
                'description' => 'Need an Asana alternative without the complexity? ZapTask gives small teams maps, routes, voice tasks, and clear boards they will actually use.',
                'keywords' => 'Asana alternative small business, simpler than Asana, task management UK',
            ],
            'monday' => [
                'title' => 'Monday.com Alternative for Property & Trades | ZapTask',
                'description' => 'A Monday.com alternative for teams on the move. Pin jobs to addresses, plan routes, and keep boards simple for property and trade businesses.',
                'keywords' => 'Monday.com alternative, Monday alternative for trades, property task software UK',
            ],
            'jira' => [
                'title' => 'Jira Alternative for Agencies & Non-Dev Teams | ZapTask',
                'description' => 'A lighter Jira alternative for agencies and client work. Fast onboarding, clear boards, and location tasks without enterprise workflow overhead.',
                'keywords' => 'Jira alternative for agencies, lightweight Jira alternative, client task board UK',
            ],
            'spreadsheets' => [
                'title' => 'Spreadsheet Alternative for Task & Compliance Tracking | ZapTask',
                'description' => 'Replace messy spreadsheets with ZapTask. Track jobs, certificates, and site visits on maps and boards with reminders before deadlines slip.',
                'keywords' => 'spreadsheet alternative task management, replace Excel job tracker, compliance tracker UK',
            ],
        ],
    ],
];
